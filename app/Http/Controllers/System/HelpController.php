<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class HelpController extends Controller
{
    public function index()
    {
        return Inertia::render('System/Help');
    }

    public function show($slug)
    {
        $articles = [
            'batch-expiry-activation' => [
                'title' => 'Cara Aktivasi Pelacakan Batch & Expiry',
                'category' => 'Manajemen Stok',
                'content' => "Untuk mulai melacak masa kadaluarsa barang, Anda harus mengaktifkannya per produk:\n\n1. Pergi ke menu **Produk**.\n2. Pilih produk yang ingin dilacak, lalu klik **Edit**.\n3. Centang opsi **'Pantau Stok Barang'**.\n4. Setelah muncul opsi tambahan, centang **'Lacak Batch & Expiry'**.\n5. Simpan perubahan.\n\nSetelah aktif, setiap kali Anda melakukan Restock atau Pembelian untuk barang tersebut, sistem akan meminta input Nomor Batch dan Tanggal Kadaluarsa.",
                'icon' => 'Package',
            ],
            'fefo-logic' => [
                'title' => 'Memahami Logika FEFO',
                'category' => 'Manajemen Stok',
                'content' => "Warung ERP menggunakan metode **FEFO (First Expired First Out)** untuk pengeluaran barang:\n\n*   Sistem akan selalu mengambil stok dari batch yang memiliki **tanggal kadaluarsa terdekat** terlebih dahulu.\n*   Jika ada batch yang sudah expired, sistem akan memberikan peringatan dan tidak memprioritaskannya untuk dijual.\n*   Metode ini membantu Anda mengurangi kerugian akibat barang kadaluarsa (shrinkage).",
                'icon' => 'Zap',
            ],
            'stock-opname-guide' => [
                'title' => 'Panduan Stock Opname Berkala',
                'category' => 'Manajemen Stok',
                'content' => "Stock Opname dilakukan untuk mencocokkan stok fisik dengan data sistem:\n\n1. Buka menu **Stok > Stock Opname**.\n2. Klik **Tambah Opname**.\n3. Pilih Gudang yang akan dihitung.\n4. Masukkan jumlah fisik untuk setiap item yang ditemukan.\n5. Sistem akan menghitung selisih secara otomatis.\n6. Klik **Selesaikan** untuk melakukan penyesuaian stok otomatis (Adjustment).",
                'icon' => 'Package',
            ],
            'stock-transfer' => [
                'title' => 'Melakukan Stock Transfer',
                'category' => 'Manajemen Stok',
                'content' => "Gunakan fitur ini untuk memindahkan barang antar gudang:\n\n1. Masuk ke **Stok > Stock Transfer**.\n2. Pilih gudang asal dan gudang tujuan.\n3. Pilih barang dan tentukan jumlah yang dipindahkan.\n4. Klik **Kirim**.\n5. Barang di gudang asal akan berkurang, dan barang di gudang tujuan akan bertambah setelah status diterima.",
                'icon' => 'Package',
            ],
            'pos-transaction' => [
                'title' => 'Melakukan Transaksi POS',
                'category' => 'Penjualan & POS',
                'content' => "Langkah-langkah transaksi kasir:\n\n1. Buka menu **Point of Sale**.\n2. Scan barcode produk atau cari nama produk di kolom pencarian.\n3. Masukkan jumlah barang.\n4. Klik **Bayar**.\n5. Pilih metode pembayaran dan masukkan jumlah uang yang diterima.\n6. Klik **Selesaikan Transaksi** untuk mencetak struk.",
                'icon' => 'ShoppingCart',
            ],
            'void-sale' => [
                'title' => 'Membatalkan (Void) Transaksi',
                'category' => 'Penjualan & POS',
                'content' => "Jika terjadi kesalahan input setelah bayar, Anda bisa melakukan Void:\n\n1. Masuk ke menu **Penjualan**.\n2. Cari nota yang ingin dibatalkan.\n3. Klik tombol **Void** (hanya tersedia untuk user dengan izin khusus).\n4. Masukkan alasan pembatalan.\n5. Stok barang akan otomatis dikembalikan ke saldo gudang asal.",
                'icon' => 'ShoppingCart',
            ],
            'credit-note' => [
                'title' => 'Menerbitkan Credit Note (Retur)',
                'category' => 'Penjualan & POS',
                'content' => "Credit Note digunakan jika customer mengembalikan sebagian barang:\n\n1. Masuk ke **Penjualan > Credit Note**.\n2. Masukkan nomor nota asli.\n3. Pilih barang yang dikembalikan dan jumlahnya.\n4. Pilih apakah barang masuk ke stok layak atau ke karantina (rusak).\n5. Nilai pengembalian akan tercatat sebagai deposit atau pengurang piutang.",
                'icon' => 'ShoppingCart',
            ],
            'bom-creation' => [
                'title' => 'Cara Membuat Resep (BOM)',
                'category' => 'Produksi & Resep',
                'content' => "BOM (Bill of Materials) adalah resep untuk produk jadi:\n\n1. Masuk ke menu **Produksi > BOM**.\n2. Klik **Tambah BOM Baru**.\n3. Pilih produk jadi (Misal: Roti Tawar).\n4. Masukkan daftar bahan baku dan kuantitas yang dibutuhkan (Misal: Tepung 0.5kg, Gula 0.1kg).\n5. Tentukan biaya overhead jika ada.\n6. Klik Simpan.\n\nResep ini akan digunakan setiap kali Anda melakukan proses Produksi.",
                'icon' => 'Zap',
            ],
            'production-flow' => [
                'title' => 'Memulai dan Memantau Produksi',
                'category' => 'Produksi & Resep',
                'content' => "Alur kerja produksi barang jadi:\n\n1. Buka menu **Produksi**.\n2. Klik **Buat Produksi Baru**.\n3. Pilih produk yang akan dibuat (sistem akan mengambil resep dari BOM).\n4. Klik **Mulai** untuk memotong stok bahan baku secara otomatis.\n5. Setelah selesai, klik **Selesaikan** untuk menambah saldo stok barang jadi.",
                'icon' => 'Zap',
            ],
            'period-lock' => [
                'title' => 'Cara Mengunci Periode (Period Lock)',
                'category' => 'Akuntansi & Keuangan',
                'content' => "Period Lock digunakan untuk mencegah perubahan data pada tanggal yang sudah tutup buku:\n\n1. Masuk ke menu **Akuntansi > Periode**.\n2. Pilih rentang tanggal yang ingin dikunci.\n3. Aktifkan status **Lock**.\n4. Setelah dikunci, tidak ada user yang bisa menambah, mengedit, atau menghapus transaksi pada tanggal tersebut.",
                'icon' => 'BarChart3',
            ],
            'audit-log' => [
                'title' => 'Melacak Perubahan via Audit Log',
                'category' => 'Akuntansi & Keuangan',
                'content' => "Audit Log mencatat siapa, kapan, dan apa yang berubah di sistem:\n\n1. Pergi ke **System > Audit Log**.\n2. Gunakan filter untuk mencari perubahan pada tabel tertentu (misal: 'stocks' atau 'sales').\n3. Anda bisa melihat data lama (Old Values) dan data baru (New Values) untuk setiap perubahan.",
                'icon' => 'BarChart3',
            ],
            'auto-journal' => [
                'title' => 'Memahami Jurnal Transaksi Otomatis',
                'category' => 'Akuntansi & Keuangan',
                'content' => "Setiap transaksi di Warung ERP menghasilkan jurnal otomatis:\n\n*   **POS**: Mendebit Kas dan Mengkredit Penjualan & Persediaan.\n*   **Restock**: Mendebit Persediaan dan Mengkredit Kas/Hutang.\n*   **Produksi**: Memindahkan nilai dari Persediaan Bahan Baku ke Persediaan Barang Jadi.\n\nSemua ini bisa dilihat di menu **Akuntansi > Jurnal**.",
                'icon' => 'BarChart3',
            ],
            'margin-report' => [
                'title' => 'Melihat Laporan Margin Harian',
                'category' => 'Penjualan & POS',
                'content' => "Laporan margin membantu Anda memantau keuntungan kotor per hari:\n\n1. Masuk ke menu **Penjualan > Laporan**.\n2. Pilih tanggal yang ingin dilihat.\n3. Sistem akan menjumlahkan (Harga Jual - Harga Modal) dari semua item yang terjual.\n4. Anda bisa melihat produk mana yang memberikan kontribusi keuntungan terbesar.",
                'icon' => 'ShoppingCart',
            ],
            'overhead-calc' => [
                'title' => 'Menghitung Biaya Overhead Produksi',
                'category' => 'Produksi & Resep',
                'content' => "Biaya overhead adalah biaya tambahan selain bahan baku (misal: listrik, tenaga kerja):\n\n1. Saat membuat **BOM**, Anda bisa memasukkan nilai overhead dalam rupiah atau persentase.\n2. Saat proses **Produksi** selesai, sistem akan menambahkan biaya ini ke nilai 'Harga Modal' produk jadi.\n3. Ini memastikan harga jual Anda sudah menutup semua biaya operasional.",
                'icon' => 'Zap',
            ],
            'unit-conversion' => [
                'title' => 'Konversi Satuan Bahan ke Produk',
                'category' => 'Produksi & Resep',
                'content' => "Penting untuk mencatat stok dalam satuan yang benar:\n\n*   Jika Anda membeli Tepung dalam **Karung (25kg)** tapi menggunakan **Gram** di resep, pastikan sudah mengatur konversi di menu **Unit**.\n*   Sistem akan otomatis menghitung pemakaian bahan baku sesuai satuan yang ada di BOM.",
                'icon' => 'Zap',
            ],
            'financial-reports' => [
                'title' => 'Memahami Trial Balance & Profit Loss',
                'category' => 'Akuntansi & Keuangan',
                'content' => "Laporan keuangan utama di Warung ERP:\n\n*   **Trial Balance**: Neraca Saldo untuk memastikan Debit dan Kredit seimbang.\n*   **Profit & Loss**: Laporan Laba Rugi untuk melihat performa bisnis Anda dalam periode tertentu.\n\nSemua data ini ditarik secara real-time dari setiap transaksi yang divalidasi.",
                'icon' => 'BarChart3',
            ],
            'fixed-asset-registration' => [
                'title' => 'Cara Registrasi Aset Tetap Baru',
                'category' => 'Manajemen Aset Tetap',
                'content' => "Untuk mendaftarkan aset baru (seperti motor, mesin, atau laptop):\n\n1. Masuk ke menu **Fixed Assets**.\n2. Klik **Tambah Aset**.\n3. Isi Nama Aset, Kategori, dan Tanggal Perolehan.\n4. Masukkan **Harga Perolehan** (harga beli asli).\n5. Tentukan **Masa Manfaat** (lama aset akan disusutkan).\n6. Pilih Akun Akuntansi (Akun Aset, Akumulasi, dan Beban).\n7. Klik **Register Asset**.\n\nSistem akan otomatis menghitung jadwal penyusutan bulan-ke-bulan.",
                'icon' => 'Building2',
            ],
            'depreciation-rates' => [
                'title' => 'Memahami Tarif & Metode Penyusutan',
                'category' => 'Manajemen Aset Tetap',
                'content' => "Sistem menggunakan metode **Straight-Line (Garis Lurus)**:\n\n*   Anda bisa memilih **Tarif Persentase** (misal: 25% per tahun) atau menentukan **Jumlah Bulan** secara manual.\n*   Penyusutan dihitung secara merata setiap bulan selama masa manfaat aset.\n*   Contoh: Aset seharga Rp 12jt dengan masa manfaat 12 bulan akan menyusut Rp 1jt setiap bulannya.",
                'icon' => 'Building2',
            ],
            'monthly-depreciation-posting' => [
                'title' => 'Posting Beban Penyusutan Bulanan',
                'category' => 'Manajemen Aset Tetap',
                'content' => "Anda tidak perlu menghitung penyusutan secara manual setiap bulan:\n\n1. Sistem memiliki **Automated Job** yang berjalan setiap akhir bulan.\n2. Job ini akan mengecek semua aset aktif dan memposting jurnal beban penyusutan otomatis.\n3. Jurnal akan mendebit **Beban Penyusutan** dan mengkredit **Akumulasi Penyusutan**.\n4. Anda bisa melihat riwayatnya di tab **Penyusutan** pada detail aset.",
                'icon' => 'Building2',
            ],
            'salvage-value-guide' => [
                'title' => 'Manajemen Nilai Residu (Salvage Value)',
                'category' => 'Manajemen Aset Tetap',
                'content' => "**Nilai Residu** adalah estimasi nilai sisa aset di akhir masa manfaatnya:\n\n*   Jika Anda memperkirakan sebuah mobil masih bisa dijual seharga Rp 50jt setelah 10 tahun, maka input Rp 50jt sebagai Nilai Residu.\n*   Sistem tidak akan menyusutkan aset di bawah angka Nilai Residu ini.\n*   Jika aset dianggap tidak bernilai setelah masa pakainya habis, isi Nilai Residu dengan **0**.",
                'icon' => 'Building2',
            ],
        ];

        $article = $articles[$slug] ?? null;

        if (! $article) {
            return redirect()->route('help')->with('error', 'Artikel tidak ditemukan.');
        }

        return Inertia::render('System/HelpDetail', [
            'article' => $article,
            'slug' => $slug,
        ]);
    }
}
