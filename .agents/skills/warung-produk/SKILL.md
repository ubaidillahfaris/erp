---
name: warung-produk
description: "Mengelola data barang dagangan di warung. Aktif saat menambah, mengedit, atau melihat daftar produk/barang."
---

# Modul Produk Warung

## Deskripsi
Modul ini digunakan untuk mencatat semua barang yang dijual di warung, termasuk harga beli, harga jual, dan stok awal.

## Komponen Utama
- **Model**: `App\Models\Produk`
- **Observer**: `App\Observers\ProdukObserver`
- **Controller**: `App\Http\Controllers\ProdukController`
- **Frontend Pages**: `resources/js/pages/produk/`
- **Database**: Tabel `produks`

## Aturan Bisnis
- **Routing (Wayfinder)**: Gunakan `@/actions/.../ProdukController` untuk navigasi dan form.
- **Inertia v2**: Gunakan `useForm` untuk handling data produk dan `router.get` dengan debounce untuk search.
- **Tailwind v4**: Gunakan utility classes dari v4 untuk layout tabel dan form yang premium.
- **SKU (Stock Keeping Unit)**: Wajib unik. Jika dikosongkan saat input, sistem akan generate otomatis dengan format `[3-HURUF-NAMA]-[NOMOR-URUT]` (misal: IND-0001).
- **Konsep 'Simpan & Tambah Lagi'**: Gunakan komponen `FormActionButtons` pada form Create. Controller akan mengecek flag `add_another` untuk menentukan apakah user di-redirect balik ke form (untuk input massal) atau ke halaman index.
- Tiap barang harus punya nama yang jelas.
- Satuan barang (pcs, kg, renteng) harus dicatat.
- Harga jual harus selalu di atas harga beli.

- **Laravel Scout**: Gunakan Scout untuk pencarian produk yang cepat dan *fuzzy search*.
- Gunakan pencarian produk berdasarkan nama, kategori, atau SKU.
- Stok harus selalu diperbarui tiap ada transaksi penjualan.
