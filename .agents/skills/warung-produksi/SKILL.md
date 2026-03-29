---
name: warung-produksi
description: "Mengelola alur produksi barang dari bahan baku menjadi barang jadi berdasarkan BOM. Aktif saat memulai produksi, memantau kemajuan, atau menyelesaikan produksi."
---

# Modul Produksi Warung

## Deskripsi
Modul ini digunakan untuk mencatat proses transformasi bahan baku (atau barang setengah jadi) menjadi barang jadi berdasarkan resep yang didefinisikan di Bill of Materials (BOM). Proses produksi mengelola pengurangan stok bahan baku dan penambahan stok barang jadi serta pemutakhiran HPP aktual.

## Komponen Utama
- **Model**: `App\Models\Production` — Data header produksi (target, yield, status).
- **Model**: `App\Models\ProductionItem` — Detail penggunaan bahan baku per sesi produksi.
- **Controller**: `App\Http\Controllers\ProductionController`
- **Action**: `App\Actions\CompleteProduction` — Logika penyelesaian produksi (stok & HPP).
- **Action**: `App\Actions\RecordStockMovement` — Pencatatan mutasi stok.
- **Frontend Pages**: `resources/js/pages/production/`
- **Database**: Tabel `productions` dan `production_items`.

## Alur Kerja (Workflow)
1. **Mulai Produksi (Create)**:
    - User memilih BOM.
    - Sistem mengambil data bahan baku dari BOM dan menghitung `planned_qty` berdasarkan `target_yield`.
    - Status awal: `in_progress`.
2. **Monitoring**:
    - Produksi yang sedang berjalan muncul di daftar dengan status `in_progress`.
3. **Penyelesaian (Complete/Edit)**:
    - User memasukkan `actual_yield` (hasil jadi nyata) dan `actual_qty` (bahan baku yang benar-benar terpakai).
    - Menghitung `total_cost` berdasarkan harga beli bahan baku saat itu.
    - Memanggil `CompleteProduction` action.
    - Status berubah menjadi `completed`.

## Aturan Bisnis
- **SKU Produksi**: Otomatis generate dengan format `PRD-YYMM-XXXX` jika kosong.
- **Status**: `draft`, `in_progress`, `completed`, `cancelled`.
    - Produksi `completed` tidak dapat dihapus atau diubah tanpa mekanisme reversal khusus.
- **Dampak Stok**:
    - Bahan baku berkurang (Movement: `out`, Reference: `production_usage`).
    - Barang jadi bertambah (Movement: `in`, Reference: `production_yield`).
- **Update HPP**: HPP barang jadi diperbarui berdasarkan total biaya bahan baku dibagi `actual_yield`.
- **Reproduce**: Memungkinkan pembuatan sesi produksi baru berdasarkan data produksi lama (untuk batch yang serupa).

## Integrasi
- **BOM**: Produksi wajib merujuk ke salah satu BOM yang aktif.
- **Satuan**: Mendukung penggunaan bahan dalam satuan yang berbeda dari satuan dasar produk melalui `SatuanService`.

## Tips Developer
- Selalu gunakan `DB::transaction` saat memproses penyelesaian produksi karena melibatkan banyak tabel (productions, items, stock_movements, prices).
- Gunakan `RecalculateHpp` jika diperlukan cascade update ke produk level di atasnya.
