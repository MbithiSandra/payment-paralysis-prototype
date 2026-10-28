<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RiskAssessmentService
{
    /** Assess a real invoice that is already saved. */
    public function assess(Client $client, Invoice $invoice): ?array
    {
        return $this->send($client, [
            'amount'     => (float) $invoice->amount,
            'issue_date' => $invoice->issue_date->toDateString(),
            'due_date'   => $invoice->due_date->toDateString(),
        ], $this->historyFor($client, $invoice->issue_date, $invoice->id));
    }

    /** FR05: assess proposed credit terms without saving an invoice. */
    public function assessTerms(Client $client, float $amount, CarbonInterface $issue, int $termsDays): ?array
    {
        return $this->send($client, [
            'amount'     => $amount,
            'issue_date' => $issue->toDateString(),
            'due_date'   => $issue->copy()->addDays($termsDays)->toDateString(),
        ], $this->historyFor($client, $issue->copy()->addDay(), null));
    }

    private function send(Client $client, array $invoice, array $history): ?array
    {
        $payload = [
            'client' => [
                'sector' => $client->sector,
                'employees' => $client->employees,
                'years_in_operation' => $client->years_in_operation !== null
                    ? (float) $client->years_in_operation : null,
            ],
            'invoice' => $invoice,
            'history' => $history,
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

    /** The client's invoices issued before the given date. */
    private function historyFor(Client $client, CarbonInterface $before, ?int $excludeId): array
    {
        return Invoice::where('client_code', $client->client_code)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('issue_date', '<', $before->toDateString())
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