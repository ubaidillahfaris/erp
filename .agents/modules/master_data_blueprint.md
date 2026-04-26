# Blueprint: Master Data Immutability (Produk)

## Overview
Implementasi `SoftDeletes` pada model `Produk` untuk mencegah kehilangan data transaksional (seperti riwayat penjualan dan stok) saat sebuah produk dihapus dari katalog aktif.

## Implementasi Teknis
1. **Database:** Menambahkan kolom `deleted_at` (timestamp) pada tabel `produks`.
2. **Model:** Menambahkan trait `Illuminate\Database\Eloquent\SoftDeletes` pada `App\Models\Produk`.
3. **Controller:** 
   - `ProdukController@destroy` tetap menggunakan `$produk->delete()`, yang sekarang secara otomatis melakukan *soft delete*.
   - `ProdukController@bulkDestroy` menggunakan query builder `delete()` yang juga menghormati *soft deletes*.

## Keuntungan Bisnis
- **Integritas Data:** Laporan penjualan masa lalu tetap bisa menampilkan detail produk meskipun produk tersebut sudah tidak dijual lagi.
- **Audit Trail:** Memungkinkan pemulihan data jika terjadi penghapusan yang tidak disengaja.

## Verifikasi
Telah diuji dengan `tests/Feature/Sprint1/ProductSoftDeleteTest.php` yang memvalidasi:
- Penghapusan tunggal menghasilkan `deleted_at` terisi dan data hilang dari query default.
- Penghapusan massal (*bulk*) juga menghasilkan *soft delete* yang benar.
