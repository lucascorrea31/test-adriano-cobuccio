<?php

namespace App;

enum TransactionType
{
    case DEPOSIT;
    case WITHDRAWAL;
    case TRANSFER;

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT => 'deposit',
            self::WITHDRAWAL => 'withdrawal',
            self::TRANSFER => 'transfer',
        };
    }
}
