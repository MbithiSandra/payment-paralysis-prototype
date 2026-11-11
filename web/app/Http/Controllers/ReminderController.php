<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\RiskAssessmentService;
use Illuminate\Support\Facades\Cache;

class ReminderController extends Controller
{
    public function __construct(private RiskAssessmentService $risk)
    {
    }

    public function index()
    {
        $sme      = auth()->user()->sme;
        $business = $sme->business_name ?? $sme->name ?? auth()->user()->name;
        $clients  = $sme->clients()->get()->keyBy('client_code');

        $invoices = Invoice::whereIn('client_code', $clients->keys())
            ->whereNull('payment_date')
            ->where('due_date', '<=', today()->addDays(7))
            ->orderBy('due_date')
            ->get();

        $rows = [];

        foreach ($invoices as $inv) {
            $client = $clients[$inv->client_code];

            $result = Cache::remember(
                "risk:{$client->client_code}:{$inv->id}:{$inv->updated_at?->timestamp}",
                now()->addMinutes(10),
                fn () => $this->risk->assess($client, $inv)
            );

            $tier = $result['tier'] ?? null;
            if (! in_array($tier, ['Medium', 'High'], true)) {
                continue;                           // low risk clients do not need chasing
            }

            $overdue = $inv->due_date->lt(today());
            $days    = (int) abs(today()->diffInDays($inv->due_date));
            $name    = $client->contact_person ?: $client->client_name;

            $rows[] = [
                'invoice'    => $inv,
                'client'     => $client,
                'tier'       => $tier,
                'confidence' => $result['confidence'],
                'overdue'    => $overdue,
                'days'       => $days,
                'english'    => $this->message($name, $business, $inv, $overdue),
                'phone'      => $this->phone($client->phone),
            ];
        }

        return view('reminders.index', compact('rows'));
    }

    /** The reminder text, polite before the due date and firmer once it has passed. */
    private function message(string $name, string $business, Invoice $inv, bool $overdue): string
    {
        $amount = 'KES ' . number_format($inv->amount, 2);
        $date   = $inv->due_date->format('d M Y');

        return $overdue
            ? "Hello {$name}, this is {$business}. Invoice {$inv->invoice_number} for {$amount} was due on {$date} and is still unpaid. Kindly let us know when to expect payment. Thank you."
            : "Hello {$name}, this is a friendly reminder from {$business} that invoice {$inv->invoice_number} for {$amount} is due on {$date}. Thank you for your continued business.";
    }

    /** 0712 345 678 becomes 254712345678 for WhatsApp. */
    private function phone(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);

        return match (true) {
            strlen($digits) === 10 && str_starts_with($digits, '0')    => '254' . substr($digits, 1),
            strlen($digits) === 12 && str_starts_with($digits, '254')  => $digits,
            strlen($digits) === 9  && in_array($digits[0], ['7', '1']) => '254' . $digits,
            default => null,
        };
    }
}