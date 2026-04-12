<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate to prevent duplicates
        Menu::query()->delete();

        // Platform Group
        Menu::create([
            'name' => 'Dashboard',
            'route_name' => 'dashboard',
            'path' => '/dashboard',
            'icon' => 'LayoutGrid',
            'permission_name' => 'view dashboard',
            'group_name' => 'Platform',
            'order_priority' => 1,
        ]);

        Menu::create([
            'name' => 'Penjualan (POS)',
            'route_name' => 'pos.index',
            'path' => '/pos',
            'icon' => 'ShoppingCart',
            'permission_name' => 'make sales',
            'group_name' => 'Platform',
            'order_priority' => 2,
        ]);

        // Master Data Group
        Menu::create([
            'name' => 'Produk (Barang)',
            'route_name' => 'produk.index',
            'path' => '/produk',
            'icon' => 'Package',
            'permission_name' => 'manage products',
            'group_name' => 'Master Data',
            'order_priority' => 10,
        ]);

        Menu::create([
            'name' => 'Stok Inventori',
            'route_name' => 'stock.index',
            'path' => '/stock',
            'icon' => 'Boxes',
            'permission_name' => 'manage stock',
            'group_name' => 'Master Data',
            'order_priority' => 11,
        ]);

        Menu::create([
            'name' => 'Stock Opname',
            'route_name' => 'stock-opname.index',
            'path' => '/stock-opname',
            'icon' => 'ClipboardList',
            'permission_name' => 'manage stock',
            'group_name' => 'Master Data',
            'order_priority' => 12,
        ]);

        Menu::create([
            'name' => 'BOM (Resep)',
            'route_name' => 'bom.index',
            'path' => '/bom',
            'icon' => 'FileText',
            'permission_name' => 'manage products',
            'group_name' => 'Master Data',
            'order_priority' => 13,
        ]);

        Menu::create([
            'name' => 'Master Vendor',
            'route_name' => 'vendor.index',
            'path' => '/vendors',
            'icon' => 'Building2',
            'permission_name' => 'manage vendors',
            'group_name' => 'Master Data',
            'order_priority' => 14,
        ]);

        Menu::create([
            'name' => 'Produksi',
            'route_name' => 'production.index',
            'path' => '/production',
            'icon' => 'PackageOpen',
            'permission_name' => 'manage products',
            'group_name' => 'Master Data',
            'order_priority' => 15,
        ]);

        Menu::create([
            'name' => 'Satuan Barang',
            'route_name' => 'satuan.index',
            'path' => '/satuan',
            'icon' => 'Ruler',
            'permission_name' => 'manage products',
            'group_name' => 'Master Data',
            'order_priority' => 16,
        ]);

        // Transaksi Group
        Menu::create([
            'name' => 'Purchasing Inbound',
            'route_name' => 'purchasing.index',
            'path' => '/purchasing',
            'icon' => 'PackageOpen',
            'permission_name' => 'manage stock', // Asumsi menggunakan scope yg sama dengan restock
            'group_name' => 'Transaksi',
            'order_priority' => 19,
        ]);

        Menu::create([
            'name' => 'Restock (Legacy)',
            'route_name' => 'restock.index',
            'path' => '/restock',
            'icon' => 'ShoppingBag',
            'permission_name' => 'manage stock',
            'group_name' => 'Transaksi',
            'order_priority' => 20,
        ]);

        Menu::create([
            'name' => 'Jurnal Umum',
            'route_name' => 'journal.index',
            'path' => '/journal',
            'icon' => 'Landmark',
            'permission_name' => 'view reports',
            'group_name' => 'Transaksi',
            'order_priority' => 21,
        ]);

        Menu::create([
            'name' => 'Laba Rugi',
            'route_name' => 'profit-loss.index',
            'path' => '/profit-loss',
            'icon' => 'PieChart',
            'permission_name' => 'view reports',
            'group_name' => 'Transaksi',
            'order_priority' => 22,
        ]);

        Menu::create([
            'name' => 'Biaya Operasional',
            'route_name' => 'pengeluaran.index',
            'path' => '/pengeluaran',
            'icon' => 'ReceiptText',
            'permission_name' => 'view reports',
            'group_name' => 'Transaksi',
            'order_priority' => 23,
        ]);

        // Pengaturan Group
        Menu::create([
            'name' => 'Manajemen User',
            'route_name' => 'users.index',
            'path' => '/settings/users',
            'icon' => 'Users',
            'permission_name' => 'manage users',
            'group_name' => 'Pengaturan',
            'order_priority' => 30,
        ]);

        Menu::create([
            'name' => 'Manajemen Role',
            'route_name' => 'roles.index',
            'path' => '/settings/roles',
            'icon' => 'ShieldCheck',
            'permission_name' => 'manage roles',
            'group_name' => 'Pengaturan',
            'order_priority' => 31,
        ]);
    }
}
