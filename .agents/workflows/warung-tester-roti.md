---
description: Menjalankan skenario E2E testing untuk flow Toko Roti menggunakan browser subagent dan berbagai role
---

# Workflow: Warung Tester - Skenario Toko Roti

Workflow ini menginstruksikan agent (AI) untuk bertindak sebagai QA Tester yang melakukan pengujian End-to-End (E2E) pada alur bisnis Toko Roti. AI akan menggunakan `browser_subagent` atau berinteraksi langsung dengan database untuk memasukkan data yang masuk akal dan realistis secara bertahap, mewakili berbagai peran (role) dalam sistem.

## Persiapan
1. Pastikan aplikasi Laravel telah berjalan (jalankan `php artisan serve` atau gunakan `composer dev`).
2. Tentukan base URL aplikasi (biasanya `http://localhost:8000` atau URL sesuai konfigurasi lokal).
3. Pastikan database sudah ter-migrate (`php artisan migrate:fresh --seed` jika perlu mereset data lama).

## Langkah-langkah Pengujian (Skenario Toko Roti)

Agent harus menjalankan langkah-langkah berikut secara berurutan, memasukkan data yang rasional dan tidak acak-acakan (misal: "Tepung Terigu", "Roti Coklat", harga yang wajar). 

### Fase 1: Setup Produk & Bahan Baku (Role: Admin / Gudang)
- **Login** sebagai user dengan role `admin` atau `gudang`.
- **Buat Data Satuan**: Pastikan satuan relevan ada seperti `kg`, `gram`, `pcs`, `liter`.
- **Buat Data Bahan Baku**: 
  - Tepung Terigu Segitiga Biru (Satuan: kg, Harga Beli: Rp. 13.000)
  - Margarin Blueband (Satuan: kg, Harga Beli: Rp. 25.000)
  - Gula Pasir (Satuan: kg, Harga Beli: Rp. 17.000)
  - Ragi Instan (Satuan: gram, Harga Beli: Rp. 500)
- **Buat Data Produk Jadi**:
  - Roti Manis Coklat (Satuan: pcs, Harga Jual: Rp. 5.000)
  - Roti Tawar (Satuan: pcs, Harga Jual: Rp. 15.000)
- **Input Stok Awal/Pembelian**: Lakukan pembelian bahan baku di atas agar stok bertambah (misal beli 10kg tepung, dll).

### Fase 2: Pembuatan Resep (Bill of Materials) (Role: Admin / Produksi)
- **Login** sebagai user dengan role `admin` atau `produksi` (jika berbeda sesi).
- **Buat BOM (Resep) untuk Roti Manis Coklat**:
  - Target: 50 pcs Roti Manis Coklat
  - Bahan dibutuhkan:
    - 2 kg Tepung Terigu
    - 0.5 kg Margarin
    - 0.5 kg Gula Pasir
    - 50 gram Ragi Instan
- Pastikan estimasi Harga Pokok Produksi (HPP) per pcs terkalkulasi dengan benar.

### Fase 3: Proses Produksi (Role: Produksi)
- **Login** sebagai `produksi`.
- **Mulai Produksi**: Buat catatan produksi baru menggunakan BOM "Roti Manis Coklat".
- Masukkan kuantitas yang diproduksi (misalnya 1 batch BOM = 50 pcs).
- Selesaikan status produksi menjadi "Selesai".
- Verifikasi bahwa stok bahan baku terpotong dan stok produk Roti Manis Coklat bertambah menjadi 50 pcs.

### Fase 4: Penjualan via POS (Role: Kasir)
- **Login** sebagai `kasir`.
- Buka menu POS.
- Input pesanan pelanggan (misalnya pelanggan membeli 5 pcs Roti Manis Coklat).
- Terima pembayaran dengan jumlah uang tunai yang wajar (misalnya bayar Rp. 50.000 untuk tagihan Rp. 25.000).
- Cetak struk/nota dan selesaikan transaksi.
- Verifikasi bahwa stok Roti Manis Coklat berkurang menjadi 45 pcs.

### Fase 5: Reporting & Cek Keuangan (Role: Admin / Superadmin)
- **Login** sebagai `admin` atau `superadmin`.
- Buka laporan penjualan atau dashboard statistik.
- Pastikan transaksi kasir tadi masuk ke dalam total omset harian.

## Cara Agent Menjalankan Workflow Ini
Agent dapat memilih salah satu pendekatan:
1. **Pendekatan Browser (Direkomendasikan):** Menggunakan tool `browser_subagent` untuk mengontrol browser secara nyata (login ke UI, mengisi form, klik tombol) agar memastikan fungsi frontend juga berjalan tanpa kendala. 
2. **Pendekatan API/Artisan:** Jika UI belum sepenuhnya siap, Agent bisa membuat script internal (di Tinker) untuk mensimulasikan input data tersebut selayaknya input manual oleh user.

*Note untuk AI: Jika user memanggil `/warung-tester-roti`, tanyakan terlebih dahulu ke user pendekatan mana yang ingin dipakai dan minta konfirmasi URL aktif serta akses login default untuk masing-masing role sebelum memulai subagent.*
