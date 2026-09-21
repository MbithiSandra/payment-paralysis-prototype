<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sme extends Model
{
    protected $primaryKey = 'kra_pin';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['kra_pin', 'user_id', 'business_name', 'sector', 'phone'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'kra_pin', 'kra_pin');
    }

    public function monthlyBudgets()
    {
        return $this->hasMany(MonthlyBudget::class, 'kra_pin', 'kra_pin');
    }
}