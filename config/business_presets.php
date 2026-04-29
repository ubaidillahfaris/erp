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
                'sales.index' => 'Riwayat Order',
            ],
            'crm' => [
                'customer.index' => 'Daftar Pelanggan',
            ],
            'finance' => ['pengeluaran.index'], // Biaya Operasional only
            'settings' => ['users.index'],
        ],
        'features' => [
            'weight_based_billing' => true,
            'item_based_billing' => true,
            'status_tracking' => true,
        ],
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
        'default_route' => 'product.index',
    ],

    'warkop' => [
        'name' => 'Warkop / Cafe',
        'modules' => [
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
        'default_route' => 'pos.index',
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
        'default_route' => 'dashboard',
    ],
];
