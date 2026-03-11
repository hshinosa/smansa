<?php

namespace Tests\Unit\Models;

use App\Models\InstagramBotAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstagramBotAccountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating an InstagramBotAccount
     */
    public function test_can_create_instagram_bot_account(): void
    {
        $account = InstagramBotAccount::create([
            'username' => 'test_user',
            'password' => 'secret_password',
            'is_active' => true,
            'notes' => 'Test account notes',
        ]);

        $this->assertDatabaseHas('sc_bot_accounts', [
            'username' => 'test_user',
            'is_active' => true,
        ]);

        $this->assertEquals('test_user', $account->username);
        $this->assertEquals('secret_password', $account->password);
        $this->assertTrue($account->is_active);
    }

    /**
     * Test active scope filters only active accounts
     */
    public function test_active_scope_filters_active_accounts(): void
    {
        InstagramBotAccount::create([
            'username' => 'active_user',
            'password' => 'password',
            'is_active' => true,
        ]);

        InstagramBotAccount::create([
            'username' => 'inactive_user',
            'password' => 'password',
            'is_active' => false,
        ]);

        $activeAccounts = InstagramBotAccount::active()->get();

        $this->assertCount(1, $activeAccounts);
        $this->assertEquals('active_user', $activeAccounts->first()->username);
    }

    /**
     * Test getMaskedPasswordAttribute masks password correctly
     */
    public function test_masked_password_attribute(): void
    {
        $account = InstagramBotAccount::create([
            'username' => 'test_user',
            'password' => 'mySecretPassword123',
            'is_active' => true,
        ]);

        $masked = $account->masked_password;

        // Should show first 2 and last 2 chars with dots in between
        $this->assertStringStartsWith('my', $masked);
        $this->assertStringEndsWith('23', $masked);
        $this->assertStringContainsString('•', $masked);
        $this->assertGreaterThan(4, strlen($masked));
    }

    /**
     * Test getMaskedPasswordAttribute handles short passwords
     */
    public function test_masked_password_handles_short_passwords(): void
    {
        $account = InstagramBotAccount::create([
            'username' => 'test_user',
            'password' => 'ab',
            'is_active' => true,
        ]);

        $masked = $account->masked_password;

        // Short passwords should be all dots
        $this->assertEquals('••', $masked);
    }

    /**
     * Test getMaskedPasswordAttribute handles empty passwords
     */
    public function test_masked_password_handles_empty_password(): void
    {
        $account = InstagramBotAccount::create([
            'username' => 'test_user',
            'password' => '',
            'is_active' => true,
        ]);

        $masked = $account->masked_password;

        $this->assertEquals('••••••••', $masked);
    }

    /**
     * Test isConfigured returns false for placeholder credentials
     */
    public function test_is_configured_returns_false_for_placeholders(): void
    {
        $notConfigured = InstagramBotAccount::create([
            'username' => 'CHANGE_ME',
            'password' => 'CHANGE_ME',
            'is_active' => true,
        ]);

        $this->assertFalse($notConfigured->isConfigured());
    }

    /**
     * Test isConfigured returns true for valid credentials
     */
    public function test_is_configured_returns_true_for_valid_credentials(): void
    {
        $configured = InstagramBotAccount::create([
            'username' => 'real_username',
            'password' => 'real_password',
            'is_active' => true,
        ]);

        $this->assertTrue($configured->isConfigured());
    }

    /**
     * Test status badge attribute for configured active account
     */
    public function test_status_badge_for_configured_active_account(): void
    {
        $account = InstagramBotAccount::create([
            'username' => 'test_user',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->assertEquals('success', $account->status_badge);
        $this->assertEquals('Active', $account->status_text);
    }

    /**
     * Test status badge attribute for configured inactive account
     */
    public function test_status_badge_for_configured_inactive_account(): void
    {
        $account = InstagramBotAccount::create([
            'username' => 'test_user',
            'password' => 'password',
            'is_active' => false,
        ]);

        $this->assertEquals('danger', $account->status_badge);
        $this->assertEquals('Inactive', $account->status_text);
    }

    /**
     * Test status badge attribute for not configured account
     */
    public function test_status_badge_for_not_configured_account(): void
    {
        $account = InstagramBotAccount::create([
            'username' => 'CHANGE_ME',
            'password' => 'CHANGE_ME',
            'is_active' => true,
        ]);

        $this->assertEquals('warning', $account->status_badge);
        $this->assertEquals('Not Configured', $account->status_text);
    }

    /**
     * Test password is hidden from serialization
     */
    public function test_password_is_hidden_from_serialization(): void
    {
        $account = InstagramBotAccount::create([
            'username' => 'test_user',
            'password' => 'secret_password',
            'is_active' => true,
        ]);

        $serialized = $account->toArray();

        $this->assertArrayNotHasKey('password', $serialized);
    }

    /**
     * Test last_used_at is cast to datetime
     */
    public function test_last_used_at_is_cast_to_datetime(): void
    {
        $account = InstagramBotAccount::create([
            'username' => 'test_user',
            'password' => 'password',
            'is_active' => true,
            'last_used_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $account->last_used_at);
    }

    /**
     * Test updating account
     */
    public function test_can_update_account(): void
    {
        $account = InstagramBotAccount::create([
            'username' => 'old_username',
            'password' => 'old_password',
            'is_active' => false,
        ]);

        $account->update([
            'username' => 'new_username',
            'password' => 'new_password',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('sc_bot_accounts', [
            'id' => $account->id,
            'username' => 'new_username',
            'is_active' => true,
        ]);

        $fresh = $account->fresh();
        $this->assertEquals('new_username', $fresh->username);
        $this->assertEquals('new_password', $fresh->password);
        $this->assertTrue($fresh->is_active);
    }

    /**
     * Test soft delete behavior (if applicable)
     */
    public function test_can_delete_account(): void
    {
        $account = InstagramBotAccount::create([
            'username' => 'test_user',
            'password' => 'password',
            'is_active' => true,
        ]);

        $account->delete();

        $this->assertDatabaseMissing('sc_bot_accounts', [
            'id' => $account->id,
        ]);
    }

    /**
     * Test notes can be stored
     */
    public function test_can_store_notes(): void
    {
        $account = InstagramBotAccount::create([
            'username' => 'test_user',
            'password' => 'password',
            'is_active' => true,
            'notes' => 'This is a note about the account',
        ]);

        $this->assertEquals('This is a note about the account', $account->notes);
    }
}
