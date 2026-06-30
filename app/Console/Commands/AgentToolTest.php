<?php

namespace App\Console\Commands;

use App\Services\AgentToolService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AgentToolTest extends Command
{
    protected $signature = 'agent:tool-test
                            {tool : Tool name (e.g. get_total_spending)}
                            {--params= : JSON-encoded params (e.g. \'{"period":"this_month"}\') }
                            {--org=1 : organisation_id to use as context}
                            {--user=1 : user_id to use as context}';

    protected $description = 'Call an agent tool directly to verify its output without going through HTTP';

    public function __construct(private AgentToolService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tool   = $this->argument('tool');
        $params = json_decode($this->option('params') ?? '{}', true) ?? [];
        $orgId  = (int) $this->option('org');
        $userId = (int) $this->option('user');

        $this->line("  tool : {$tool}");
        $this->line("  org  : {$orgId}   user: {$userId}");
        $this->newLine();

        try {
            $result = match ($tool) {
                'get_spending_by_category' => $this->service->getSpendingByCategory(
                    $orgId, $userId,
                    (string) ($params['category'] ?? $this->error('Missing param: category') ?: ''),
                    (string) ($params['period'] ?? 'this_month'),
                ),
                'get_spending_by_item' => $this->service->getSpendingByItem(
                    $orgId, $userId,
                    (string) ($params['item_keyword'] ?? $this->error('Missing param: item_keyword') ?: ''),
                    (string) ($params['period'] ?? 'this_month'),
                ),
                'get_total_spending' => $this->service->getTotalSpending(
                    $orgId, $userId,
                    (string) ($params['period'] ?? 'this_month'),
                ),
                'list_categories'      => $this->service->listCategories($orgId, $userId),
                'list_recent_expenses' => $this->service->listRecentExpenses(
                    $orgId, $userId,
                    (int) ($params['limit'] ?? 5),
                ),
                'update_expense_category' => $this->service->updateExpenseCategory(
                    $orgId, $userId,
                    (int) ($params['expense_id'] ?? 0),
                    (string) ($params['category'] ?? ''),
                ),
                'get_expense' => $this->service->getExpense(
                    $orgId, $userId,
                    (int) ($params['expense_id'] ?? 0),
                ),
                default => throw new \InvalidArgumentException("Unknown tool: {$tool}"),
            };

            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;
        } catch (ModelNotFoundException $e) {
            $this->error('Not found: '.$e->getMessage());

            return Command::FAILURE;
        } catch (\InvalidArgumentException $e) {
            $this->error('Invalid argument: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
