<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Module;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 0. Cleanup duplicates or legacy entries
        Menu::whereIn('path', [
            '/customers',
            '/pos',
            '/produk',
            '/vendors',
            '/settings/users',
            '/settings/roles'
        ])->whereNull('route_name')->delete();

        // Platform (Slug: platform)
        $this->seedMenu([
            'name' => 'Dashboard',
            'path' => '/dashboard',
            'route_name' => 'dashboard',
            'icon' => 'LayoutGrid',
            'permission_name' => 'view dashboard',
            'module_slug' => 'platform',
            'order_priority' => 10,
        ]);

        $this->seedMenu([
            'name' => 'Penjualan (POS)',
            'path' => '/pos',
            'route_name' => 'pos.index',
            'icon' => 'ShoppingCart',
            'permission_name' => 'make sales',
            'module_slug' => 'platform',
            'order_priority' => 20,
        ]);

        // Inventory (Slug: inventory)
        $this->seedMenu([
            'name' => 'Produk (Barang)',
            'path' => '/produk',
            'route_name' => 'produk.index',
            'icon' => 'Package',
            'permission_name' => 'manage products',
            'module_slug' => 'inventory',
            'order_priority' => 110,
        ]);

        $this->seedMenu([
            'name' => 'Stok Inventori',
            'path' => '/stock',
            'route_name' => 'stock.index',
            'icon' => 'Boxes',
            'permission_name' => 'manage stock',
            'module_slug' => 'inventory',
            'order_priority' => 120,
        ]);

        $this->seedMenu([
            'name' => 'Stock Opname',
            'path' => '/stock-opname',
            'route_name' => 'stock-opname.index',
            'icon' => 'ClipboardList',
            'permission_name' => 'manage stock',
            'module_slug' => 'inventory',
            'order_priority' => 130,
        ]);

        $this->seedMenu([
            'name' => 'BOM (Resep)',
            'path' => '/bom',
            'route_name' => 'bom.index',
            'icon' => 'FileText',
            'permission_name' => 'manage products',
            'module_slug' => 'inventory',
            'order_priority' => 140,
        ]);

        // Production (Slug: production)
        $this->seedMenu([
            'name' => 'Produksi',
            'path' => '/production',
            'route_name' => 'production.index',
            'icon' => 'PackageOpen',
            'permission_name' => 'manage products',
            'module_slug' => 'production',
            'order_priority' => 210,
        ]);

        // Purchasing (Slug: purchasing)
        $this->seedMenu([
            'name' => 'Purchasing Inbound',
            'path' => '/purchasing',
            'route_name' => 'purchasing.index',
            'icon' => 'Store',
            'permission_name' => 'manage stock',
            'module_slug' => 'purchasing',
            'order_priority' => 310,
        ]);

        $this->seedMenu([
            'name' => 'Master Vendor',
            'path' => '/vendors',
            'route_name' => 'vendor.index',
            'icon' => 'Building2',
            'permission_name' => 'manage vendors',
            'module_slug' => 'purchasing',
            'order_priority' => 320,
        ]);

        // Finance (Slug: finance)
        $this->seedMenu([
            'name' => 'Chart of Accounts',
            'path' => '/accounting/accounts',
            'route_name' => 'accounting.accounts.index',
            'icon' => 'Landmark',
            'permission_name' => 'view reports',
            'module_slug' => 'finance',
            'order_priority' => 410,
        ]);

        $this->seedMenu([
            'name' => 'Buku Jurnal',
            'path' => '/accounting/journal',
            'route_name' => 'accounting.journal.index',
            'icon' => 'FileText',
            'permission_name' => 'view reports',
            'module_slug' => 'finance',
            'order_priority' => 420,
        ]);

        $this->seedMenu([
            'name' => 'Trial Balance',
            'path' => '/accounting/trial-balance',
            'route_name' => 'accounting.trial-balance.index',
            'icon' => 'PieChart',
            'permission_name' => 'view reports',
            'module_slug' => 'finance',
            'order_priority' => 430,
        ]);

        $this->seedMenu([
            'name' => 'Aging Report',
            'path' => '/accounting/aging',
            'route_name' => 'accounting.aging.index',
            'icon' => 'HistoryIcon',
            'permission_name' => 'view reports',
            'module_slug' => 'finance',
            'order_priority' => 440,
        ]);

        $this->seedMenu([
            'name' => 'Biaya Operasional',
            'path' => '/pengeluaran',
            'route_name' => 'pengeluaran.index',
            'icon' => 'ReceiptText',
            'permission_name' => 'view reports',
            'module_slug' => 'finance',
            'order_priority' => 450,
        ]);

        // CRM (Slug: crm)
        $this->seedMenu([
            'name' => 'Master Customer',
            'path' => '/customers',
            'route_name' => 'customer.index',
            'icon' => 'Users',
            'permission_name' => 'manage customers',
            'module_slug' => 'crm',
            'order_priority' => 510,
        ]);

        // HR (Slug: hr)
        $this->seedMenu([
            'name' => 'Manajemen Pegawai',
            'path' => '/employees',
            'route_name' => 'employees.index',
            'icon' => 'ContactRound',
            'permission_name' => 'manage employees',
            'module_slug' => 'hr',
            'order_priority' => 710,
        ]);

        // Settings (Slug: settings)
        $this->seedMenu([
            'name' => 'Satuan Barang',
            'path' => '/satuan',
            'route_name' => 'satuan.index',
            'icon' => 'Ruler',
            'permission_name' => 'manage products',
            'module_slug' => 'settings',
            'order_priority' => 910,
        ]);

        $this->seedMenu([
            'name' => 'Manajemen User',
            'path' => '/settings/users',
            'route_name' => 'users.index',
            'icon' => 'Users',
            'permission_name' => 'manage users',
            'module_slug' => 'settings',
            'order_priority' => 920,
        ]);

        $this->seedMenu([
            'name' => 'Manajemen Role',
            'path' => '/settings/roles',
            'route_name' => 'roles.index',
            'icon' => 'ShieldCheck',
            'permission_name' => 'manage roles',
            'module_slug' => 'settings',
            'order_priority' => 930,
        ]);

        // Deactivate Legacy Menus
        Menu::whereIn('path', ['/restock', '/journal', '/profit-loss'])->update(['is_active' => false]);
    }

    /**
     * Helper to seed menu with module mapping.
     */
    private function seedMenu(array $data): void
    {
        $module = Module::where('slug', $data['module_slug'])->first();

        Menu::updateOrCreate(
            ['path' => $data['path']],
            [
                'name' => $data['name'],
                'route_name' => $data['route_name'] ?? null,
                'icon' => $data['icon'],
                'permission_name' => $data['permission_name'],
                'module_id' => $module?->id,
                'order_priority' => $data['order_priority'],
                'is_active' => true,
            ]
        );
    }
}
