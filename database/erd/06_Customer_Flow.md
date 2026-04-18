# Flow 06: Customer & Member Management

Modul ini menangani pengelolaan data pelanggan, klasifikasi tipe/status, integrasi login customer, serta scaffolding sistem nasabah (kredit).

```mermaid
erDiagram
    USERS ||--o| CUSTOMERS : "can_be"
    CUSTOMER_TYPES ||--o{ CUSTOMERS : "classified_as"
    CUSTOMER_STATUSES ||--o{ CUSTOMERS : "currently_is"
    CUSTOMERS ||--o| NASABAH : "becomes"
    NASABAH_STATUSES ||--o{ NASABAH : "has_status"
    
    SALES ||--o{ SALE_CUSTOMERS : "associated_with"
    CUSTOMERS ||--o{ SALE_CUSTOMERS : "attributed_to"

    CUSTOMERS {
        bigint id PK
        bigint user_id FK "Nullable - Link to Auth"
        bigint customer_type_id FK
        bigint customer_status_id FK
        string name
        string phone
        string email
        text address
        timestamps created_at
    }

    CUSTOMER_TYPES {
        bigint id PK
        string name "Regular, Wholesaler, Drop-shipper"
        decimal default_discount "Optional"
    }

    CUSTOMER_STATUSES {
        bigint id PK
        string name "Active, Suspended, Blacklisted"
    }

    NASABAH {
        bigint id PK
        bigint customer_id FK "Unique - 1:1 to Customers"
        bigint nasabah_status_id FK
        decimal credit_limit
        timestamps created_at
    }

    NASABAH_STATUSES {
        bigint id PK
        string name "Active, Locked, Closed"
    }

    SALE_CUSTOMERS {
        bigint id PK
        bigint sale_id FK
        bigint customer_id FK
    }
```

## Relasi & Arsitektur
1. **Login System**: Tabel `CUSTOMERS` bersifat opsional terhadap `USERS`. Jika user_id diisi, maka orang tersebut bisa login sebagai role `customer`.
2. **Dynamic Meta**: `CUSTOMER_TYPES` dan `CUSTOMER_STATUSES` dipisah agar admin bisa menambah kategori baru tanpa mengubah struktur tabel/coding.
3. **Decoupled Sales**: Tabel `SALES` (Flow 04) tidak dimodifikasi. Sebagai gantinya, `SALE_CUSTOMERS` menjadi tabel penghubung (junction) untuk merekam siapa pembeli di suatu invoice.
4. **Nasabah Subset**: `NASABAH` adalah ekstensi dari `CUSTOMERS`. Seorang Nasabah wajib memiliki record induk di `CUSTOMERS`.
