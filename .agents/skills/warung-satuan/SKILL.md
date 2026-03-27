---
name: warung-satuan
description: "Mengelola satuan barang (pcs, kg, renteng, dus). Mengatur konversi antar satuan jika diperlukan."
---

# Modul Satuan Barang

## Deskripsi
- **Model**: `App\Models\Satuan`
- **Observer**: `App\Observers\SatuanObserver`
- **Controller**: `App\Http\Controllers\SatuanController`
- **UI**: `resources/js/pages/satuan/`

## Daftar Satuan Umum
- **Pcs**: Untuk barang satuan.
- **Kg/Gram**: Untuk barang timbangan (beras, gula).
- **Renteng/Pak/Dus**: Untuk barang grosir.

## Aturan Bisnis
- **Observer**: `SatuanObserver` memastikan integritas data saat konversi satuan diubah.
- Tiap barang wajib punya "Satuan Terkecil" untuk perhitungan stok.
- Konversi harus jelas (misal: 1 Dus = 40 Pcs).

## Pengetahuan AI
- **Laravel Scout**: Gunakan Scout untuk mencari jenis satuan atau rumus konversi yang sudah ada.
- AI harus bisa konversi otomatis dari harga dus ke harga eceran.
- Pastikan user nggak salah input satuan (misal: barang cair pake unit berat).
