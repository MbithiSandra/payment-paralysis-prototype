<?php

namespace App\Http\Controllers;

use App\Models\MonthlyBudget;
use Illuminate\Http\Request;

class MonthlyBudgetController extends Controller
{
    private function sme()
    {
        return auth()->user()->sme;
    }

    public function index()
    {
        $budgets = $this->sme()->monthlyBudgets()
            ->orderByDesc('month')
            ->paginate(24);

        return view('budgets.index', compact('budgets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'month'    => ['required', 'date_format:Y-m'],
            'revenue'  => ['required', 'numeric', 'min:0'],
            'expenses' => ['required', 'numeric', 'min:0'],
        ]);

        MonthlyBudget::updateOrCreate(
            [
                'kra_pin' => $this->sme()->kra_pin,
                'month'   => $data['month'] . '-01',
            ],
            [
                'revenue'  => $data['revenue'],
                'expenses' => $data['expenses'],
            ]
        );

        return redirect()->route('budgets.index')->with('status', 'Month saved.');
    }

    public function destroy(MonthlyBudget $budget)
    {
        abort_unless($budget->kra_pin === $this->sme()->kra_pin, 403);

        $budget->delete();

        return redirect()->route('budgets.index')->with('status', 'Month removed.');
    }
}