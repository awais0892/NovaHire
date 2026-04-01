<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('broadcast channel is removed when realtime notifications are disabled', function () {
    config(['recruitment.realtime_notifications.broadcast_enabled' => false]);

    $user = User::factory()->create([
        'status' => 'active',
        'notification_preferences' => [
            'application_status_changed' => [
                'database' => true,
                'mail' => true,
                'broadcast' => true,
            ],
        ],
    ]);

    $channels = $user->notificationChannelsFor('application_status_changed', ['database', 'broadcast']);

    expect($channels)->toContain('database');
    expect($channels)->toContain('mail');
    expect($channels)->not->toContain('broadcast');
});

test('broadcast channel is included when realtime notifications are enabled', function () {
    config(['recruitment.realtime_notifications.broadcast_enabled' => true]);

    $user = User::factory()->create([
        'status' => 'active',
        'notification_preferences' => [
            'application_status_changed' => [
                'database' => true,
                'mail' => false,
                'broadcast' => true,
            ],
        ],
    ]);

    $channels = $user->notificationChannelsFor('application_status_changed', ['database']);

    expect($channels)->toContain('database');
    expect($channels)->toContain('broadcast');
});

