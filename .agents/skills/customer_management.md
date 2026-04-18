---
name: customer-management
description: "Mengelola data pelanggan, tipe pelanggan, status, dan data nasabah untuk sistem kredit."
---

# Modul Customer Management

## Deskripsi
- **Model**: `App\Models\Customer`
- **Model Tipe**: `App\Models\CustomerType`
- **Model Status**: `App\Models\CustomerStatus`
- **Model Nasabah**: `App\Models\Nasabah`
- **Junction**: `App\Models\SaleCustomer`

## Informasi Customer
- Nama Lengkap & Nama Panggilan.
- No. HP / WhatsApp (Penting untuk notifikasi tagihan/nasabah).
- Alamat Pengiriman / Domisili.
- Tipe Customer (Regular, Wholesaler, dll).
- Status Customer (Active, Suspended, Blacklisted).

## Aturan Bisnis
- **Customer as User**: Jika customer diberikan akses login, buat record di tabel `users` dengan role `customer`.
- **Nasabah**: Record di tabel `nasabah` hanya bisa dibuat jika sudah terdaftar sebagai `customer`.
- **Relasi Sales**: Untuk menjaga keutuhan tabel `sales` yang sudah ada, gunakan tabel `sale_customers` untuk menghubungkan transaksi dengan pelanggan.
- **Credit Limit**: Validasi total piutang nasabah tidak boleh melebihi `credit_limit` saat melakukan transaksi (untuk pengembangan tahap selanjutnya).

## Pengetahuan AI
- **Laravel Scout**: Gunakan Scout untuk mencari data customer berdasarkan nama, HP, atau alamat.
- **Role Control**: Pastikan permissions `manage customers` hanya diberikan ke admin, manager, atau staf yang berwenang.
- **Status Notification**: Pantau status `Blacklisted` untuk mencegah pembuatan transaksi baru melalui sistem.
