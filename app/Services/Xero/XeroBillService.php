<?php

namespace App\Services\Xero;

use App\Models\XeroConnection;

class XeroBillService
{
    /**
     * Create a bill (ACCPAY invoice) in Xero, idempotent on Reference.
     *
     * Required keys in $data:
     *   vendor_name   string
     *   description   string
     *   unit_amount   float
     *   account_code  string
     *   date          string  Y-m-d
     *   due_date      string  Y-m-d
     *   reference     string  idempotency key (our internal expense id)
     *
     * Optional keys:
     *   quantity      float   (default 1)
     *   tax_type      string
     *   status        string  DRAFT | AUTHORISED (default DRAFT)
     *
     * @return string InvoiceID of the created or existing bill
     */
    public function createBill(XeroConnection $conn, array $data): string
    {
        $client = new XeroClient($conn);

        // Idempotency: check for an existing bill with this reference
        $existing = $client->get('Invoices', [
            'where' => 'Type=="ACCPAY" AND Reference=="'.addslashes($data['reference']).'"',
        ]);

        if (! empty($existing['Invoices'])) {
            return $existing['Invoices'][0]['InvoiceID'];
        }

        $lineItem = [
            'Description' => $data['description'],
            'Quantity' => $data['quantity'] ?? 1,
            'UnitAmount' => $data['unit_amount'],
            'AccountCode' => $data['account_code'],
        ];

        if (isset($data['tax_type'])) {
            $lineItem['TaxType'] = $data['tax_type'];
        }

        // TODO: attach project/tracking category here when project mapping is implemented
        // $lineItem['Tracking'] = [['Name' => '...', 'Option' => '...']];

        $payload = [
            'Invoices' => [
                [
                    'Type' => 'ACCPAY',
                    'Contact' => ['Name' => $data['vendor_name']],
                    'LineItems' => [$lineItem],
                    'Date' => $data['date'],
                    'DueDate' => $data['due_date'],
                    'Reference' => $data['reference'],
                    'Status' => $data['status'] ?? 'DRAFT',
                ],
            ],
        ];

        $response = $client->post('Invoices', $payload);

        return $response['Invoices'][0]['InvoiceID'];
    }
}
