<?php

namespace Tests\Feature\App\Http\Controllers\API;

use App\Http\Controllers\API\TransactionController;
use App\Models\User;
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

        $this->controller = new TransactionController;

        /** @var User $user */
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_deposit()
    {
        $response = $this->post('/api/transactions/deposit', [
            'amount' => 100,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Deposit successful.',
        ]);
    }

    public function test_cannot_deposit_negative_amount()
    {
        $this->expectExceptionCode(0);

        $response = $this->post('/api/transactions/deposit', [
            'amount' => -100,
        ]);

        $response->assertStatus(302);
        $response->assertJson([
            'success' => false,
            'message' => 'The amount must be at least 0.01.',
        ]);
    }

    public function test_cannot_deposit_zero_amount()
    {
        $this->expectExceptionCode(0);

        $response = $this->post('/api/transactions/deposit', [
            'amount' => 0,
        ]);

        $response->assertStatus(302);
        $response->assertJson([
            'success' => false,
            'message' => 'The amount must be at least 0.01.',
        ]);
    }

    public function test_can_withdraw()
    {
        $this->user->wallet()->update(['amount' => 100]);

        $response = $this->post('/api/transactions/withdraw', [
            'amount' => 50,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Withdrawal successful.',
        ]);
    }

    public function test_cannot_withdraw_negative_amount()
    {
        $this->expectExceptionCode(0);

        $response = $this->post('/api/transactions/withdraw', [
            'amount' => -100,
        ]);

        $response->assertStatus(302);
        $response->assertJson([
            'success' => false,
            'message' => 'The amount must be at least 0.01.',
        ]);
    }

    public function test_cannot_withdraw_zero_amount()
    {
        $this->expectExceptionCode(0);

        $response = $this->post('/api/transactions/withdraw', [
            'amount' => 0,
        ]);

        $response->assertStatus(302);
        $response->assertJson([
            'success' => false,
            'message' => 'The amount must be at least 0.01.',
        ]);
    }

    public function test_cannot_withdraw_insufficient_funds()
    {
        $response = $this->post('/api/transactions/withdraw', [
            'amount' => 50,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Insufficient funds.',
        ]);
    }

    public function test_can_transfer()
    {
        $this->user->wallet()->update(['amount' => 100]);

        $receiver = User::factory()->create();
        $receiver->wallet()->update(['amount' => 10]);
        $receiver->save();

        $response = $this->post('/api/transactions/transfer', [
            'amount' => 30,
            'receiver_id' => $receiver->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Transfer successful.',
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'value' => 30,
            'type' => 'transfer',
            'receiver_id' => $receiver->id,
        ]);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'amount' => 70,
        ]);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $receiver->id,
            'amount' => 40,
        ]);
    }

    public function test_cannot_transfer_negative_amount()
    {
        $this->expectExceptionCode(0);

        $response = $this->post('/api/transactions/transfer', [
            'amount' => -100,
        ]);

        $response->assertStatus(302);
        $response->assertJson([
            'success' => false,
            'message' => 'The amount must be at least 0.01.',
        ]);
    }

    public function test_cannot_transfer_zero_amount()
    {
        $this->expectExceptionCode(0);

        $response = $this->post('/api/transactions/transfer', [
            'amount' => 0,
        ]);

        $response->assertStatus(302);
        $response->assertJson([
            'success' => false,
            'message' => 'The amount must be at least 0.01.',
        ]);
    }

    public function test_cannot_transfer_insufficient_funds()
    {
        $response = $this->post('/api/transactions/transfer', [
            'amount' => 100,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Insufficient funds.',
        ]);
    }

    public function test_cannot_transfer_to_itself()
    {
        $this->user->wallet()->update(['amount' => 100]);

        $response = $this->post('/api/transactions/transfer', [
            'amount' => 50,
            'receiver_id' => $this->user->id,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'You cannot transfer money to yourself.',
        ]);
        $this->assertDatabaseMissing('transactions', [
            'user_id' => $this->user->id,
            'value' => 50,
            'type' => 'transfer',
            'receiver_id' => $this->user->id,
        ]);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'amount' => 100,
        ]);
    }
}
