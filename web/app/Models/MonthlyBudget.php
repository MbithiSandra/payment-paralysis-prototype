<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyBudget extends Model
{
    protected $fillable = ['kra_pin', 'month', 'revenue', 'expenses'];

    protected $casts = [
        'month'    => 'date',
        'revenue'  => 'decimal:2',
        'expenses' => 'decimal:2',
    ];

    public function sme()
    {
        return $this->belongsTo(Sme::class, 'kra_pin', 'kra_pin');
    }

    public function getNetAttribute()
    {
        return $this->revenue - $this->expenses;
    }
}