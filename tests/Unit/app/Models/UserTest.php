<?php

namespace Tests\Unit\app\Models;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_a_waller_when_the_user_was_created()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ];

        /** @var User $user */
        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userData['name'], $user->name);
        $this->assertEquals($userData['email'], $user->email);

        $wallet = $user->wallet;

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(0, $wallet->amount);
    }
}
