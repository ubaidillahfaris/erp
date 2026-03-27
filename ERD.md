# Warung App Entity Relationship Diagram (ERD)

Berikut adalah gambaran hubungan antar tabel/model untuk aplikasi warung ini (Simplified):

```mermaid
erDiagram
    PRODUK ||--o{ TRANSAKSI_DETAIL : "tercatat di"
    PRODUK {
        bigint id PK
        string sku UK
        string barcode
        string nama
        string kategori
        text deskripsi
        integer stok_minimal
        boolean is_active
        timestamps created_at
    }

    PRODUK ||--o{ HARGA : "memiliki history"
    HARGA {
        bigint id PK
        bigint produk_id FK
        decimal harga_beli
        decimal harga_jual
        decimal margin_persen
        timestamps created_at
    }

    PRODUK }o--|| SATUAN : "menggunakan"
    SATUAN {
        bigint id PK
        string nama_satuan
        decimal konversi_ke_terkecil
        timestamps created_at
    }

    PRODUK }o--|| VENDOR : "disuplai oleh"
    VENDOR {
        bigint id PK
        string nama_vendor
        string no_hp
        string alamat
        string jadwal_sales
        timestamps created_at
    }

    TRANSAKSI ||--o{ TRANSAKSI_DETAIL : "terdiri dari"
    TRANSAKSI {
        bigint id PK
        string no_nota UK
        decimal total_bayar
        string metode_pembayaran
        timestamps created_at
    }

    TRANSAKSI_DETAIL {
        bigint id PK
        bigint transaksi_id FK
        bigint produk_id FK
        integer jumlah
        decimal harga_satuan
        decimal subtotal
    }
```

## Penjelasan Singkat:
1.  **Item Master**: Induk dari segala barang (Data murni).
2.  **Produk**: Variasi spesifik dari Item Master (punya SKU & Barcode sendiri).
3.  **Harga**: Dicatat per produk agar bisa simpan history perubahan harga (lonjakan modal).
4.  **Satuan**: Untuk handle konversi (misal: jual per pcs tapi stok masuk per dus).
5.  **Vendor**: Menghubungkan produk dengan supplier-nya.
6.  **Transaksi & Detail**: Mencatat aktivitas kasir dan update stok otomatis via Observer.
