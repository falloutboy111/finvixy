<?php

namespace App\Console\Commands;

use App\Exceptions\XeroApiException;
use App\Exceptions\XeroReauthRequired;
use App\Models\User;
use App\Models\XeroConnection;
use App\Services\Xero\XeroBillService;
use Illuminate\Console\Command;

class XeroTestBill extends Command
{
    protected $signature = 'xero:test-bill {user_id : The ID of the user whose Xero connection to test}';

    protected $description = 'Push a dummy DRAFT bill to Xero for the given user and print the InvoiceID';

    public function handle(XeroBillService $billService): int
    {
        $user = User::find($this->argument('user_id'));

        if (! $user) {
            $this->error("User {$this->argument('user_id')} not found.");
            return self::FAILURE;
        }

        $conn = XeroConnection::where('user_id', $user->id)->first();

        if (! $conn) {
            $this->error("No Xero connection found for user {$user->id} ({$user->email}). Have them connect via Settings → Connected accounts.");
            return self::FAILURE;
        }

        $this->info("Testing Xero bill push for {$user->email} (tenant: {$conn->tenant_name})...");

        try {
            $invoiceId = $billService->createBill($conn, [
                'vendor_name' => 'Test Vendor Pty Ltd',
                'description' => 'Test expense from Finvixy xero:test-bill command',
                'unit_amount' => 99.00,
                'account_code' => '400',
                'date' => now()->format('Y-m-d'),
                'due_date' => now()->format('Y-m-d'),
                'reference' => 'finvixy-test-'.now()->format('YmdHis'),
                'status' => 'DRAFT',
            ]);

            $this->info("Success! InvoiceID: {$invoiceId}");
            return self::SUCCESS;

        } catch (XeroReauthRequired $e) {
            $this->error('Xero token has expired. The user must reconnect via Settings → Connected accounts.');
            return self::FAILURE;

        } catch (XeroApiException $e) {
            $this->error("Xero API error: {$e->getMessage()}");
            $body = $e->getResponseBody();
            if (! empty($body)) {
                $this->line(json_encode($body, JSON_PRETTY_PRINT));
            }
            return self::FAILURE;

        } catch (\Exception $e) {
            $this->error("Unexpected error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
