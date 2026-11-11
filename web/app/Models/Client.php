<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $primaryKey = 'client_code';
    protected $keyType = 'string';
    public $incrementing = false;

  
       /** The only sectors allowed. They match the names the risk service understands. */
       public const SECTORS = [
           'Agriculture', 'Mining and quarrying', 'Utilities', 'Construction', 'Manufacturing',
           'Wholesale', 'Retail', 'Transport and logistics', 'Information and technology',
           'Financial services', 'Real estate', 'Professional services',
           'Administrative and support services', 'Education', 'Health services',
           'Arts and recreation', 'Hospitality', 'Other services',
       ];
protected $fillable = [
    'client_code', 'kra_pin', 'client_name', 'client_kra_pin',
    'contact_person', 'phone', 'email', 'sector',
    'relationship_start_date', 'employees', 'years_in_operation',
];

    protected $casts = [
        'relationship_start_date' => 'date',
        'client_kra_pin'          => 'encrypted',
        'contact_person'          => 'encrypted',
        'phone'                   => 'encrypted',
        'email'                   => 'encrypted',
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