---
name: warung-harga
description: "Mengelola harga beli (modal) dan harga jual barang. Mengatur margin keuntungan."
---

# Modul Harga Item

## Deskripsi
- **Model**: `App\Models\Harga`
- **Observer**: `App\Observers\HargaObserver`

## Komponen Harga
- **Harga Beli (Modal)**: Harga terakhir dari vendor.
- **Harga Jual Eceran**: Harga buat pembeli biasa.
- **Harga Grosir**: Harga buat pembeli partai besar (optional).

## Aturan Bisnis
- **Observer**: `HargaObserver` wajib mengirim notifikasi/alert jika ada lonjakan harga beli yang signifikan.
- Margin keuntungan minimal 10% (sesuaikan kebijakan warung).
- Jika harga beli naik, AI harus kasih alert buat update harga jual.
- Simpan history perubahan harga untuk pantau inflasi barang.

## Pengetahuan AI
- **Laravel Scout**: Gunakan Scout untuk mencari history perubahan harga item tertentu.
- Hitung otomatis profit per barang atau per kategori.
- Berikan saran harga jual berdasarkan kenaikan harga modal terbaru.
