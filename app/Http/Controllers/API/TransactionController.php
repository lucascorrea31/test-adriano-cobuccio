<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function deposit(TransactionRequest $request)
    {
        $user = auth()->user();
        $wallet = $user->wallet;

        $amount = $request->input('amount');

        $wallet->amount += $amount;

        $transaction = $user->transactions()->create([
            'value' => $amount,
            'type' => 'deposit',
        ]);

        $wallet->save();

        return response()->json([
            'success' => true,
            'message' => 'Deposit successful.',
            'transaction' => $transaction,
        ]);
    }

    public function withdraw(TransactionRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();
        $wallet = $user->wallet;

        $amount = $request->input('amount');

        if (!$wallet->hasFunds($amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient funds.',
            ], 400);
        }

        $wallet->amount -= $amount;

        $transaction = $user->transactions()->create([
            'value' => $amount,
            'type' => 'withdrawal',
        ]);

        $wallet->save();

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal successful.',
            'transaction' => $transaction,
        ]);
    }

    public function transfer(TransactionRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();
        $wallet = $user->wallet;

        $receiverId = $request->input('receiver_id');
        $amount = $request->input('amount');

        if ($user->id === $receiverId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot transfer money to yourself.',
            ], 400);
        }

        if (!$wallet->hasFunds($amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient funds.',
            ], 400);
        }

        /** @var User $receiver */
        $receiver = User::find($receiverId);
        $receiverWallet = $receiver->wallet;
        $receiverWallet->amount += $amount;

        $wallet->amount -= $amount;

        $transaction = $user->transactions()->create([
            'value' => $amount,
            'type' => 'transfer',
            'receiver_id' => $receiverId,
        ]);

        $receiverWallet->save();
        $wallet->save();

        return response()->json([
            'success' => true,
            'message' => 'Transfer successful.',
            'transaction' => $transaction,
        ]);
    }

    public function revokeTransaction(Request $request)
    {
        if (!$request->has('transaction_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction ID is required.'
            ], 400);
        }

        /** @var Transaction $transaction */
        $transaction = Transaction::find($request->input('transaction_id'));

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found or already revoked.'
            ], 404);
        }

        $user = $transaction->user;
        $wallet = $user->wallet;

        if (!$transaction->isLastTransaction()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot revoke this transaction.'
            ], 400);
        }

        if ($transaction->isDeposit()) {
            $wallet->amount -= $transaction->value;
        } elseif ($transaction->isWithdrawal()) {
            $wallet->amount += $transaction->value;
        } elseif ($transaction->isTransfer()) {
            $receiver = User::findOrFail($transaction->receiver_id);

            if (!$receiver->wallet->hasFunds($transaction->value)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction cannot be revoked due the receiver.'
                ], 400);
            }

            $receiver->wallet->amount -= $transaction->value;
            $receiver->wallet->save();

            $wallet->amount += $transaction->value;
        }

        $wallet->save();
        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction revoked successfully.',
        ]);
    }
}
