<?php

use App\Models\Application;
use App\Models\Candidate;
use App\Models\EmailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('phase 7 load simulation command creates candidate load artifacts', function () {
    $this->artisan('recruitment:phase7:simulate-load', [
        '--candidates' => 5,
        '--chunk' => 2,
    ])->assertExitCode(0);

    expect(Candidate::query()->count())->toBe(5);
    expect(Application::query()->count())->toBe(5);
    expect(EmailLog::query()->count())->toBe(5);

    if (Schema::hasTable('notifications')) {
        expect((int) DB::table('notifications')->count())->toBe(5);
    }
});

