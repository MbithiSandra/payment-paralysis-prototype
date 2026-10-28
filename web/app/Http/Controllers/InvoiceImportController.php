<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceImportController extends Controller
{
    private const REQUIRED = ['client_name', 'invoice_number', 'amount', 'issue_date', 'due_date'];
    private const COLUMNS  = ['client_name', 'sector', 'invoice_number', 'amount', 'issue_date', 'due_date', 'payment_date'];
    private const MAX_ROWS = 5000;

    private function sme()
    {
        return auth()->user()->sme;
    }

    public function create()
    {
        return view('invoices.import');
    }

    /** A ready made example file with dates that are always in the past. */
    public function template()
    {
        $d = fn (int $daysAgo) => now()->subDays($daysAgo)->toDateString();

        $rows = [
            self::COLUMNS,
            ['Mwangi Construction', 'Construction', 'INV-0101', '85000', $d(150), $d(120), $d(112)],
            ['Mwangi Construction', 'Construction', 'INV-0102', '42000', $d(110), $d(80), $d(83)],
            ['Achieng Wholesalers', 'Wholesale', 'INV-0201', '120000', $d(60), $d(30), ''],
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'invoice-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        // 1. read the file
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            return back()->with('error', 'The file is empty.');
        }

        $header = array_map(fn ($h) => Str::of((string) $h)
            ->replace("\xEF\xBB\xBF", '')->trim()->lower()->replace(' ', '_')->toString(), $header);

        $missing = array_diff(self::REQUIRED, $header);
        if ($missing) {
            fclose($handle);
            return back()->with('error', 'These columns are missing: ' . implode(', ', $missing)
                . '. Download the template to see the right layout.');
        }

        $rows = [];
        $line = 1;
        while (($cells = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;                                   // skip blank lines
            }
            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);
                return back()->with('error', 'The file has more than ' . self::MAX_ROWS . ' rows. Split it into smaller files.');
            }
            $cells = array_pad(array_slice($cells, 0, count($header)), count($header), '');
            $rows[] = ['line' => $line, 'data' => array_combine($header, $cells)];
        }
        fclose($handle);

        if (! $rows) {
            return back()->with('error', 'The file has a header but no invoice rows.');
        }

        // 2. what already exists for this business
        $sme     = $this->sme();
        $clients = $sme->clients()->get()->keyBy(fn ($c) => Str::lower(trim($c->client_name)));
        $names   = $clients->mapWithKeys(fn ($c, $key) => [$c->client_code => $key]);

        $taken = Invoice::whereIn('client_code', $names->keys())
            ->get(['client_code', 'invoice_number'])
            ->mapWithKeys(fn ($i) => [$names[$i->client_code] . '|' . Str::lower($i->invoice_number) => true]);

        // 3. check every row before saving anything
        $today  = today();
        $errors = [];
        $clean  = [];
        $seen   = [];

        foreach ($rows as $r) {
            $d   = array_map(fn ($v) => trim((string) $v), $r['data']);
            $n   = $r['line'];
            $bad = [];

            $name   = $d['client_name'];
            $number = $d['invoice_number'];

            if ($name === '')                 { $bad[] = 'client name is empty'; }
            elseif (mb_strlen($name) > 255)   { $bad[] = 'client name is too long'; }

            if ($number === '')               { $bad[] = 'invoice number is empty'; }
            elseif (mb_strlen($number) > 50)  { $bad[] = 'invoice number is too long'; }

            $amount = str_ireplace([',', ' ', 'KES', 'KSH'], '', $d['amount']);
            if (! is_numeric($amount) || (float) $amount <= 0) {
                $bad[] = 'amount must be a number above 0';
            }

            $issue = $this->date($d['issue_date']);
            if (! $issue)                     { $bad[] = 'issue date is not a valid date'; }
            elseif ($issue->gt($today))       { $bad[] = 'issue date is in the future'; }

            $due = $this->date($d['due_date']);
            if (! $due)                       { $bad[] = 'due date is not a valid date'; }
            elseif ($issue && $due->lt($issue)) { $bad[] = 'due date is before the issue date'; }

            $paid = null;
            if (($d['payment_date'] ?? '') !== '') {
                $paid = $this->date($d['payment_date']);
                if (! $paid)                      { $bad[] = 'payment date is not a valid date'; }
                elseif ($issue && $paid->lt($issue)) { $bad[] = 'payment date is before the issue date'; }
                elseif ($paid->gt($today))        { $bad[] = 'payment date is in the future'; }
            }

            if ($name !== '' && $number !== '') {
                $key = Str::lower($name) . '|' . Str::lower($number);
                if (isset($taken[$key]))      { $bad[] = 'this invoice number already exists for this client'; }
                elseif (isset($seen[$key]))   { $bad[] = 'same invoice number as row ' . $seen[$key]; }
                $seen[$key] = $n;
            }

            if ($bad) {
                $errors[] = "Row {$n}: " . implode(', ', $bad);
            } else {
                $clean[] = [
                    'name'   => $name,
                    'sector' => $d['sector'] ?? '',
                    'number' => $number,
                    'amount' => round((float) $amount, 2),
                    'issue'  => $issue->toDateString(),
                    'due'    => $due->toDateString(),
                    'paid'   => $paid?->toDateString(),
                ];
            }
        }

        if ($errors) {
            return back()
                ->with('import_errors', array_slice($errors, 0, 50))
                ->with('import_error_total', count($errors));
        }

        // 4. save everything together, or nothing at all
        $newClients = 0;

        DB::transaction(function () use ($clean, $sme, $clients, &$newClients) {
            foreach ($clean as $row) {
                $key    = Str::lower($row['name']);
                $client = $clients->get($key);

                if (! $client) {
                    $client = Client::create([
                        'client_code' => $this->nextClientCode(),
                        'kra_pin'     => $sme->kra_pin,
                        'client_name' => $row['name'],
                        'sector'      => $row['sector'] ?: null,
                    ]);
                    $clients->put($key, $client);
                    $newClients++;
                }

                Invoice::create([
                    'client_code'    => $client->client_code,
                    'invoice_number' => $row['number'],
                    'amount'         => $row['amount'],
                    'issue_date'     => $row['issue'],
                    'due_date'       => $row['due'],
                    'payment_date'   => $row['paid'],
                ]);
            }
        });

        return redirect()->route('invoices.index')->with('status',
            'Imported ' . count($clean) . ' ' . Str::plural('invoice', count($clean))
            . ' and added ' . $newClients . ' new ' . Str::plural('client', $newClients) . '.');
    }

    /** Accepts 2026-06-02, 02/06/2026 and 2/6/2026. */
    private function date(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'Y/m/d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date;
                }
            } catch (\Throwable $e) {
                // try the next format
            }
        }

        return null;
    }

    /** Same numbering as the client form: CL-0001, CL-0002 and so on. */
    private function nextClientCode(): string
    {
        $last = $this->sme()->clients()->orderByDesc('client_code')->first();
        $n    = $last ? ((int) substr($last->client_code, 3)) + 1 : 1;

        do {
            $code = 'CL-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while (Client::where('client_code', $code)->exists());

        return $code;
    }
}