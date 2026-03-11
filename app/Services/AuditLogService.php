<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Audit Logging Service
 * 
 * Tracks all important actions for compliance and debugging.
 * Records who did what, when, and from where.
 */
class AuditLogService
{
    /**
     * Log an action.
     */
    public static function log(
        string $action,
        ?string $model = null,
        ?int $modelId = null,
        ?array $changes = null,
        ?int $organisationId = null
    ): void {
        try {
            $userId = Auth::id();

            AuditLog::create([
                'action' => $action,
                'model' => $model,
                'model_id' => $modelId,
                'organisation_id' => $organisationId,
                'user_id' => $userId,
                'changes' => $changes ? json_encode($changes) : null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);

            Log::info("📝 Audit log recorded", [
                'action' => $action,
                'model' => $model,
                'model_id' => $modelId,
                'user_id' => $userId,
                'organisation_id' => $organisationId,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to record audit log", [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log expense creation.
     */
    public static function logExpenseCreated(int $expenseId, int $organisationId, ?array $data = null): void
    {
        self::log('expense_created', 'Expense', $expenseId, $data, $organisationId);
    }

    /**
     * Log expense processing start.
     */
    public static function logExpenseProcessing(int $expenseId, int $organisationId): void
    {
        self::log('expense_processing_started', 'Expense', $expenseId, null, $organisationId);
    }

    /**
     * Log expense processing completion.
     */
    public static function logExpenseProcessed(int $expenseId, int $organisationId, array $extractedData = []): void
    {
        self::log('expense_processed', 'Expense', $expenseId, [
            'vendor' => $extractedData['vendor_name'] ?? null,
            'amount' => $extractedData['total_amount'] ?? null,
            'items_count' => count($extractedData['line_items'] ?? []),
        ], $organisationId);
    }

    /**
     * Log expense deletion.
     */
    public static function logExpenseDeleted(int $expenseId, int $organisationId): void
    {
        self::log('expense_deleted', 'Expense', $expenseId, null, $organisationId);
    }

    /**
     * Log duplicate detection.
     */
    public static function logDuplicateDetected(int $expenseId, int $duplicateOfId, int $organisationId): void
    {
        self::log('duplicate_detected', 'Expense', $expenseId, [
            'duplicate_of' => $duplicateOfId,
        ], $organisationId);
    }

    /**
     * Log Textract call.
     */
    public static function logTextractCall(int $expenseId, int $organisationId, bool $success, ?string $error = null): void
    {
        self::log($success ? 'textract_call_success' : 'textract_call_failed', 'Expense', $expenseId, [
            'error' => $error,
        ], $organisationId);
    }

    /**
     * Log Bedrock call.
     */
    public static function logBedrockCall(int $expenseId, int $organisationId, bool $success, ?string $error = null): void
    {
        self::log($success ? 'bedrock_call_success' : 'bedrock_call_failed', 'Expense', $expenseId, [
            'error' => $error,
        ], $organisationId);
    }

    /**
     * Log security event (e.g., failed validation, malware detected).
     */
    public static function logSecurityEvent(string $event, ?int $organisationId = null, array $details = []): void
    {
        self::log('security_event', null, null, array_merge([
            'event' => $event,
        ], $details), $organisationId);

        Log::warning("🔐 Security event logged", [
            'event' => $event,
            'organisation_id' => $organisationId,
            'details' => $details,
        ]);
    }

    /**
     * Get audit logs for a model.
     */
    public static function getLogsForModel(string $model, int $modelId, int $limit = 50)
    {
        return AuditLog::where('model', $model)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs for an organisation.
     */
    public static function getLogsForOrganisation(int $organisationId, int $limit = 100)
    {
        return AuditLog::where('organisation_id', $organisationId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
