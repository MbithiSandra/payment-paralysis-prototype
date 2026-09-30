<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RiskAssessmentService
{
    public function assess(Client $client, Invoice $invoice): ?array
    {
        $payload = [
            'client' => [
                'sector' => $client->sector,
                'employees' => $client->employees,
                'years_in_operation' => $client->years_in_operation !== null
                    ? (float) $client->years_in_operation : null,
            ],
            'invoice' => [
                'amount' => (float) $invoice->amount,
                'issue_date' => $invoice->issue_date->toDateString(),
                'due_date' => $invoice->due_date->toDateString(),
            ],
            'history' => $this->historyFor($client, $invoice),
        ];

        try {
            $response = Http::timeout(config('services.ml.timeout'))
                ->acceptJson()
                ->post(config('services.ml.url') . '/assess', $payload);
        } catch (\Throwable $e) {
            Log::error('Risk service unreachable', ['error' => $e->getMessage()]);
            return null;
        }

        if ($response->failed()) {
            Log::error('Risk service returned an error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json();
    }

    private function historyFor(Client $client, Invoice $invoice): array
    {
        return Invoice::where('client_code', $client->client_code)
            ->where('id', '!=', $invoice->id)
            ->where('issue_date', '<', $invoice->issue_date)
            ->orderBy('issue_date')
            ->get()
            ->map(fn (Invoice $past) => [
                'amount' => (float) $past->amount,
                'issue_date' => $past->issue_date->toDateString(),
                'due_date' => $past->due_date->toDateString(),
                'payment_date' => $past->payment_date?->toDateString(),
            ])
            ->all();
    }
}