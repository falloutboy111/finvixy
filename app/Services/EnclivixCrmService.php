<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Facades\Http;

class EnclivixCrmService
{
    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(rtrim(config('services.enclivix_crm.base_url'), '/'))
            ->withHeaders(['X-Finvixy-Token' => config('services.enclivix_crm.token')])
            ->timeout(15)
            ->acceptJson();
    }

    /**
     * Fetch the CRM project list. Returns [{id, name}].
     *
     * @return array<int, array{id: int|string, name: string}>
     */
    public function getProjects(): array
    {
        $response = $this->http()->get('/api/v1/projects');

        if (! $response->successful()) {
            throw new \RuntimeException("CRM getProjects failed ({$response->status()}): ".$response->body());
        }

        return (array) $response->json('projects', []);
    }

    /**
     * Push a new expense to the CRM (upsert by external_id). Returns crm_expense_id.
     */
    public function postExpense(Expense $expense): string
    {
        $items = $expense->expenseItems()
            ->get(['name', 'qty', 'price', 'total'])
            ->map(fn ($i) => [
                'name'  => $i->name,
                'qty'   => (float) $i->qty,
                'price' => (float) $i->price,
                'total' => (float) $i->total,
            ])
            ->all();

        $response = $this->http()->post('/api/v1/expenses', [
            'external_id' => (string) $expense->id,
            'project_id'  => $expense->crm_project_id ?: null,
            'receipt_url' => $expense->drive_web_link,
            'store'       => $expense->name ?? 'Unknown',
            'total'       => (float) $expense->amount,
            'currency'    => $expense->organisation?->currency ?? 'ZAR',
            'date'        => $expense->date?->toDateString() ?? now()->toDateString(),
            'category'    => $expense->category ?? 'Other',
            'items'       => $items,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException("CRM postExpense failed ({$response->status()}): ".$response->body());
        }

        $id = $response->json('crm_expense_id');

        if (empty($id)) {
            throw new \RuntimeException('CRM postExpense: response missing crm_expense_id');
        }

        return (string) $id;
    }

    /**
     * Update the project assignment for an already-synced expense.
     */
    public function patchExpenseProject(string $crmExpenseId, ?string $projectId): void
    {
        $response = $this->http()->patch("/api/v1/expenses/{$crmExpenseId}", [
            'project_id' => $projectId,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException("CRM patchExpenseProject failed ({$response->status()}): ".$response->body());
        }
    }
}
