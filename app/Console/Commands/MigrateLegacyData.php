<?php

namespace App\Console\Commands;

use App\Models\AiUsageLog;
use App\Models\ConnectedAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\Organisation;
use App\Models\User;
use App\Models\WhatsappWebhook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyData extends Command
{
    protected $signature = 'app:migrate-legacy-data
        {--fresh : Wipe all migrated data before importing}
        {--only= : Only migrate specific tables (comma-separated: organisations,users,categories,expenses,items,accounts,ai_logs,webhooks)}';

    protected $description = 'Pull data from the legacy payment-manager-api database into Finvixy';

    /** @var array<string, int> UUID → new bigint ID maps */
    private array $orgMap = [];

    private array $userMap = [];

    private array $expenseMap = [];

    private int $created = 0;

    private int $updated = 0;

    private int $skipped = 0;

    public function handle(): int
    {
        $this->info('Connecting to legacy database...');

        try {
            DB::connection('legacy')->getPdo();
        } catch (\Exception $e) {
            $this->error('Could not connect to legacy database: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Connected successfully to legacy database.');
        $this->newLine();

        $only = $this->option('only')
            ? array_map('trim', explode(',', $this->option('only')))
            : null;

        if ($this->option('fresh')) {
            if (! $this->confirm('This will DELETE all previously migrated records. Continue?')) {
                return self::SUCCESS;
            }
            $this->wipeMigratedData();
        }

        $steps = [
            'organisations' => 'migrateOrganisations',
            'users' => 'migrateUsers',
            'categories' => 'migrateExpenseCategories',
            'expenses' => 'migrateExpenses',
            'items' => 'migrateExpenseItems',
            'accounts' => 'migrateConnectedAccounts',
            'ai_logs' => 'migrateAiUsageLogs',
            'webhooks' => 'migrateWhatsappWebhooks',
        ];

        foreach ($steps as $key => $method) {
            if ($only && ! in_array($key, $only)) {
                continue;
            }
            $this->{$method}();
        }

        $this->newLine();
        $this->info("Migration complete: {$this->created} created, {$this->updated} updated, {$this->skipped} skipped.");

        return self::SUCCESS;
    }

    private function legacy(): \Illuminate\Database\Connection
    {
        return DB::connection('legacy');
    }

    /**
     * Pre-load existing legacy_uuid→id maps for a model.
     *
     * @return array<string, int>
     */
    private function loadExistingMap(string $table): array
    {
        return DB::table($table)
            ->whereNotNull('legacy_uuid')
            ->pluck('id', 'legacy_uuid')
            ->all();
    }

    // ─── Organisations ──────────────────────────────────────────

    private function migrateOrganisations(): void
    {
        $this->info('Migrating organisations...');
        $this->orgMap = $this->loadExistingMap('organisations');

        $rows = $this->legacy()->table('organizations')->get();
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $org = Organisation::withoutTimestamps(function () use ($row) {
                return Organisation::updateOrCreate(
                    ['legacy_uuid' => $row->id],
                    [
                        'name' => $row->name,
                        'logo_path' => $row->logo_path,
                        'email' => $row->email,
                        'phone' => $row->phone,
                        'tax_id' => $row->tax_id,
                        'status' => $row->status ?? 'active',
                        'currency' => $row->currency ?? 'ZAR',
                        'timezone' => $row->timezone ?? 'Africa/Johannesburg',
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ],
                );
            });

            $this->orgMap[$row->id] = $org->id;
            $org->wasRecentlyCreated ? $this->created++ : $this->updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Organisations: {$rows->count()} processed.");
    }

    // ─── Users ──────────────────────────────────────────────────

    private function migrateUsers(): void
    {
        $this->info('Migrating users...');
        $this->userMap = $this->loadExistingMap('users');

        // Ensure org map is loaded
        if (empty($this->orgMap)) {
            $this->orgMap = $this->loadExistingMap('organisations');
        }

        $rows = $this->legacy()->table('users')->get();
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $orgId = $row->organization_id ? ($this->orgMap[$row->organization_id] ?? null) : null;

            if ($row->organization_id && ! $orgId) {
                $this->skipped++;
                $bar->advance();

                continue; // Skip users whose org wasn't migrated
            }

            $user = User::withoutTimestamps(function () use ($row, $orgId) {
                return User::updateOrCreate(
                    ['legacy_uuid' => $row->id],
                    [
                        'organisation_id' => $orgId,
                        'name' => $row->name,
                        'email' => $row->email,
                        'email_verified_at' => $row->email_verified_at ?? now(),
                        'password' => $row->password, // Already hashed
                        'avatar' => $row->avatar,
                        'whatsapp_number' => $row->whatsapp_number,
                        'whatsapp_enabled' => $row->whatsapp_enabled ?? false,
                        'email_2fa_enabled_at' => now(),
                        'first_time_login' => $row->first_time_login ?? true,
                        'last_login_at' => $row->last_login_at,
                        'last_login_ip' => $row->last_login_ip,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ],
                );
            });

            $this->userMap[$row->id] = $user->id;
            $user->wasRecentlyCreated ? $this->created++ : $this->updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Users: {$rows->count()} processed.");
    }

    // ─── Expense Categories ─────────────────────────────────────

    private function migrateExpenseCategories(): void
    {
        $this->info('Migrating expense categories...');

        if (empty($this->orgMap)) {
            $this->orgMap = $this->loadExistingMap('organisations');
        }

        $rows = $this->legacy()->table('expense_categories')->get();
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $orgId = $this->orgMap[$row->organization_id] ?? null;

            if (! $orgId) {
                $this->skipped++;
                $bar->advance();

                continue;
            }

            $cat = ExpenseCategory::withoutTimestamps(function () use ($row, $orgId) {
                return ExpenseCategory::updateOrCreate(
                    ['organisation_id' => $orgId, 'slug' => $row->slug],
                    [
                        'legacy_uuid' => $row->id,
                        'name' => $row->name,
                        'description' => $row->description,
                        'is_default' => $row->is_default ?? false,
                        'sort_order' => $row->sort_order ?? 0,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ],
                );
            });

            $cat->wasRecentlyCreated ? $this->created++ : $this->updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Expense categories: {$rows->count()} processed.");
    }

    // ─── Expenses ───────────────────────────────────────────────

    private function migrateExpenses(): void
    {
        $this->info('Migrating expenses...');
        $this->expenseMap = $this->loadExistingMap('expenses');

        if (empty($this->orgMap)) {
            $this->orgMap = $this->loadExistingMap('organisations');
        }
        if (empty($this->userMap)) {
            $this->userMap = $this->loadExistingMap('users');
        }

        $validStatuses = ['pending', 'processing', 'processed', 'approved', 'rejected', 'failed'];

        // Chunk to avoid memory issues on large datasets
        $this->legacy()->table('expenses')->orderBy('created_at')->chunk(500, function ($rows) use ($validStatuses) {
            foreach ($rows as $row) {
                $orgId = $this->orgMap[$row->organization_id] ?? null;
                $userId = $this->userMap[$row->user_id] ?? null;

                if (! $orgId || ! $userId) {
                    $this->skipped++;

                    continue;
                }

                $status = in_array($row->status, $validStatuses) ? $row->status : 'pending';

                $expense = Expense::withoutTimestamps(function () use ($row, $orgId, $userId, $status) {
                    return Expense::updateOrCreate(
                        ['legacy_uuid' => $row->id],
                        [
                            'organisation_id' => $orgId,
                            'user_id' => $userId,
                            'name' => $row->name ?? 'Unnamed Expense',
                            'category' => $row->category,
                            'amount' => $row->amount ?? 0,
                            'tax' => $row->tax,
                            'date' => $row->date,
                            'image_path' => $row->image_path,
                            'receipt_path' => $row->receipt_path,
                            'additional_fields' => $row->additional_fields,
                            'extracted_data' => $row->extracted_data,
                            'notes' => $row->notes,
                            'status' => $status,
                            'is_duplicate' => $row->is_dup ?? false,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ],
                    );
                });

                $this->expenseMap[$row->id] = $expense->id;
                $expense->wasRecentlyCreated ? $this->created++ : $this->updated++;
            }
        });

        // Second pass: link duplicate_of references
        $this->legacy()->table('expenses')
            ->whereNotNull('duplicate_of')
            ->orderBy('created_at')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $newId = $this->expenseMap[$row->id] ?? null;
                    $dupOfId = $this->expenseMap[$row->duplicate_of] ?? null;

                    if ($newId && $dupOfId) {
                        Expense::where('id', $newId)->update(['duplicate_of' => $dupOfId]);
                    }
                }
            });

        $total = $this->legacy()->table('expenses')->count();
        $this->info("  Expenses: {$total} processed.");
    }

    // ─── Expense Items ──────────────────────────────────────────

    private function migrateExpenseItems(): void
    {
        $this->info('Migrating expense items...');

        if (empty($this->expenseMap)) {
            $this->expenseMap = $this->loadExistingMap('expenses');
        }

        $this->legacy()->table('expense_items')->orderBy('created_at')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $expenseId = $this->expenseMap[$row->expense_id] ?? null;

                if (! $expenseId) {
                    $this->skipped++;

                    continue;
                }

                $item = ExpenseItem::withoutTimestamps(function () use ($row, $expenseId) {
                    return ExpenseItem::updateOrCreate(
                        ['legacy_uuid' => $row->id],
                        [
                            'expense_id' => $expenseId,
                            'name' => $row->name,
                            'description' => $row->description,
                            'qty' => $row->qty ?? 1,
                            'price' => $row->price ?? 0,
                            'total' => $row->total ?? 0,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ],
                    );
                });

                $item->wasRecentlyCreated ? $this->created++ : $this->updated++;
            }
        });

        $total = $this->legacy()->table('expense_items')->count();
        $this->info("  Expense items: {$total} processed.");
    }

    // ─── Connected Accounts ─────────────────────────────────────

    private function migrateConnectedAccounts(): void
    {
        $this->info('Migrating connected accounts...');

        if (empty($this->orgMap)) {
            $this->orgMap = $this->loadExistingMap('organisations');
        }
        if (empty($this->userMap)) {
            $this->userMap = $this->loadExistingMap('users');
        }

        $rows = $this->legacy()->table('connected_accounts')->get();
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $orgId = $this->orgMap[$row->organization_id] ?? null;
            $userId = $this->userMap[$row->user_id] ?? null;

            if (! $orgId || ! $userId) {
                $this->skipped++;
                $bar->advance();

                continue;
            }

            $account = ConnectedAccount::withoutTimestamps(function () use ($row, $orgId, $userId) {
                return ConnectedAccount::updateOrCreate(
                    ['legacy_uuid' => $row->id],
                    [
                        'organisation_id' => $orgId,
                        'user_id' => $userId,
                        'provider' => $row->provider ?? 'google_drive',
                        'email' => $row->email,
                        'credentials' => $row->credentials,
                        'settings' => $row->settings,
                        'is_active' => $row->is_active ?? true,
                        'last_sync_at' => $row->last_sync_at,
                        'expires_at' => $row->expires_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ],
                );
            });

            $account->wasRecentlyCreated ? $this->created++ : $this->updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Connected accounts: {$rows->count()} processed.");
    }

    // ─── AI Usage Logs ──────────────────────────────────────────

    private function migrateAiUsageLogs(): void
    {
        $this->info('Migrating AI usage logs...');

        if (empty($this->orgMap)) {
            $this->orgMap = $this->loadExistingMap('organisations');
        }
        if (empty($this->userMap)) {
            $this->userMap = $this->loadExistingMap('users');
        }
        if (empty($this->expenseMap)) {
            $this->expenseMap = $this->loadExistingMap('expenses');
        }

        $this->legacy()->table('ai_usage_logs')->orderBy('created_at')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $orgId = $this->orgMap[$row->organization_id] ?? null;

                if (! $orgId) {
                    $this->skipped++;

                    continue;
                }

                $log = AiUsageLog::withoutTimestamps(function () use ($row, $orgId) {
                    return AiUsageLog::updateOrCreate(
                        ['legacy_uuid' => $row->id],
                        [
                            'organisation_id' => $orgId,
                            'user_id' => $row->user_id ? ($this->userMap[$row->user_id] ?? null) : null,
                            'expense_id' => $row->expense_id ? ($this->expenseMap[$row->expense_id] ?? null) : null,
                            'service_type' => $row->service_type,
                            'model_name' => $row->model_name,
                            'prompt_tokens' => $row->prompt_tokens ?? 0,
                            'completion_tokens' => $row->completion_tokens ?? 0,
                            'total_tokens' => $row->total_tokens ?? 0,
                            'input_characters' => $row->input_characters ?? 0,
                            'output_characters' => $row->output_characters ?? 0,
                            'estimated_cost' => $row->estimated_cost ?? 0,
                            'currency' => $row->currency ?? 'USD',
                            'request_summary' => $row->request_summary,
                            'response_time_ms' => $row->response_time_ms,
                            'success' => $row->success ?? true,
                            'error_message' => $row->error_message,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ],
                    );
                });

                $log->wasRecentlyCreated ? $this->created++ : $this->updated++;
            }
        });

        $total = $this->legacy()->table('ai_usage_logs')->count();
        $this->info("  AI usage logs: {$total} processed.");
    }

    // ─── WhatsApp Webhooks ──────────────────────────────────────

    private function migrateWhatsappWebhooks(): void
    {
        $this->info('Migrating WhatsApp webhooks...');

        if (empty($this->orgMap)) {
            $this->orgMap = $this->loadExistingMap('organisations');
        }
        if (empty($this->userMap)) {
            $this->userMap = $this->loadExistingMap('users');
        }
        if (empty($this->expenseMap)) {
            $this->expenseMap = $this->loadExistingMap('expenses');
        }

        $this->legacy()->table('whatsapp_webhooks')->orderBy('created_at')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $webhook = WhatsappWebhook::withoutTimestamps(function () use ($row) {
                    return WhatsappWebhook::updateOrCreate(
                        ['legacy_uuid' => $row->id],
                        [
                            'from' => $row->from,
                            'message_id' => $row->message_id,
                            'type' => $row->type ?? 'unknown',
                            'payload' => $row->payload,
                            'expense_id' => $row->expense_id ? ($this->expenseMap[$row->expense_id] ?? null) : null,
                            'user_id' => $row->user_id ? ($this->userMap[$row->user_id] ?? null) : null,
                            'organisation_id' => $row->organization_id ? ($this->orgMap[$row->organization_id] ?? null) : null,
                            'status' => $row->status ?? 'received',
                            'error_message' => $row->error_message,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ],
                    );
                });

                $webhook->wasRecentlyCreated ? $this->created++ : $this->updated++;
            }
        });

        $total = $this->legacy()->table('whatsapp_webhooks')->count();
        $this->info("  WhatsApp webhooks: {$total} processed.");
    }

    // ─── Fresh Wipe ─────────────────────────────────────────────

    private function wipeMigratedData(): void
    {
        $this->warn('Wiping previously migrated data...');

        // Delete in reverse dependency order
        $tables = [
            'whatsapp_webhooks',
            'ai_usage_logs',
            'connected_accounts',
            'expense_items',
            'expenses',
            'expense_categories',
            'users',
            'organisations',
        ];

        foreach ($tables as $table) {
            $count = DB::table($table)->whereNotNull('legacy_uuid')->delete();
            $this->line("  Deleted {$count} from {$table}");
        }
    }
}
