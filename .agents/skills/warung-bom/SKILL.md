---
name: warung-bom
description: "Mengelola Bill of Materials (BOM/Resep) produksi. Aktif saat membuat, mengedit, atau melihat resep produksi barang jadi dari bahan baku."
---

# Modul BOM (Bill of Materials / Resep Produksi)

## Deskripsi
Modul ini mengatur resep produksi (BOM) yang mendefinisikan berapa banyak bahan baku dibutuhkan untuk menghasilkan 1 unit barang jadi. Sistem juga menghitung HPP (Harga Pokok Penjualan) secara otomatis berdasarkan komposisi bahan.

## Komponen Utama

### Backend
- **Model**: `App\Models\Bom` — Resep utama, terhubung ke 1 produk finished_good
- **Model**: `App\Models\BomItem` — Item bahan baku per resep (ingredient)
- **Controller**: `App\Http\Controllers\BOMController`
- **Form Requests**: `App\Http\Requests\StoreBOMRequest`, `App\Http\Requests\UpdateBOMRequest`
- **Action**: `App\Actions\RecalculateHpp` — Menghitung ulang HPP berdasarkan harga bahan baku
- **Service**: `App\Services\SatuanService` — Konversi satuan antar unit (BFS pathfinding)
- **Database**: Tabel `boms`, `bom_items`

### Frontend
- **Pages**: `resources/js/pages/bom/Index.vue`, `Create.vue`, `Edit.vue`
- **Wayfinder Actions**: `@/actions/App/Http/Controllers/BOMController`

## Skema Data

### Tabel `boms`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint | Primary key |
| produk_id | FK → produks | Produk barang jadi yang dihasilkan |
| sku | string (unique) | SKU resep, auto-generate jika kosong (BOM-0001) |
| nama | string (nullable) | Nama resep (misal: "Resep Standar Roti Tawar") |
| is_active | boolean | Status aktif resep |
| expected_yield | decimal | Berapa unit barang jadi dihasilkan dari resep ini |
| auto_deduct_on_sale | boolean | Otomatis potong stok bahan baku saat penjualan di POS |

### Tabel `bom_items`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint | Primary key |
| bom_id | FK → boms | Resep induk |
| produk_id | FK → produks | Bahan baku (raw_material / intermediate_good) |
| satuan_id | FK → satuans (nullable) | Override satuan untuk konversi |
| jumlah | decimal(20,4) | Jumlah bahan yang dibutuhkan |

## Aturan Bisnis

### HPP (Harga Pokok Penjualan)
- HPP dihitung otomatis oleh `RecalculateHpp` action saat BOM dibuat/diupdate.
- **Formula**: `HPP per unit = Total biaya semua bahan baku / expected_yield`
- HPP disimpan sebagai `purchase_price` di tabel `prices` untuk produk finished_good.
- **Cascade**: Jika harga bahan baku berubah, HPP semua produk yang menggunakannya di-recalculate.
- Saat membuat seeder atau data BOM secara programmatic, **WAJIB** panggil `RecalculateHpp::handle()` setelah membuat BOM items agar HPP terisi.

### Konversi Satuan
- `SatuanService::getConversionRatio()` menggunakan BFS untuk mencari jalur konversi.
- Mendukung konversi product-specific dan global fallback.
- Frontend juga punya logic konversi identik di `getConversionRatio()` untuk live preview HPP.

### SKU Auto-generate
- Jika SKU kosong saat create, sistem generate format `BOM-XXXX` (zero-padded 4 digit).

### Auto-Deduct on Sale
- Jika `auto_deduct_on_sale = true`, saat barang jadi terjual di POS, stok bahan baku otomatis berkurang sesuai resep.

## Relasi Model
- `Bom` → `belongsTo` → `Produk` (barang jadi)
- `Bom` → `hasMany` → `BomItem` (list bahan)
- `BomItem` → `belongsTo` → `Produk` (bahan baku)
- `BomItem` → `belongsTo` → `Satuan` (override unit)
- `Produk` → `hasOne` → `Bom` (1 produk hanya 1 resep aktif)

## Routes (Resource)
| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET | `/bom` | bom.index | Daftar semua BOM |
| GET | `/bom/create` | bom.create | Form buat BOM baru |
| POST | `/bom` | bom.store | Simpan BOM baru |
| GET | `/bom/{bom}/edit` | bom.edit | Form edit BOM |
| PUT/PATCH | `/bom/{bom}` | bom.update | Update BOM |
| DELETE | `/bom/{bom}` | bom.destroy | Hapus BOM |

## Tips Frontend

### Index Page (Datatable)
- HPP ditampilkan dari `bom.produk.current_price.purchase_price` (hasil kalkulasi server-side dari `RecalculateHpp`).
- Gunakan `formatCurrency` helper untuk format Rupiah.
- Search mendukung nama resep, SKU BOM, dan nama produk.

### Create & Edit Page
- HPP dihitung **client-side** secara live dari data `bahanBakus` yang dikirim controller.
- Fungsi `getItemCost(item)` menghitung biaya per bahan dengan konversi satuan.
- `totalEstimatedCost` computed property menjumlahkan semua biaya bahan.
- HPP per unit = `totalEstimatedCost / expected_yield`.
- Gunakan `CreatableSelect` component untuk memilih bahan baku dan satuan.

### Sync Yield Banner (Edit Page)
- Jika ada produksi terbaru dengan `actual_yield` berbeda dari `expected_yield`, tampilkan banner info.
- User bisa klik "Sync HPP Sekarang" untuk update yield dari data aktual.

## Seeder
- Saat membuat seeder BOM, selalu panggil `RecalculateHpp` setelah membuat BOM items:
```php
$bom = Bom::create([...]);
foreach ($recipe as $item) {
    BomItem::create([...]);
}
// WAJIB: trigger HPP calculation
app(\App\Actions\RecalculateHpp::class)->handle($produk);
```

## Validasi (StoreBOMRequest)
- `produk_id`: required, exists in produks, unique per BOM (1 produk = 1 resep)
- `items`: required, minimal 1 bahan
- `items.*.jumlah`: required, min 0.0001
- `expected_yield`: required, min 0.0001
