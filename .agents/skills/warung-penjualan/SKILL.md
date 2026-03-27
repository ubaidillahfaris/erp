---
name: warung-penjualan
description: "Mengelola transaksi penjualan harian. Aktif saat membuat nota, menghitung total belanja, atau melihat laporan harian."
---

# Modul Penjualan Warung

## Deskripsi
Modul ini menangani proses transaksi antara penjual dan pembeli di warung.

## Komponen Utama
- **Model**: `App\Models\Transaksi`
- **Observer**: `App\Observers\TransaksiObserver`
- **Controller**: `App\Http\Controllers\PenjualanController`

## Aturan Bisnis
- **Observer**: `TransaksiObserver` harus memicu update stok produk setelah transaksi `created`.
- Transaksi dianggap sah jika pembayaran sudah diterima (Tunai).
- Stok produk harus berkurang otomatis setelah transaksi disimpan.
- Riwayat transaksi disimpan untuk laporan omzet harian.

## Pengetahuan AI
- **Laravel Scout**: Gunakan Scout untuk mencari riwayat transaksi berdasarkan nomor nota atau nama pembeli.
- AI harus bisa membantu menghitung total harga dari daftar belanja.
- AI harus bisa menyarankan produk yang sering dibeli bersamaan.
