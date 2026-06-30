<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\PendingConfirmation;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ConfirmationService
{
    private const TTL_MINUTES = 30;

    // WhatsApp hard limit is 10 rows per message. Controls take up to 3 rows
    // (Show more, Type name, Skip), so project rows per page is 7.
    private const PROJECTS_PER_PAGE = 7;

    // -------------------------------------------------------------------------
    // Task 5 / Task 2 — proactive paginated project picker
    // -------------------------------------------------------------------------

    /**
     * Build and send an interactive list picker for the given project page.
     *
     * Call with $offset = 0 and $isPaging = false for the initial send after receipt
     * processing. For "__more__" taps, pass the next offset and $isPaging = true so
     * the existing `awaiting_type_reply` flag is not reset.
     */
    public function sendProactiveProjectPicker(
        Expense $expense,
        string $to,
        WhatsAppService $whatsApp,
        int $offset = 0,
        bool $isPaging = false,
    ): void {
        $result   = app(AgentToolService::class)->paginatedProjects($expense->user_id, self::PROJECTS_PER_PAGE, $offset);
        $projects = $result['projects'];
        $total    = $result['total'];

        if ($total === 0) {
            Log::info('ConfirmationService: no CRM projects — skipping picker', ['expense_id' => $expense->id]);
            return;
        }

        $needsMore = $total > self::PROJECTS_PER_PAGE;

        $rows = array_map(fn ($p) => [
            'id'    => 'proj:'.$expense->id.':'.$p['id'],
            'title' => mb_substr($p['name'], 0, 24),
        ], $projects);

        if ($needsMore) {
            $nextOffset = $offset + self::PROJECTS_PER_PAGE;
            if ($nextOffset >= $total) {
                $nextOffset = 0; // wrap back to page 1
            }
            $rows[] = ['id' => 'proj:'.$expense->id.':__more__:'.$nextOffset, 'title' => 'Show more →'];
        }

        $rows[] = ['id' => 'proj:'.$expense->id.':__type__', 'title' => 'Type a project name'];
        $rows[] = ['id' => 'proj:'.$expense->id.':__skip__', 'title' => 'Skip for now'];

        $whatsApp->sendList(
            $to,
            'Assign to project?',
            'Which project should this expense go to?',
            [['title' => 'Projects', 'rows' => $rows]],
        );

        // Task 4: paging only refreshes TTL; initial send also resets the type flag
        if ($isPaging) {
            PendingConfirmation::where('expense_id', $expense->id)
                ->update(['expires_at' => now()->addMinutes(self::TTL_MINUTES)]);
        } else {
            PendingConfirmation::updateOrCreate(
                ['expense_id' => $expense->id],
                [
                    'user_id'             => $expense->user_id,
                    'kind'                => 'project',
                    'awaiting_type_reply' => false,
                    'expires_at'          => now()->addMinutes(self::TTL_MINUTES),
                ],
            );
        }

        Log::info('ConfirmationService: project picker sent', [
            'expense_id' => $expense->id,
            'offset'     => $offset,
            'total'      => $total,
            'page_count' => count($projects),
        ]);
    }

    // -------------------------------------------------------------------------
    // Task 1 — parse agent reply as optional JSON action
    // -------------------------------------------------------------------------

    /**
     * If the agent reply is a valid JSON action (`ask_category` or `ask_project`),
     * return the decoded array. Otherwise return null (send as plain text).
     *
     * @return array{action: string, expense_id: int, message?: string, options?: list<string>, projects?: list<array{id: string, name: string}>}|null
     */
    public function parseAction(string $reply): ?array
    {
        $trimmed = trim($reply);

        if ($trimmed === '' || $trimmed[0] !== '{') {
            return null;
        }

        try {
            $data = json_decode($trimmed, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        $action = $data['action'] ?? null;

        if (! in_array($action, ['ask_category', 'ask_project'], true)) {
            return null;
        }

        if (! isset($data['expense_id'])) {
            return null;
        }

        return $data;
    }

    /**
     * Render a parsed agent action as an interactive WhatsApp message.
     * Falls back to plain text if the action carries no interactive options.
     */
    public function sendActionMessage(string $to, array $action, WhatsAppService $whatsApp): void
    {
        $expenseId = (int) $action['expense_id'];
        $message   = $action['message'] ?? '';

        if ($action['action'] === 'ask_category') {
            $options = (array) ($action['options'] ?? []);

            if (empty($options)) {
                $whatsApp->sendText($to, $message ?: 'What category is this expense?');
                return;
            }

            $buttons = array_map(fn ($opt) => [
                'id'    => 'cat:'.$expenseId.':'.($opt === '__keep__' ? '__keep__' : $opt),
                'title' => $opt === '__keep__'
                    ? 'Keep current'
                    : ucwords(str_replace('-', ' ', mb_substr($opt, 0, 20))),
            ], array_slice($options, 0, 3));

            $whatsApp->sendButtons($to, $message ?: 'Choose a category:', $buttons);

            $expense = Expense::find($expenseId);
            if ($expense) {
                $this->storePending($expenseId, (int) $expense->user_id, 'category');
            }
        }

        if ($action['action'] === 'ask_project') {
            $projects = (array) ($action['projects'] ?? []);

            if (empty($projects)) {
                $whatsApp->sendText($to, $message ?: 'Which project is this expense for?');
                return;
            }

            $rows = array_map(fn ($p) => [
                'id'    => 'proj:'.$expenseId.':'.$p['id'],
                'title' => mb_substr($p['name'], 0, 24),
            ], array_slice($projects, 0, self::PROJECTS_PER_PAGE));

            $rows[] = ['id' => 'proj:'.$expenseId.':__type__', 'title' => 'Type a project name'];
            $rows[] = ['id' => 'proj:'.$expenseId.':__skip__', 'title' => 'Skip for now'];

            $whatsApp->sendList(
                $to,
                'Assign to project?',
                $message ?: 'Choose the project for this expense.',
                [['title' => 'Projects', 'rows' => $rows]],
            );

            $expense = Expense::find($expenseId);
            if ($expense) {
                $this->storePending($expenseId, (int) $expense->user_id, 'project');
            }
        }
    }

    // -------------------------------------------------------------------------
    // Task 3 — deterministic interactive reply handlers
    // -------------------------------------------------------------------------

    /**
     * Handle a `button_reply` or `list_reply` tap. Returns the acknowledgement text
     * to send back to the user, or an empty string if the handler already sent a
     * message (e.g. "Show more →" sends a new list page).
     */
    public function handleInteractiveTap(
        string $replyId,
        User $user,
        WhatsAppService $whatsApp,
        string $to,
    ): string {
        if (str_starts_with($replyId, 'cat:')) {
            return $this->handleCategoryTap($replyId, $user);
        }

        if (str_starts_with($replyId, 'proj:')) {
            return $this->handleProjectTap($replyId, $user, $whatsApp, $to);
        }

        return 'Got it.';
    }

    private function handleCategoryTap(string $replyId, User $user): string
    {
        $parts        = explode(':', $replyId, 3);
        $expenseId    = (int) ($parts[1] ?? 0);
        $categorySlug = $parts[2] ?? '';

        if (! $expenseId) {
            return 'Something went wrong — please try again.';
        }

        if ($categorySlug === '__keep__') {
            PendingConfirmation::where('expense_id', $expenseId)->delete();
            return 'Keeping the current category.';
        }

        if (! $categorySlug) {
            return 'Something went wrong — please try again.';
        }

        try {
            app(AgentToolService::class)->updateExpenseCategory(
                $user->organisation_id,
                $user->id,
                $expenseId,
                $categorySlug,
            );
        } catch (\Throwable $e) {
            Log::warning('ConfirmationService: category tap failed', [
                'expense_id' => $expenseId,
                'category'   => $categorySlug,
                'error'      => $e->getMessage(),
            ]);

            return "Couldn't update the category — please try again.";
        }

        PendingConfirmation::where('expense_id', $expenseId)->delete();

        $label = ucwords(str_replace('-', ' ', $categorySlug));

        return "Category set to *{$label}*.";
    }

    private function handleProjectTap(
        string $replyId,
        User $user,
        WhatsAppService $whatsApp,
        string $to,
    ): string {
        // 'proj' : expenseId : <rest>
        // <rest> can be: a UUID, '__skip__', '__type__', or '__more__:<nextOffset>'
        $parts     = explode(':', $replyId, 3);
        $expenseId = (int) ($parts[1] ?? 0);
        $rest      = $parts[2] ?? '';

        if (! $expenseId) {
            return 'Something went wrong — please try again.';
        }

        // --- Pagination: show next page ---
        if (str_starts_with($rest, '__more__:')) {
            $nextOffset = (int) substr($rest, strlen('__more__:'));

            $expense = Expense::where('user_id', $user->id)->find($expenseId);

            if (! $expense) {
                return 'Expense not found — please try again.';
            }

            try {
                // isPaging = true → preserves awaiting_type_reply, only refreshes TTL
                $this->sendProactiveProjectPicker($expense, $to, $whatsApp, $nextOffset, true);
            } catch (\Throwable $e) {
                Log::warning('ConfirmationService: __more__ paging failed', [
                    'expense_id' => $expenseId,
                    'offset'     => $nextOffset,
                    'error'      => $e->getMessage(),
                ]);

                return "Couldn't load more projects — please try again.";
            }

            return ''; // list message already sent; no separate text ack needed
        }

        // --- Type path: arm awaiting_type_reply ---
        if ($rest === '__type__') {
            PendingConfirmation::where('expense_id', $expenseId)
                ->where('user_id', $user->id)
                ->update(['awaiting_type_reply' => true]);

            return "Sure — just type the project name and I'll find it.";
        }

        // --- Skip ---
        if ($rest === '__skip__') {
            PendingConfirmation::where('expense_id', $expenseId)->delete();

            return "No problem — you can assign it later from the app.";
        }

        if (! $rest) {
            return 'Something went wrong — please try again.';
        }

        // --- Project UUID tap ---
        $projectId = $rest;

        $expense = Expense::where('user_id', $user->id)->find($expenseId);

        if (! $expense) {
            return 'Expense not found — please try again.';
        }

        // Patch CRM immediately if already synced; otherwise the local crm_project_id
        // is picked up by SyncExpenseToCrm when it fires 45 s later.
        if ($expense->crm_expense_id) {
            try {
                app(EnclivixCrmService::class)->patchExpenseProject($expense->crm_expense_id, $projectId);
            } catch (\Throwable $e) {
                Log::warning('ConfirmationService: CRM project patch failed', [
                    'expense_id'     => $expenseId,
                    'crm_expense_id' => $expense->crm_expense_id,
                    'project_id'     => $projectId,
                    'error'          => $e->getMessage(),
                ]);

                return "Couldn't assign the project — please try again.";
            }
        }

        $expense->update(['crm_project_id' => $projectId]);

        PendingConfirmation::where('expense_id', $expenseId)->delete();

        return 'Project assigned.';
    }

    // -------------------------------------------------------------------------
    // Task 4 helpers
    // -------------------------------------------------------------------------

    public function storePending(int $expenseId, int $userId, string $kind): void
    {
        PendingConfirmation::updateOrCreate(
            ['expense_id' => $expenseId],
            [
                'user_id'             => $userId,
                'kind'                => $kind,
                'awaiting_type_reply' => false,
                'expires_at'          => now()->addMinutes(self::TTL_MINUTES),
            ],
        );
    }
}
