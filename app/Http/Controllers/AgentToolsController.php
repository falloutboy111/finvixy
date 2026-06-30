<?php

namespace App\Http\Controllers;

use App\Services\AgentToolService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentToolsController extends Controller
{
    private const TOOL_MAP = [
        'get_spending_by_category' => true,
        'get_spending_by_item'     => true,
        'get_total_spending'       => true,
        'list_categories'          => true,
        'list_recent_expenses'     => true,
        'update_expense_category'  => true,
        'get_expense'              => true,
        'search_items'             => true,
        'sum_items'                => true,
        'get_expense_items'        => true,
        'lookup_price'             => true,
        'get_projects'             => true,
        'set_expense_project'      => true,
    ];

    public function __construct(private AgentToolService $service) {}

    public function dispatch(Request $request): JsonResponse
    {
        $tool    = $request->input('tool');
        $params  = $request->input('params', []);
        $context = $request->input('context', []);

        if (! $tool) {
            return response()->json(['error' => 'Missing required field: tool'], 400);
        }

        if (! isset(self::TOOL_MAP[$tool])) {
            return response()->json([
                'error'     => "Unknown tool: {$tool}",
                'available' => array_keys(self::TOOL_MAP),
            ], 400);
        }

        $orgId  = $context['organisation_id'] ?? null;
        $userId = $context['user_id'] ?? null;

        if (! $orgId || ! $userId) {
            return response()->json(['error' => 'context.organisation_id and context.user_id are required'], 400);
        }

        try {
            $result = $this->invoke($tool, (int) $orgId, (int) $userId, (array) $params);

            return response()->json(['tool' => $tool, 'result' => $result]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    private function invoke(string $tool, int $orgId, int $userId, array $p): mixed
    {
        return match ($tool) {
            'get_spending_by_category' => $this->service->getSpendingByCategory(
                $orgId, $userId,
                $this->require($p, 'category'),
                isset($p['start_date']) ? (string) $p['start_date'] : null,
                isset($p['end_date'])   ? (string) $p['end_date']   : null,
            ),
            'get_spending_by_item' => $this->service->getSpendingByItem(
                $orgId, $userId,
                $this->require($p, 'item_keyword'),
                isset($p['start_date']) ? (string) $p['start_date'] : null,
                isset($p['end_date'])   ? (string) $p['end_date']   : null,
            ),
            'get_total_spending' => $this->service->getTotalSpending(
                $orgId, $userId,
                isset($p['start_date']) ? (string) $p['start_date'] : null,
                isset($p['end_date'])   ? (string) $p['end_date']   : null,
            ),
            'list_categories'      => $this->service->listCategories($orgId, $userId),
            'list_recent_expenses' => $this->service->listRecentExpenses(
                $orgId, $userId,
                (int) ($p['limit'] ?? 5),
            ),
            'update_expense_category' => $this->service->updateExpenseCategory(
                $orgId, $userId,
                (int) $this->require($p, 'expense_id'),
                $this->require($p, 'category'),
            ),
            'get_expense' => $this->service->getExpense(
                $orgId, $userId,
                (int) $this->require($p, 'expense_id'),
            ),
            'search_items' => $this->service->searchItems(
                $orgId, $userId,
                $this->require($p, 'keyword'),
                isset($p['start_date']) ? (string) $p['start_date'] : null,
                isset($p['end_date'])   ? (string) $p['end_date']   : null,
            ),
            'sum_items' => $this->service->sumItems(
                $orgId, $userId,
                (array) $this->require($p, 'item_names'),
                isset($p['start_date']) ? (string) $p['start_date'] : null,
                isset($p['end_date'])   ? (string) $p['end_date']   : null,
            ),
            'get_expense_items' => $this->service->getExpenseItems(
                $orgId, $userId,
                (int) $this->require($p, 'expense_id'),
            ),
            'lookup_price' => $this->service->lookupPrice(
                $orgId, $userId,
                isset($p['product_name']) ? (string) $p['product_name'] : null,
                ($p['barcode'] ?? '') !== '' ? (string) $p['barcode'] : null,
            ),
            'get_projects' => $this->service->getProjects($orgId, $userId),
            'set_expense_project' => $this->service->setExpenseProject(
                $orgId, $userId,
                (int) $this->require($p, 'expense_id'),
                isset($p['project_id']) ? (string) $p['project_id'] : null,
            ),
        };
    }

    private function require(array $params, string $key): mixed
    {
        if (! array_key_exists($key, $params)) {
            throw new \InvalidArgumentException("Missing required param: {$key}");
        }

        return $params[$key];
    }
}
