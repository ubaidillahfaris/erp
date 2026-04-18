# Flow 02: Master Data & Inventory Core

Pondasi barang, satuan, dan bagaimana stok bergerak.

```mermaid
erDiagram
    PRODUKS ||--o{ SATUANS : "has_default_unit"
    PRODUKS ||--o{ PRICES : "price_history"
    PRODUKS ||--o{ STOCKS : "current_balance"
    PRODUKS ||--o{ STOCK_MOVEMENTS : "audit_trail"
    SATUANS ||--o{ SATUAN_CONVERSIONS : "from_unit"
    SATUANS ||--o{ SATUAN_CONVERSIONS : "to_unit"
    PRODUKS ||--o{ SATUAN_CONVERSIONS : "specific_conversion"
    
    STOCK_OPNAMES ||--o{ STOCK_OPNAME_ITEMS : "details"
    PRODUKS ||--o{ STOCK_OPNAME_ITEMS : "counted_item"

    PRODUKS {
        bigint id PK
        string sku UK
        string type "raw/finished"
        boolean track_stock
    }
    STOCKS {
        bigint id PK
        bigint produk_id FK
        decimal balance
    }
    STOCK_MOVEMENTS {
        bigint id PK
        bigint produk_id FK
        string type "in/out"
        string reference_type "purchase/sale/production"
    }
```
