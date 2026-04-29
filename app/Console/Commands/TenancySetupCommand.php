<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class TenancySetupCommand extends Command
{
    protected $signature = 'tenancy:setup';

    protected $description = 'Generate a migration to add company_id to all relevant tables';

    public function handle()
    {
        $tables = collect(Schema::getTables())->pluck('name')->toArray();
        $exclude = [
            'migrations', 'failed_jobs', 'password_reset_tokens', 'personal_access_tokens',
            'sessions', 'users', 'companies', 'roles', 'permissions', 'model_has_permissions',
            'model_has_roles', 'role_has_permissions', 'jobs', 'cache', 'cache_locks',
        ];

        $targetTables = [];
        foreach ($tables as $table) {
            if (in_array($table, $exclude)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            if (! in_array('company_id', $columns)) {
                $targetTables[] = $table;
            }
        }

        if (empty($targetTables)) {
            $this->info('All tables already have company_id or are excluded.');

            return;
        }

        $this->info('Target tables: '.implode(', ', $targetTables));

        $migrationName = 'add_company_id_to_all_tables_'.date('Y_m_d_His');
        $filePath = database_path('migrations/'.$migrationName.'.php');

        $up = '';
        $down = '';

        foreach ($targetTables as $table) {
            $up .= "        Schema::table('{$table}', function (Blueprint \$table) {\n";
            $up .= "            \$table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');\n";
            $up .= "        });\n\n";

            $down .= "        Schema::table('{$table}', function (Blueprint \$table) {\n";
            $down .= "            \$table->dropConstrainedForeignId('company_id');\n";
            $down .= "        });\n\n";
        }

        $template = "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
{$up}
    }

    public function down(): void
    {
{$down}
    }
};
";

        File::put($filePath, $template);
        $this->info("Migration created: {$filePath}");
        $this->info("Next: Run 'php artisan migrate' to apply changes.");
    }
}
