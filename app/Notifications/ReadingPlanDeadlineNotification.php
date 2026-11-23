<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanDeadlineNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ReadingPlan $readingPlan,
        private readonly string $timing
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->readingPlan->loadMissing('book');

        $message = match ($this->timing) {
            'before' => '読書計画の期限まであと3日です。',
            'today' => '読書計画の期限は本日です。',
            'after' => '読書計画の期限から3日が経過しました。',
            default => '読書計画の期限をご確認ください。',
        };

        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_id' => $this->readingPlan->book_id,
            'book_title' => $this->readingPlan->book->title,
            'target_date' => $this->readingPlan->target_date->format('Y-m-d'),
            'timing' => $this->timing,
            'message' => $message,
        ];
    }
}
