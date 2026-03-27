---
name: warung-vendor
description: "Mengelola data supplier atau vendor tempat belanja stok warung."
---

# Modul Vendor / Supplier

## Deskripsi
- **Model**: `App\Models\Vendor`
- **Observer**: `App\Observers\VendorObserver`

## Informasi Vendor
- Nama Toko / Sales.
- No. HP / WhatsApp (Penting buat order stok).
- Jadwal Sales datang (misal: tiap Selasa).

## Aturan Bisnis
- **Observer**: Gunakan `VendorObserver` untuk membersihkan cache data vendor atau log histori order.
- Catat vendor mana yang kasih harga paling murah untuk item tertentu.
- Simpan riwayat nota pembelian dari vendor.

## Pengetahuan AI
- **Laravel Scout**: Gunakan Scout untuk mencari data vendor berdasarkan nama atau wilayah.
- Ingatkan user kapan harus belanja ke vendor tertentu berdasarkan stok yang tipis.
- Bandingkan harga antar vendor kalau ada data inputnya.
