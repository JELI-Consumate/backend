<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\DevicePlatform;
use App\Listeners\RemoveDeadDeviceToken;
use App\Models\DeviceToken;
use App\Models\User;
use App\Notifications\InactivityReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationFailed;
use Kreait\Firebase\Exception\Messaging\InvalidArgument;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\SendReport;
use NotificationChannels\Fcm\FcmChannel;
use Tests\TestCase;

final class RemoveDeadDeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    private function failedEventFor(string $fcmToken, MessagingException $error): NotificationFailed
    {
        $target = MessageTarget::with(MessageTarget::TOKEN, $fcmToken);
        $report = SendReport::failure($target, $error);

        return new NotificationFailed(
            User::factory()->create(),
            new InactivityReminderNotification,
            FcmChannel::class,
            ['report' => $report],
        );
    }

    public function test_deletes_device_token_when_fcm_reports_unknown_token(): void
    {
        $token = DeviceToken::query()->create([
            'user_id' => User::factory()->create()->id,
            'fcm_token' => 'dead-token',
            'platform' => DevicePlatform::Android,
        ]);

        $event = $this->failedEventFor('dead-token', NotFound::becauseTokenNotFound('dead-token'));

        app(RemoveDeadDeviceToken::class)->handle($event);

        $this->assertDatabaseMissing('device_tokens', ['id' => $token->id]);
    }

    public function test_deletes_device_token_when_fcm_reports_invalid_token(): void
    {
        $token = DeviceToken::query()->create([
            'user_id' => User::factory()->create()->id,
            'fcm_token' => 'invalid-token',
            'platform' => DevicePlatform::Ios,
        ]);

        $event = $this->failedEventFor('invalid-token', new InvalidArgument('The registration token is not a valid FCM registration token'));

        app(RemoveDeadDeviceToken::class)->handle($event);

        $this->assertDatabaseMissing('device_tokens', ['id' => $token->id]);
    }

    public function test_keeps_device_token_when_failure_is_unrelated_to_the_token(): void
    {
        $token = DeviceToken::query()->create([
            'user_id' => User::factory()->create()->id,
            'fcm_token' => 'still-alive-token',
            'platform' => DevicePlatform::Android,
        ]);

        $target = MessageTarget::with(MessageTarget::TOKEN, 'still-alive-token');
        $report = SendReport::failure($target, new class('server unavailable') extends \Exception implements MessagingException
        {
            public function errors(): array
            {
                return [];
            }
        });

        $event = new NotificationFailed(
            User::factory()->create(),
            new InactivityReminderNotification,
            FcmChannel::class,
            ['report' => $report],
        );

        app(RemoveDeadDeviceToken::class)->handle($event);

        $this->assertDatabaseHas('device_tokens', ['id' => $token->id]);
    }

    public function test_ignores_failures_from_channels_other_than_fcm(): void
    {
        $token = DeviceToken::query()->create([
            'user_id' => User::factory()->create()->id,
            'fcm_token' => 'some-token',
            'platform' => DevicePlatform::Android,
        ]);

        $event = new NotificationFailed(
            User::factory()->create(),
            new InactivityReminderNotification,
            'mail',
            [],
        );

        app(RemoveDeadDeviceToken::class)->handle($event);

        $this->assertDatabaseHas('device_tokens', ['id' => $token->id]);
    }
}
