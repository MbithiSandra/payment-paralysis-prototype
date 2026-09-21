<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'client_code', 'invoice_number', 'amount',
        'issue_date', 'due_date', 'payment_date',
    ];

    protected $casts = [
        'issue_date'   => 'date',
        'due_date'     => 'date',
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_code', 'client_code');
    }

    public function getDaysLateAttribute(): ?int
    {
        if (! $this->payment_date) {
            return null;
        }

        return (int) round($this->due_date->diffInDays($this->payment_date, false));
    }

    public function getIsLateAttribute(): ?bool
    {
        $days = $this->days_late;

        return $days === null ? null : $days > 0;
    }

    public function getStatusAttribute(): string
    {
        if (! $this->payment_date) {
            return $this->due_date->isPast() ? 'Overdue' : 'Outstanding';
        }

        return $this->is_late ? 'Paid late' : 'Paid on time';
    }
}