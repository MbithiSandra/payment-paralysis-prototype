<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $primaryKey = 'client_code';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_code', 'kra_pin', 'client_name', 'client_kra_pin',
        'contact_person', 'phone', 'email', 'sector', 'relationship_start_date',
    ];

    protected $casts = [
        'relationship_start_date' => 'date',
    ];

    public function sme()
    {
        return $this->belongsTo(Sme::class, 'kra_pin', 'kra_pin');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'client_code', 'client_code');
    }
}