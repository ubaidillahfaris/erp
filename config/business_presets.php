<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Business Type Presets
    |--------------------------------------------------------------------------
    |
    | This file defines the default modules and configuration for each business
    | type. This is used during the onboarding process to tailor the user
    | experience based on their industry.
    |
    | Module values:
    |   '*'   → show all menus in that module
    |   array → only show menus whose route_name matches the given list
    |
    */

    'laundry' => [
        'name' => 'Laundry & Dry Cleaning',
        'modules' => [
            'platform' => '*',                   // Dashboard
            'transaksi' => [
                'service-orders.create' => 'Order Laundry',
                'service-orders.board' => 'Board Order',
                'service-orders.index' => 'Daftar Order',
                'sales.index' => 'Riwayat Penjualan',
            ],
            'crm' => [
                'customer.index' => 'Daftar Pelanggan',
            ],
            'finance' => ['pengeluaran.index'], // Biaya Operasional only
            'settings' => [
                'users.index',
                'settings.services.index' => 'Katalog Jasa',
            ],
        ],
        'features' => [
            'weight_based_billing' => true,
            'item_based_billing' => true,
            'status_tracking' => true,
        ],
        'service_categories' => ['Laundry Kiloan', 'Laundry Satuan', 'Dry Cleaning', 'Setrika Saja'],
        'default_route' => 'service-orders.create',
    ],

    'event_organizer' => [
        'name' => 'Event Organizer',
        'modules' => [
            'platform' => '*',                   // Dashboard
            'transaksi' => [
                'service-orders.create' => 'Order Event',
                'service-orders.board' => 'Board Event',
                'service-orders.index' => 'Daftar Event',
                'sales.index' => 'Riwayat Penjualan',
            ],
            'crm' => [
                'customer.index' => 'Daftar Pelanggan',
            ],
            'finance' => ['pengeluaran.index'], // Biaya Operasional only
            'settings' => [
                'users.index',
                'settings.services.index' => 'Katalog Jasa',
            ],
        ],
        'features' => [
            'weight_based_billing' => true,
            'item_based_billing' => true,
            'status_tracking' => true,
        ],
        'service_categories' => ['Event Kiloan', 'Event Satuan', 'Dry Cleaning', 'Setrika Saja'],
        'default_route' => 'service-orders.create',
    ],

    'retail' => [
        'name' => 'Retail / Toko Kelontong',
        'modules' => [
            'platform' => '*',  // Dashboard
            'inventory' => '*',  // Stock is Priority
            'transaksi' => '*',  // POS + Sales
            'purchasing' => '*',  // Restocking
            'finance' => '*',
            'crm' => '*',
            'report' => '*',
            'settings' => '*',
        ],
        'features' => [
            'barcode_scanning' => true,
            'expiry_tracking' => true,
        ],
        'service_categories' => ['Jasa Titip', 'Jasa Antar', 'Lainnya'],
        'default_route' => 'product.index',
    ],

    'warkop' => [
        'name' => 'Warkop / Cafe',
        'modules' => [
            'platform' => '*',  // Dashboard
            'transaksi' => '*',  // POS / Ordering
            'inventory' => '*',  // Ingredients
            'production' => '*',  // BOM / Recipe
            'finance' => '*',
            'report' => '*',
            'settings' => '*',
        ],
        'features' => [
            'table_management' => true,
            'kitchen_display' => true,
        ],
        'service_categories' => ['Sewa Ruangan', 'Jasa Antar', 'Lainnya'],
        'default_route' => 'pos.index',
    ],

    'service' => [
        'name' => 'Jasa / Service Terminal',
        'modules' => [
            'platform' => '*',
            'transaksi' => [
                'service-orders.create' => 'Service Terminal',
                'service-orders.board' => 'Pipeline',
                'service-orders.index' => 'History Order',
                'sales.index' => 'History Barang',
            ],
            'inventory' => '*', // For spare parts
            'crm' => '*',
            'finance' => '*',
            'report' => '*',
            'settings' => [
                '*',
                'settings.services.index' => 'Katalog Jasa',
            ],
        ],
        'features' => [
            'status_tracking' => true,
            'staff_assignment' => true,
        ],
        'service_categories' => ['Perbaikan', 'Pengecekan', 'Instalasi', 'Maintenance'],
        'default_route' => 'service-orders.create',
    ],

    'bengkel' => [
        'name' => 'Bengkel / Workshop',
        'modules' => [
            'platform' => '*',
            'transaksi' => [
                'service-orders.create' => 'Bengkel POS',
                'service-orders.board' => 'Work Order',
                'service-orders.index' => 'Riwayat Servis',
                'sales.index' => 'Riwayat Part',
            ],
            'inventory' => '*', // Spare parts are crucial
            'purchasing' => '*',
            'finance' => '*',
            'report' => '*',
            'settings' => [
                '*',
                'settings.services.index' => 'Katalog Jasa',
            ],
        ],
        'features' => [
            'spare_part_integration' => true,
            'vehicle_tracking' => true,
        ],
        'service_categories' => ['Service Rutin', 'Ganti Oli', 'Perbaikan Mesin', 'Body Repair'],
        'default_route' => 'service-orders.create',
    ],

    'other' => [
        'name' => 'Lainnya',
        'modules' => [
            'platform' => '*',
            'inventory' => '*',
            'transaksi' => '*',
            'finance' => '*',
            'crm' => '*',
            'report' => '*',
            'settings' => '*',
        ],
        'features' => [],
        'service_categories' => ['Jasa Umum', 'Lainnya'],
        'default_route' => 'dashboard',
    ],
];
