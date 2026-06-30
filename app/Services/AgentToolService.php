<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\PriceLookupUsage;
use App\Services\EnclivixCrmService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgentToolService
{
    private const VALID_PERIODS = ['this_month', 'last_month', 'this_year', 'all_time'];

    private const SPENDING_STATUSES = ['processed', 'approved'];

    // -------------------------------------------------------------------------
    // Tool 1
    // -------------------------------------------------------------------------

    public function getSpendingByCategory(int $orgId, int $userId, string $category, string $period): array
    {
        $this->assertPeriod($period);
        [$from, $to] = $this->dateRange($period);

        $query = Expense::where('organisation_id', $orgId)
            ->where('user_id', $userId)
            ->where('category', $category)
            ->whereIn('status', self::SPENDING_STATUSES);

        if ($from) {
            $query->whereBetween('date', [$from, $to]);
        }

        $row = $query->selectRaw('SUM(amount) as total, COUNT(*) as count')->first();

        return [
            'total'    => (float) ($row->total ?? 0),
            'currency' => 'ZAR',
            'count'    => (int) ($row->count ?? 0),
        ];
    }

    // -------------------------------------------------------------------------
    // Tool 2
    // -------------------------------------------------------------------------

    public function getSpendingByItem(int $orgId, int $userId, string $itemKeyword, string $period): array
    {
        $this->assertPeriod($period);
        [$from, $to] = $this->dateRange($period);

        // SoftDeletes on ExpenseItem adds whereNull('expense_items.deleted_at') globally.
        // The joined expenses table needs its own explicit deleted_at guard.
        $query = ExpenseItem::query()
            ->where('expense_items.name', 'like', '%'.$itemKeyword.'%')
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->where('expenses.organisation_id', $orgId)
            ->where('expenses.user_id', $userId)
            ->whereIn('expenses.status', self::SPENDING_STATUSES)
            ->whereNull('expenses.deleted_at');

        if ($from) {
            $query->whereBetween('expenses.date', [$from, $to]);
        }

        $row = $query->selectRaw('SUM(expense_items.total) as total, COUNT(*) as count')->first();

        return [
            'total'  => (float) ($row->total ?? 0),
            'count'  => (int) ($row->count ?? 0),
            'period' => $period,
        ];
    }

    // -------------------------------------------------------------------------
    // Tool 8 — search_items: candidate discovery (agent reasons over results)
    // -------------------------------------------------------------------------

    public function searchItems(int $orgId, int $userId, string $keyword, string $period): array
    {
        $this->assertPeriod($period);
        [$from, $to] = $this->dateRange($period);

        $toRow = fn ($r) => [
            'name'  => $r->name,
            'total' => (float) $r->total,
            'count' => (int) $r->count,
        ];

        // Fast path: literal keyword appears in item name.
        $exactQuery = ExpenseItem::query()
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->where('expenses.organisation_id', $orgId)
            ->where('expenses.user_id', $userId)
            ->whereIn('expenses.status', self::SPENDING_STATUSES)
            ->whereNull('expenses.deleted_at')
            ->where('expense_items.name', 'like', '%'.trim($keyword).'%');

        if ($from) {
            $exactQuery->whereBetween('expenses.date', [$from, $to]);
        }

        $exact = $exactQuery
            ->selectRaw('expense_items.name, SUM(expense_items.total) as total, COUNT(*) as count')
            ->groupBy('expense_items.name')
            ->orderByDesc('total')
            ->limit(25)
            ->get();

        if ($exact->count() >= 3) {
            return $exact->map($toRow)->all();
        }

        // Broad path: keyword not literally in any name (e.g. "milk", "meat").
        // Return the user's recent distinct line items so the agent has candidates
        // to reason over — it can then identify "Douglasdale Full Cream 2LT" as
        // milk, "Beef Dry Wors" as meat, etc.
        $broadQuery = ExpenseItem::query()
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->where('expenses.organisation_id', $orgId)
            ->where('expenses.user_id', $userId)
            ->whereIn('expenses.status', self::SPENDING_STATUSES)
            ->whereNull('expenses.deleted_at');

        if ($from) {
            $broadQuery->whereBetween('expenses.date', [$from, $to]);
        }

        return $broadQuery
            ->selectRaw('expense_items.name, SUM(expense_items.total) as total, COUNT(*) as count')
            ->groupBy('expense_items.name')
            ->orderByRaw('MAX(expenses.date) DESC')
            ->limit(25)
            ->get()
            ->map($toRow)
            ->all();
    }

    // -------------------------------------------------------------------------
    // Tool 9 — sum_items: SQL-computed total over agent-selected item names
    // -------------------------------------------------------------------------

    public function sumItems(int $orgId, int $userId, array $itemNames, string $period): array
    {
        $this->assertPeriod($period);
        [$from, $to] = $this->dateRange($period);

        if (empty($itemNames)) {
            throw new \InvalidArgumentException('item_names must be a non-empty array.');
        }

        // Cap to prevent accidental abuse; parameterised IN list, never interpolated.
        $itemNames = array_values(array_slice($itemNames, 0, 30));

        $query = ExpenseItem::query()
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->where('expenses.organisation_id', $orgId)
            ->where('expenses.user_id', $userId)
            ->whereIn('expenses.status', self::SPENDING_STATUSES)
            ->whereNull('expenses.deleted_at')
            ->whereIn('expense_items.name', $itemNames);

        if ($from) {
            $query->whereBetween('expenses.date', [$from, $to]);
        }

        $row = $query->selectRaw('SUM(expense_items.total) as total, COUNT(*) as count')->first();

        return [
            'total'  => (float) ($row->total ?? 0),
            'count'  => (int) ($row->count ?? 0),
            'items'  => $itemNames,
            'period' => $period,
        ];
    }

    // -------------------------------------------------------------------------
    // Tool 3
    // -------------------------------------------------------------------------

    public function getTotalSpending(int $orgId, int $userId, string $period): array
    {
        $this->assertPeriod($period);
        [$from, $to] = $this->dateRange($period);

        $query = Expense::where('organisation_id', $orgId)
            ->where('user_id', $userId)
            ->whereIn('status', self::SPENDING_STATUSES);

        if ($from) {
            $query->whereBetween('date', [$from, $to]);
        }

        $row = $query->selectRaw('SUM(amount) as total, COUNT(*) as count')->first();

        return [
            'total'  => (float) ($row->total ?? 0),
            'count'  => (int) ($row->count ?? 0),
            'period' => $period,
        ];
    }

    // -------------------------------------------------------------------------
    // Tool 4
    // -------------------------------------------------------------------------

    public function listCategories(int $orgId, int $userId): array
    {
        return ExpenseCategory::where('organisation_id', $orgId)
            ->orderBy('sort_order')
            ->get(['name', 'slug'])
            ->map(fn ($c) => ['name' => $c->name, 'slug' => $c->slug])
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Tool 5
    // -------------------------------------------------------------------------

    public function listRecentExpenses(int $orgId, int $userId, int $limit = 5): array
    {
        $limit = min(max(1, $limit), 20);

        return Expense::where('organisation_id', $orgId)
            ->where('user_id', $userId)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'name', 'amount', 'category', 'date'])
            ->map(fn ($e) => [
                'id'       => $e->id,
                'name'     => $e->name,
                'amount'   => (float) $e->amount,
                'category' => $e->category,
                'date'     => $e->date->toDateString(),
            ])
            ->all();
    }

    // -------------------------------------------------------------------------
    // Tool 6
    // -------------------------------------------------------------------------

    public function updateExpenseCategory(int $orgId, int $userId, int $expenseId, string $category): array
    {
        $expense = Expense::where('organisation_id', $orgId)
            ->where('user_id', $userId)
            ->find($expenseId);

        if (! $expense) {
            throw new ModelNotFoundException("Expense {$expenseId} not found for this user.");
        }

        $valid = ExpenseCategory::where('organisation_id', $orgId)
            ->where('slug', $category)
            ->exists();

        if (! $valid) {
            throw new \InvalidArgumentException("Category '{$category}' is not valid for this organisation.");
        }

        $expense->category = $category;
        $expense->save();

        return [
            'ok'         => true,
            'expense_id' => $expense->id,
            'category'   => $expense->category,
        ];
    }

    // -------------------------------------------------------------------------
    // Tool 7
    // -------------------------------------------------------------------------

    public function getExpense(int $orgId, int $userId, int $expenseId): array
    {
        $expense = Expense::where('organisation_id', $orgId)
            ->where('user_id', $userId)
            ->find($expenseId);

        if (! $expense) {
            throw new ModelNotFoundException("Expense {$expenseId} not found for this user.");
        }

        $items = $expense->expenseItems()
            ->get(['name', 'description', 'qty', 'price', 'total'])
            ->map(fn ($i) => [
                'name'        => $i->name,
                'description' => $i->description,
                'qty'         => (float) $i->qty,
                'price'       => (float) $i->price,
                'total'       => (float) $i->total,
            ])
            ->all();

        return [
            'id'       => $expense->id,
            'name'     => $expense->name,
            'amount'   => (float) $expense->amount,
            'category' => $expense->category,
            'date'     => $expense->date->toDateString(),
            'status'   => $expense->status,
            'items'    => $items,
        ];
    }

    // -------------------------------------------------------------------------
    // Tool 10 — get_expense_items: line items for a single expense
    // -------------------------------------------------------------------------

    public function getExpenseItems(int $orgId, int $userId, int $expenseId): array
    {
        $expense = Expense::where('organisation_id', $orgId)
            ->where('user_id', $userId)
            ->find($expenseId);

        if (! $expense) {
            throw new ModelNotFoundException("Expense {$expenseId} not found for this user.");
        }

        $items = $expense->expenseItems()
            ->get(['name', 'qty', 'price'])
            ->map(fn ($i) => [
                'name'    => $i->name,
                'price'   => (float) $i->price,
                'qty'     => (float) $i->qty,
                'barcode' => null, // no barcode column in expense_items
            ])
            ->all();

        return [
            'expense_id' => $expense->id,
            'store'      => $expense->name,
            'date'       => $expense->date->toDateString(),
            'total'      => (float) $expense->amount,
            'items'      => $items,
        ];
    }

    // -------------------------------------------------------------------------
    // Tool 11 — lookup_price: receipt observations + Serper web fallback
    // -------------------------------------------------------------------------

    public function lookupPrice(int $orgId, int $userId, ?string $productName, ?string $barcode): array
    {
        // Step 1 — validate product_name (PII firewall)
        // Barcode param is accepted but unused: expense_items has no barcode column.
        // Barcode-narrowed queries can be added once that column exists.
        if ($productName !== null) {
            $productName = substr(trim($productName), 0, 80);
        }

        if (empty($productName)) {
            return ['matched' => false, 'error' => 'product_name is required', 'observed' => [], 'web' => []];
        }

        // Reject email addresses
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $productName)) {
            return ['matched' => false, 'error' => 'product_name contains personal data', 'observed' => [], 'web' => []];
        }

        // Reject long consecutive digit runs (phone, SA ID, card number, etc.)
        if (preg_match('/\d{9,}/', $productName)) {
            return ['matched' => false, 'error' => 'product_name contains personal data', 'observed' => [], 'web' => []];
        }

        // Reject formatted phone numbers (digits with separators only, 9+ digits total)
        $digitsStripped = preg_replace('/[\s\-.()+\/]/', '', $productName);
        if (ctype_digit($digitsStripped) && strlen($digitsStripped) >= 9) {
            return ['matched' => false, 'error' => 'product_name contains personal data', 'observed' => [], 'web' => []];
        }

        // Step 2 — receipt observations (never capped, always returned)
        $observed = $this->queryObservedPrices($orgId, $userId, $productName);

        // Step 3 — Serper fallback when observations are thin; check monthly cap first
        $cap  = (int) config('services.lookup.monthly_cap', 50);
        $used = PriceLookupUsage::getCount($orgId, $userId);
        $web  = [];

        if (count($observed) < 3) {
            if ($used >= $cap) {
                $resetDate = Carbon::now()->addMonthNoOverflow()->startOfMonth()->toDateString();

                return [
                    'matched'           => ! empty($observed),
                    'product'           => $productName,
                    'observed'          => $observed,
                    'web'               => [],
                    'note'              => "Monthly price-check limit reached — resets {$resetDate}.",
                    'lookups_remaining' => 0,
                    'limit_reached'     => true,
                ];
            }

            $location = $this->resolveLocation();
            $web      = $this->fetchSerperPrices($productName, $location);

            // Count every Serper attempt (call was made, result or not)
            PriceLookupUsage::recordLookup($orgId, $userId);
            $used++;
        }

        return [
            'matched'           => ! empty($observed) || ! empty($web),
            'product'           => $productName,
            'observed'          => $observed,
            'web'               => $web,
            'note'              => 'web prices are indicative, not exact',
            'lookups_remaining' => max(0, $cap - $used),
            'limit_reached'     => false,
        ];
    }

    /** @return list<array{store: string, price: float, as_of: string, source: string}> */
    private function queryObservedPrices(int $orgId, int $userId, string $productName): array
    {
        return ExpenseItem::query()
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->where('expenses.organisation_id', $orgId)
            ->where('expenses.user_id', $userId)
            ->whereIn('expenses.status', self::SPENDING_STATUSES)
            ->whereNull('expenses.deleted_at')
            ->where('expense_items.name', 'like', '%'.trim($productName).'%')
            ->orderByDesc('expenses.date')
            ->limit(10)
            ->select(
                'expense_items.price',
                'expenses.name as store_name',
                'expenses.date as expense_date',
            )
            ->get()
            ->filter(fn ($row) => ((float) $row->price) > 0)
            ->map(fn ($row) => [
                'store'  => (string) $row->store_name,
                'price'  => (float) $row->price,
                'as_of'  => substr((string) $row->expense_date, 0, 10),
                'source' => 'receipt',
            ])
            ->values()
            ->all();
    }

    private function resolveLocation(): string
    {
        // Per-user city override: add $userId param + User::find($userId)?->city lookup
        // once a city column exists on users.
        return config('services.serper.location', 'Johannesburg, South Africa');
    }

    /** @return list<array{title: string, snippet: string}> */
    private function fetchSerperPrices(string $productName, string $location): array
    {
        $apiKey = config('services.serper.api_key');

        if (! $apiKey) {
            return [];
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders(['X-API-KEY' => $apiKey])
                ->post(config('services.serper.endpoint', 'https://google.serper.dev/search'), [
                    'q'        => $productName.' price '.$location,
                    'gl'       => config('services.serper.gl', 'za'),
                    'hl'       => 'en',
                    'location' => $location,
                    'num'      => (int) config('services.serper.num', 8),
                ]);

            if (! $response->successful()) {
                Log::warning('Serper search failed', [
                    'status'  => $response->status(),
                    'product' => $productName,
                ]);

                return [];
            }

            return collect($response->json('organic', []))
                ->take(6)
                ->map(fn ($item) => [
                    'title'   => (string) ($item['title'] ?? ''),
                    'snippet' => (string) ($item['snippet'] ?? ''),
                ])
                ->filter(fn ($item) => $item['title'] !== '' || $item['snippet'] !== '')
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Serper request exception', [
                'product' => $productName,
                'error'   => $e->getMessage(),
            ]);

            return [];
        }
    }

    // -------------------------------------------------------------------------
    // Tool 12 — get_projects: list CRM projects the user can claim against
    // -------------------------------------------------------------------------

    public function getProjects(int $orgId, int $userId): array
    {
        $user = \App\Models\User::find($userId);

        if (! $user || ! $user->crm_sync_enabled) {
            return ['projects' => [], 'note' => 'CRM sync is not enabled for this account.'];
        }

        $raw = app(EnclivixCrmService::class)->getProjects();

        $projects = array_map(fn ($p) => [
            'id'   => $p['id'],
            'name' => (string) ($p['name'] ?? ''),
        ], $raw);

        return ['projects' => $projects];
    }

    // -------------------------------------------------------------------------
    // Tool 13 — set_expense_project: assign an expense to a CRM project
    // -------------------------------------------------------------------------

    public function setExpenseProject(int $orgId, int $userId, int $expenseId, ?int $projectId): array
    {
        $expense = Expense::where('organisation_id', $orgId)
            ->where('user_id', $userId)
            ->find($expenseId);

        if (! $expense) {
            throw new ModelNotFoundException("Expense {$expenseId} not found for this user.");
        }

        if (empty($expense->crm_expense_id)) {
            throw new \InvalidArgumentException(
                "Expense {$expenseId} has not been synced to the CRM yet. Wait a moment and try again."
            );
        }

        app(EnclivixCrmService::class)->patchExpenseProject($expense->crm_expense_id, $projectId);

        $expense->update(['crm_project_id' => $projectId]);

        return ['ok' => true, 'project_id' => $projectId];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function assertPeriod(string $period): void
    {
        if (! in_array($period, self::VALID_PERIODS, true)) {
            throw new \InvalidArgumentException(
                "Invalid period '{$period}'. Must be one of: ".implode(', ', self::VALID_PERIODS).'.'
            );
        }
    }

    /** @return array{string|null, string|null} */
    private function dateRange(string $period): array
    {
        return match ($period) {
            'this_month' => [
                Carbon::now()->startOfMonth()->toDateString(),
                Carbon::now()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                Carbon::now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'this_year' => [
                Carbon::now()->startOfYear()->toDateString(),
                Carbon::now()->endOfYear()->toDateString(),
            ],
            'all_time' => [null, null],
        };
    }
}
