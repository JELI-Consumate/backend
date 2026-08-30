<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\DevicePlatform;
use App\Models\DeviceToken;
use App\Models\User;
use App\Notifications\InactivityReminderNotification;
use App\Services\Notification\InactivityNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class InactivityNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function withDeviceToken(User $user): User
    {
        DeviceToken::query()->create([
            'user_id' => $user->id,
            'fcm_token' => 'token-'.$user->id,
            'platform' => DevicePlatform::Android,
        ]);

        return $user;
    }

    public function test_notifies_user_inactive_for_24_hours_or_more(): void
    {
        Notification::fake();

        $user = $this->withDeviceToken(User::factory()->create([
            'last_active_at' => now()->subHours(25),
        ]));

        app(InactivityNotificationService::class)->notifyInactiveUsers();

        Notification::assertSentTo($user, InactivityReminderNotification::class);
    }

    public function test_does_not_notify_user_active_within_the_last_24_hours(): void
    {
        Notification::fake();

        $user = $this->withDeviceToken(User::factory()->create([
            'last_active_at' => now()->subHours(23),
        ]));

        app(InactivityNotificationService::class)->notifyInactiveUsers();

        Notification::assertNotSentTo($user, InactivityReminderNotification::class);
    }

    public function test_does_not_notify_user_who_has_never_opened_the_app(): void
    {
        Notification::fake();

        $user = $this->withDeviceToken(User::factory()->create([
            'last_active_at' => null,
        ]));

        app(InactivityNotificationService::class)->notifyInactiveUsers();

        Notification::assertNotSentTo($user, InactivityReminderNotification::class);
    }

    public function test_does_not_notify_user_without_a_registered_device_token(): void
    {
        Notification::fake();

        $user = User::factory()->create(['last_active_at' => now()->subHours(25)]);

        app(InactivityNotificationService::class)->notifyInactiveUsers();

        Notification::assertNotSentTo($user, InactivityReminderNotification::class);
    }

    /**
     * Sesi inaktif yang sama tidak boleh dinotif berkali-kali tiap job hourly jalan.
     */
    public function test_does_not_double_notify_for_the_same_inactivity_session(): void
    {
        Notification::fake();

        $user = $this->withDeviceToken(User::factory()->create([
            'last_active_at' => now()->subHours(30),
            'last_inactive_notified_at' => now()->subHours(5),
        ]));

        app(InactivityNotificationService::class)->notifyInactiveUsers();

        Notification::assertNotSentTo($user, InactivityReminderNotification::class);
    }

    /**
     * User aktif lagi lalu inaktif lagi (sesi inaktif baru) -> boleh dinotif lagi,
     * meski last_inactive_notified_at dari sesi sebelumnya masih terisi.
     */
    public function test_notifies_again_after_a_new_inactivity_session(): void
    {
        Notification::fake();

        $user = $this->withDeviceToken(User::factory()->create([
            'last_active_at' => now()->subHours(25),
            'last_inactive_notified_at' => now()->subDays(3),
        ]));

        app(InactivityNotificationService::class)->notifyInactiveUsers();

        Notification::assertSentTo($user, InactivityReminderNotification::class);
    }

    /**
     * `last_inactive_notified_at` TIDAK diupdate langsung oleh service --
     * itu tanggung jawab App\Listeners\MarkInactivityReminderAsSent, dipicu
     * event NotificationSent setelah kirim FCM beneran sukses (lihat
     * tests/Unit/Listeners/MarkInactivityReminderAsSentTest.php). Kalau
     * diupdate di sini (sebelum tahu hasil kirim), user yang device-nya
     * mati akan salah ke-mark "sudah dinotif" walau notifnya gak nyampe.
     */
    public function test_does_not_update_last_inactive_notified_at_before_delivery_is_confirmed(): void
    {
        Notification::fake();

        $user = $this->withDeviceToken(User::factory()->create([
            'last_active_at' => now()->subHours(25),
        ]));

        app(InactivityNotificationService::class)->notifyInactiveUsers();

        $this->assertNull($user->fresh()->last_inactive_notified_at);
    }
}
