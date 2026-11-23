<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (
            config('app.env') !== 'testing'
            || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'testing'
            || DB::selectOne('SELECT DATABASE() AS db')->db !== 'testing'
        ) {
            $this->fail('テスト専用DB testing 以外への接続を検出しました。');
        }

        if (DB::transactionLevel() < 1) {
            $this->fail('テスト用トランザクションが開始されていません。');
        }
    }

    public function test_guest_cannot_view_notifications(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_mark_notification_as_read(): void
    {
        $this->post(route('notifications.read', Str::uuid()->toString()))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_view_own_notification(): void
    {
        $user = User::factory()->create();

        $this->createNotification($user, '本人だけの通知メッセージ');

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('本人だけの通知メッセージ')
            ->assertSee('既読にする');
    }

    public function test_user_cannot_view_another_users_notification(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createNotification($otherUser, '他人の秘密の通知メッセージ');

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('他人の秘密の通知メッセージ');
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notificationId = $this->createNotification($user, '既読テスト通知');

        $this->actingAs($user)
            ->post(route('notifications.read', $notificationId))
            ->assertRedirect(route('notifications.index'));

        $notification = $user->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();

        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $notificationId = $this->createNotification(
            $otherUser,
            '他人の未読通知'
        );

        $this->actingAs($user)
            ->post(route('notifications.read', $notificationId))
            ->assertNotFound();

        $notification = $otherUser->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();

        $this->assertNull($notification->read_at);
    }

    public function test_marking_already_read_notification_is_safe(): void
    {
        $user = User::factory()->create();
        $notificationId = $this->createNotification(
            $user,
            '既読済み通知',
            now()
        );

        $this->actingAs($user)
            ->post(route('notifications.read', $notificationId))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull(
            $user->notifications()
                ->whereKey($notificationId)
                ->firstOrFail()
                ->read_at
        );
    }

    private function createNotification(
        User $user,
        string $message,
        ?\DateTimeInterface $readAt = null
    ): string {
        $id = Str::uuid()->toString();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'App\\Notifications\\ReadingPlanDeadlineNotification',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->getKey(),
            'data' => json_encode([
                'reading_plan_id' => 1,
                'book_title' => '通知テスト用書籍',
                'target_date' => '2026-11-25',
                'timing' => 'before',
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE),
            'read_at' => $readAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
