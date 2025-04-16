<?php

namespace App\Models;

use App\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'value',
        'type',
        'receiver_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function isDeposit()
    {
        return $this->type === TransactionType::DEPOSIT->label();
    }

    public function isWithdrawal()
    {
        return $this->type === TransactionType::WITHDRAWAL->label();
    }

    public function isTransfer()
    {
        return $this->type === TransactionType::TRANSFER->label();
    }

    public function isLastTransaction()
    {
        return $this->id === Transaction::fromAuthUser()->get()->last()->id;
    }

    public function scopeFromAuthUser($query)
    {
        return $query->where('user_id', auth()->user()->id);
    }
}
