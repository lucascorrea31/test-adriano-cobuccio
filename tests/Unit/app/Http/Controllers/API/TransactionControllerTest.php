<?php

namespace Tests\Unit\App\Http\Controllers\API;

use App\Http\Controllers\API\TransactionController;
use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var User */
    private $user;
    /** @var TransactionController */
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new TransactionController();

        /** @var User $user */
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_deposit()
    {
        $amount = 100;
        $request = new TransactionRequest([
            'amount' => $amount
        ]);

        $response = $this->controller->deposit($request);

        $this->assertEquals($response->status(), 200);
        $this->assertArrayHasKey('message', $response->getData(true));
        $this->assertArrayHasKey('success', $response->getData(true));
        $this->assertTrue($response->getData(true)['success']);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'value' => $amount,
            'type' => 'deposit'
        ]);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'amount' => $amount
        ]);
        $this->assertEquals($amount, $this->user->wallet->amount);
    }

    public function test_can_withdraw()
    {
        $amount = 100;
        $this->user->wallet->amount = $amount;
        $this->user->wallet->save();

        $request = new TransactionRequest([
            'amount' => $amount
        ]);

        $response = $this->controller->withdraw($request);

        $this->assertEquals($response->status(), 200);
        $this->assertArrayHasKey('message', $response->getData(true));
        $this->assertArrayHasKey('success', $response->getData(true));
        $this->assertTrue($response->getData(true)['success']);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'value' => $amount,
            'type' => 'withdrawal'
        ]);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'amount' => 0
        ]);
        $this->assertEquals(0, $this->user->wallet->amount);
    }

    public function test_can_transfer()
    {
        $amount = 100;
        $receiver = User::factory()->create();
        $this->user->wallet->amount = $amount;
        $this->user->wallet->save();

        $receiver->wallet->amount = 0;
        $receiver->wallet->save();

        $request = new TransactionRequest([
            'amount' => $amount,
            'receiver_id' => $receiver->id
        ]);

        $response = $this->controller->transfer($request);

        $receiver->wallet->refresh();

        $this->assertEquals($response->status(), 200);
        $this->assertArrayHasKey('message', $response->getData(true));
        $this->assertArrayHasKey('success', $response->getData(true));
        $this->assertTrue($response->getData(true)['success']);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'value' => $amount,
            'type' => 'transfer',
            'receiver_id' => $receiver->id,
        ]);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'amount' => 0,
        ]);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $receiver->id,
            'amount' => $amount,
        ]);
        $this->assertEquals(0, $this->user->wallet->amount);
        $this->assertEquals($amount, $receiver->wallet->amount);
    }
}
