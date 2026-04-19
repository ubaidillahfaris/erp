<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'code' => '1101',
                'name' => 'Kas & Bank',
                'type' => 'asset',
                'balance_type' => 'debit',
            ],
            [
                'code' => '1102',
                'name' => 'Piutang Usaha',
                'type' => 'asset',
                'balance_type' => 'debit',
            ],
            [
                'code' => '1301',
                'name' => 'Persediaan Bahan Baku',
                'type' => 'asset',
                'balance_type' => 'debit',
            ],
            [
                'code' => '1302',
                'name' => 'Persediaan Barang Jadi',
                'type' => 'asset',
                'balance_type' => 'debit',
            ],
            [
                'code' => '2101',
                'name' => 'Hutang Usaha',
                'type' => 'liability',
                'balance_type' => 'credit',
            ],
            [
                'code' => '4101',
                'name' => 'Penjualan',
                'type' => 'income',
                'balance_type' => 'credit',
            ],
            [
                'code' => '5101',
                'name' => 'HPP / COGS',
                'type' => 'expense',
                'balance_type' => 'debit',
            ],
            [
                'code' => '5102',
                'name' => 'Biaya Overhead Manufaktur',
                'type' => 'expense',
                'balance_type' => 'debit',
            ],
        ];

        foreach ($accounts as $account) {
            Account::updateOrCreate(['code' => $account['code']], $account);
        }
    }
}
