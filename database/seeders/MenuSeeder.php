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
        // Platform (Slug: platform)
        $this->seedMenu([
            'name' => 'Dashboard',
            'path' => '/dashboard',
            'icon' => 'LayoutGrid',
            'permission_name' => 'view dashboard',
            'module_slug' => 'platform',
            'order_priority' => 10,
        ]);

        $this->seedMenu([
            'name' => 'Penjualan (POS)',
            'path' => '/pos',
            'icon' => 'ShoppingCart',
            'permission_name' => 'make sales',
            'module_slug' => 'platform',
            'order_priority' => 20,
        ]);

        // Inventory (Slug: inventory)
        $this->seedMenu([
            'name' => 'Produk (Barang)',
            'path' => '/produk',
            'icon' => 'Package',
            'permission_name' => 'manage products',
            'module_slug' => 'inventory',
            'order_priority' => 110,
        ]);

        $this->seedMenu([
            'name' => 'Stok Inventori',
            'path' => '/stock',
            'icon' => 'Boxes',
            'permission_name' => 'manage stock',
            'module_slug' => 'inventory',
            'order_priority' => 120,
        ]);

        $this->seedMenu([
            'name' => 'Stock Opname',
            'path' => '/stock-opname',
            'icon' => 'ClipboardList',
            'permission_name' => 'manage stock',
            'module_slug' => 'inventory',
            'order_priority' => 130,
        ]);

        $this->seedMenu([
            'name' => 'BOM (Resep)',
            'path' => '/bom',
            'icon' => 'FileText',
            'permission_name' => 'manage products',
            'module_slug' => 'inventory',
            'order_priority' => 140,
        ]);

        // Production (Slug: production)
        $this->seedMenu([
            'name' => 'Produksi',
            'path' => '/production',
            'icon' => 'PackageOpen',
            'permission_name' => 'manage products',
            'module_slug' => 'production',
            'order_priority' => 210,
        ]);

        // Purchasing (Slug: purchasing)
        $this->seedMenu([
            'name' => 'Purchasing Inbound',
            'path' => '/purchasing',
            'icon' => 'Store',
            'permission_name' => 'manage stock',
            'module_slug' => 'purchasing',
            'order_priority' => 310,
        ]);

        $this->seedMenu([
            'name' => 'Master Vendor',
            'path' => '/vendors',
            'icon' => 'Building2',
            'permission_name' => 'manage vendors',
            'module_slug' => 'purchasing',
            'order_priority' => 320,
        ]);

        // Finance (Slug: finance)
        $this->seedMenu([
            'name' => 'Chart of Accounts',
            'path' => '/accounting/accounts',
            'icon' => 'Landmark',
            'permission_name' => 'view reports',
            'module_slug' => 'finance',
            'order_priority' => 410,
        ]);

        $this->seedMenu([
            'name' => 'Buku Jurnal',
            'path' => '/accounting/journal',
            'icon' => 'FileText',
            'permission_name' => 'view reports',
            'module_slug' => 'finance',
            'order_priority' => 420,
        ]);

        $this->seedMenu([
            'name' => 'Trial Balance',
            'path' => '/accounting/trial-balance',
            'icon' => 'PieChart',
            'permission_name' => 'view reports',
            'module_slug' => 'finance',
            'order_priority' => 430,
        ]);

        $this->seedMenu([
            'name' => 'Aging Report',
            'path' => '/accounting/aging',
            'icon' => 'HistoryIcon',
            'permission_name' => 'view reports',
            'module_slug' => 'finance',
            'order_priority' => 440,
        ]);

        $this->seedMenu([
            'name' => 'Biaya Operasional',
            'path' => '/pengeluaran',
            'icon' => 'ReceiptText',
            'permission_name' => 'view reports',
            'module_slug' => 'finance',
            'order_priority' => 450,
        ]);

        // CRM (Slug: crm)
        $this->seedMenu([
            'name' => 'Master Customer',
            'path' => '/customers',
            'icon' => 'Users',
            'permission_name' => 'manage customers',
            'module_slug' => 'crm',
            'order_priority' => 510,
        ]);

        // Settings (Slug: settings)
        $this->seedMenu([
            'name' => 'Satuan Barang',
            'path' => '/satuan',
            'icon' => 'Ruler',
            'permission_name' => 'manage products',
            'module_slug' => 'settings',
            'order_priority' => 910,
        ]);

        $this->seedMenu([
            'name' => 'Manajemen User',
            'path' => '/settings/users',
            'icon' => 'Users',
            'permission_name' => 'manage users',
            'module_slug' => 'settings',
            'order_priority' => 920,
        ]);

        $this->seedMenu([
            'name' => 'Manajemen Role',
            'path' => '/settings/roles',
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
                'icon' => $data['icon'],
                'permission_name' => $data['permission_name'],
                'module_id' => $module?->id,
                'order_priority' => $data['order_priority'],
                'is_active' => true,
            ]
        );
    }
}
