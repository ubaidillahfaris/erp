<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds for all companies or a specific one.
     */
    public function run(?int $companyId = null): void
    {
        $companies = $companyId 
            ? Company::where('id', $companyId)->get() 
            : Company::all();

        foreach ($companies as $company) {
            $this->seedForCompany($company->id);
        }
    }

    /**
     * Seed default accounts for a specific company.
     */
    public function seedForCompany(int $companyId): void
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
                'name' => 'Persediaan Raw Materials',
                'type' => 'asset',
                'balance_type' => 'debit',
            ],
            [
                'code' => '1302',
                'name' => 'Persediaan Finished Goods',
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
            [
                'code' => '6101',
                'name' => 'Beban Listrik, Air & Gas',
                'type' => 'expense',
                'balance_type' => 'debit',
            ],
            [
                'code' => '6102',
                'name' => 'Beban Gaji & Upah',
                'type' => 'expense',
                'balance_type' => 'debit',
            ],
            [
                'code' => '6103',
                'name' => 'Beban Sewa & Keamanan',
                'type' => 'expense',
                'balance_type' => 'debit',
            ],
            [
                'code' => '6104',
                'name' => 'Beban Pemeliharaan & Alat',
                'type' => 'expense',
                'balance_type' => 'debit',
            ],
            [
                'code' => '4102',
                'name' => 'Pendapatan Lain-lain',
                'type' => 'income',
                'balance_type' => 'credit',
            ],
            [
                'code' => '6201',
                'name' => 'Kerugian Selisih Stok',
                'type' => 'expense',
                'balance_type' => 'debit',
            ],
            [
                'code' => '1401',
                'name' => 'Peralatan & Mesin',
                'type' => 'asset',
                'balance_type' => 'debit',
            ],
            [
                'code' => '1499',
                'name' => 'Akumulasi Penyusutan',
                'type' => 'asset',
                'balance_type' => 'credit',
            ],
            [
                'code' => '6301',
                'name' => 'Beban Penyusutan',
                'type' => 'expense',
                'balance_type' => 'debit',
            ],
            [
                'code' => '4201',
                'name' => 'Pendapatan Jasa',
                'type' => 'income',
                'balance_type' => 'credit',
            ],
            [
                'code' => '6401',
                'name' => 'Beban Jasa',
                'type' => 'expense',
                'balance_type' => 'debit',
            ],
        ];

        foreach ($accounts as $account) {
            Account::updateOrCreate(
                [
                    'code' => $account['code'],
                    'company_id' => $companyId
                ], 
                array_merge($account, ['company_id' => $companyId])
            );
        }
    }
}

