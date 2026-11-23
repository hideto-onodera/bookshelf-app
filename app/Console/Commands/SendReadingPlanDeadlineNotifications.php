<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanDeadlineNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SendReadingPlanDeadlineNotifications extends Command
{
    protected $signature = 'reading-plans:send-deadline-notifications';

    protected $description = '読書計画の期限切れ判定と期限3日前・当日・3日後の通知を実行する';

    public function handle(): int
    {
        $today = Carbon::now('Asia/Tokyo')->startOfDay();

        ReadingPlan::query()
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', '<', $today->toDateString())
            ->update([
                'status' => ReadingPlanStatus::Expired->value,
            ]);

        $targets = [
            'before' => [
                'date' => $today->copy()->addDays(3)->toDateString(),
                'status' => ReadingPlanStatus::InProgress,
            ],
            'today' => [
                'date' => $today->toDateString(),
                'status' => ReadingPlanStatus::InProgress,
            ],
            'after' => [
                'date' => $today->copy()->subDays(3)->toDateString(),
                'status' => ReadingPlanStatus::Expired,
            ],
        ];

        $sentCount = 0;

        foreach ($targets as $timing => $target) {
            ReadingPlan::query()
                ->with(['user', 'book'])
                ->where('status', $target['status']->value)
                ->whereDate('target_date', $target['date'])
                ->chunkById(
                    100,
                    function ($readingPlans) use ($timing, &$sentCount): void {
                        foreach ($readingPlans as $readingPlan) {
                            DB::transaction(function () use (
                                $readingPlan,
                                $timing,
                                &$sentCount
                            ): void {
                                $alreadySent = DB::table('notifications')
                                    ->where(
                                        'notifiable_type',
                                        $readingPlan->user->getMorphClass()
                                    )
                                    ->where(
                                        'notifiable_id',
                                        $readingPlan->user_id
                                    )
                                    ->where(
                                        'type',
                                        ReadingPlanDeadlineNotification::class
                                    )
                                    ->where(
                                        'data->reading_plan_id',
                                        $readingPlan->id
                                    )
                                    ->where(
                                        'data->timing',
                                        $timing
                                    )
                                    ->exists();

                                if ($alreadySent) {
                                    return;
                                }

                                $readingPlan->user->notify(
                                    new ReadingPlanDeadlineNotification(
                                        $readingPlan,
                                        $timing
                                    )
                                );

                                $sentCount++;
                            });
                        }
                    }
                );
        }

        $this->info("送信した通知件数: {$sentCount}");

        return self::SUCCESS;
    }
}
