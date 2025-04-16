<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    /** @use HasFactory<\Database\Factories\WalletFactory> */
    use HasFactory;

    protected $fillable = [
        'amount',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hasFunds($amount)
    {
        if (!$this->amount || $this->amount <= 0) {
            return false;
        }

        return !$amount || $this->amount >= $amount;
    }
}
