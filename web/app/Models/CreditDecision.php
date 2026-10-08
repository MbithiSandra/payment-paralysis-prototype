<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditDecision extends Model
{
    protected $fillable = [
        'user_id', 'invoice_id', 'client_code',
        'recommended_tier', 'probability_late', 'model_used', 'recommended_action',
        'decision', 'override_reason', 'followed_recommendation',
    ];

    protected $casts = [
        'probability_late'        => 'decimal:4',
        'followed_recommendation' => 'boolean',
    ];

    public const CHOICES = [
        'approve'                 => 'Approve on standard terms',
        'approve_with_conditions' => 'Approve with conditions',
        'decline'                 => 'Decline the credit',
    ];

    /** What the system would have had the owner do, given the tier. */
    public static function expectedFor(string $tier): string
    {
        return match ($tier) {
            'Low'    => 'approve',
            'Medium' => 'approve_with_conditions',
            'High'   => 'decline',
        };
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_code', 'client_code');
    }
}