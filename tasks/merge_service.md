# Implementation Plan - Merge Service Order List & Board

Menggabungkan halaman Riwayat Servis (Table) dan Pipeline Order (Kanban) menjadi satu halaman master yang kohesif di `/service-orders`.

## User Review Required

> [!IMPORTANT]
> - Route `/service-orders/board` akan di-redirect secara permanen (301) ke `/service-orders`.
> - Seluruh view (Table & Kanban) akan menggunakan paginasi data dari server.
> - Fitur Pencarian bersifat Server-side dan tetap aktif saat berpindah antar tab view.
> - Fitur Price Adjustment tersedia baik di Kanban Board maupun di Table List.

## Proposed Changes

### 1. Backend: ServiceOrderController
#### [MODIFY] [ServiceOrderController.php](file:///Volumes/ssd_faruq/Work_Projects/Project/Web/Personal/warung/app/Http/Controllers/ServiceOrderController.php)
- Satukan logika `index` dan `board`.
- Tambahkan parameter `view` (default: `kanban`).
- Seluruh data order menggunakan paginasi (`paginate()`).
- Pastikan filter `search`, `status`, dan `date` diterapkan di kedua mode view.

### 2. Frontend: Unified Interface
#### [MODIFY] [Index.vue](file:///Volumes/ssd_faruq/Work_Projects/Project/Web/Personal/warung/resources/js/pages/service-orders/Index.vue)
- Implementasikan `Tabs` simple (List vs Board) di dalam area `PageHeader`.
- Pindahkan seluruh logika Kanban dari `Board.vue` (Draggable, Step Management) ke dalam `Index.vue` atau sub-komponen.
- Implementasikan Modal Price Adjustment yang bisa dipicu dari baris tabel maupun kartu Kanban.
- Pastikan `search` state tersinkronisasi di URL agar tetap ada saat pindah tab.

#### [DELETE] [Board.vue](file:///Volumes/ssd_faruq/Work_Projects/Project/Web/Personal/warung/resources/js/pages/service-orders/Board.vue)
- File ini akan dihapus setelah logikanya berhasil dimigrasi ke `Index.vue`.

### 3. Navigation & Branding
#### [MODIFY] [ServiceMenuSeeder.php](file:///Volumes/ssd_faruq/Work_Projects/Project/Web/Personal/warung/database/seeders/ServiceMenuSeeder.php)
- Hapus entri menu `service-orders.board`.
- Ubah nama menu `service-orders.index` menjadi **"Manajemen Order"**.
- Update ikon menu menggunakan `LayoutGrid` atau `ClipboardList`.

#### [MODIFY] [web.php](file:///Volumes/ssd_faruq/Work_Projects/Project/Web/Personal/warung/routes/web.php)
- Tambahkan `Route::redirect('/service-orders/board', '/service-orders', 301);`
- Update route `/service-orders` untuk menerima parameter optional.

## Verification Plan

### Automated Tests
- Jalankan test terkait ServiceOrder untuk memastikan tidak ada fitur yang pecah setelah penggabungan route.
- `php artisan test --filter=ServiceOrder`

### Manual Verification
1. Buka `/service-orders`, pastikan Kanban Board muncul sebagai default.
2. Klik Tab "Table View", pastikan data tabel muncul dengan filter yang berfungsi.
3. Coba fitur drag-and-drop di Kanban View.
4. Cek Sidebar, pastikan hanya ada satu menu untuk Service Orders.