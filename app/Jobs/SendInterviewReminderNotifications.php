<?php

namespace App\Jobs;

use App\Models\Interview;
use App\Notifications\InterviewReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SendInterviewReminderNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = now();

        $this->send24HourReminders($now);
        $this->send1HourReminders($now);
    }

    private function send24HourReminders(Carbon $now): void
    {
        $from = $now->copy()->addHours(23)->addMinutes(50);
        $to = $now->copy()->addHours(24)->addMinutes(10);

        Interview::query()
            ->with(['application.candidate.user'])
            ->where('status', 'scheduled')
            ->whereNull('cancelled_at')
            ->whereNull('reminder_24h_sent_at')
            ->whereBetween('starts_at', [$from, $to])
            ->chunkById(100, function ($interviews) {
                foreach ($interviews as $interview) {
                    $user = $interview->application?->candidate?->user;
                    if ($user) {
                        try {
                            $user->notify(new InterviewReminder($interview, '24h'));
                            $interview->forceFill(['reminder_24h_sent_at' => now()])->save();
                        } catch (\Throwable $exception) {
                            logger()->warning('24h interview reminder notification failed.', [
                                'interview_id' => $interview->id,
                                'candidate_user_id' => $user->id,
                                'error' => $exception->getMessage(),
                            ]);
                        }
                    }
                }
            });
    }

    private function send1HourReminders(Carbon $now): void
    {
        $from = $now->copy()->addMinutes(50);
        $to = $now->copy()->addMinutes(70);

        Interview::query()
            ->with(['application.candidate.user'])
            ->where('status', 'scheduled')
            ->whereNull('cancelled_at')
            ->whereNull('reminder_1h_sent_at')
            ->whereBetween('starts_at', [$from, $to])
            ->chunkById(100, function ($interviews) {
                foreach ($interviews as $interview) {
                    $user = $interview->application?->candidate?->user;
                    if ($user) {
                        try {
                            $user->notify(new InterviewReminder($interview, '1h'));
                            $interview->forceFill(['reminder_1h_sent_at' => now()])->save();
                        } catch (\Throwable $exception) {
                            logger()->warning('1h interview reminder notification failed.', [
                                'interview_id' => $interview->id,
                                'candidate_user_id' => $user->id,
                                'error' => $exception->getMessage(),
                            ]);
                        }
                    }
                }
            });
    }
}
