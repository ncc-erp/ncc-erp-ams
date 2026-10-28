<?php

namespace Tests\Feature\Upgrade;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Notification;

/**
 * The application has to keep sending its notifications by mail.
 */
class MailTest extends UpgradeTestCase
{
    public function testTheWelcomeNotificationIsSentWhenAUserIsCreated(): void
    {
        Notification::fake();

        $admin = $this->createSuperUser();

        $username = $this->unique('gate.mail.user');

        $response = $this->actingAs($admin)->post('/users', [
            'first_name' => 'Gate',
            'last_name' => 'Regression',
            'username' => $username,
            'email' => 'gate.mail.user@example.test',
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
            'activated' => 1,
            'email_user' => 1,
            'permission' => ['superuser' => '0'],
        ]);

        $response->assertRedirect();

        $created = User::where('username', $username)->first();

        $this->assertNotNull($created);

        Notification::assertSentTo(
            $created,
            WelcomeNotification::class,
            function ($notification, $channels, $user) {
                return in_array('mail', $channels, true);
            }
        );
    }

    public function testTheWelcomeNotificationUsesTheMailChannel(): void
    {
        $user = $this->createSuperUser();

        $notification = new WelcomeNotification([
            'email' => 'gate.mail.user@example.test',
            'username' => 'gate.mail.user',
            'first_name' => 'Gate',
            'last_name' => 'Regression',
            'password' => self::STRONG_PASSWORD,
        ]);

        $this->assertContains('mail', $notification->via($user));
    }
}
