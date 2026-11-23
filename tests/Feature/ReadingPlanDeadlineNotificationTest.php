<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReadingPlanDeadlineNotificationTest extends TestCase
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

        DB::table('notifications')->delete();
        ReadingPlan::query()->delete();

        Carbon::setTestNow(
            Carbon::parse('2026-11-22 20:00:00', 'Asia/Tokyo')
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_notifications_are_sent_on_three_expected_dates(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $before = $this->createPlan($user, $book, '2026-11-25');
        $today = $this->createPlan($user, $book, '2026-11-22');
        $after = $this->createPlan($user, $book, '2026-11-19');

        $this->artisan('reading-plans:send-deadline-notifications')
            ->expectsOutput('送信した通知件数: 3')
            ->assertExitCode(0);

        $this->assertSame(3, $user->notifications()->count());

        $this->assertEqualsCanonicalizing(
            ['before', 'today', 'after'],
            $user->notifications()
                ->get()
                ->pluck('data')
                ->map(fn (array $data): string => $data['timing'])
                ->all()
        );

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $before->fresh()->status
        );

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $today->fresh()->status
        );

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $after->fresh()->status
        );
    }

    public function test_overdue_in_progress_plan_becomes_expired(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $plan = $this->createPlan($user, $book, '2026-11-21');

        $this->artisan('reading-plans:send-deadline-notifications')
            ->expectsOutput('送信した通知件数: 0')
            ->assertExitCode(0);

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->fresh()->status
        );

        $this->assertNull($plan->fresh()->completed_at);
    }

    public function test_expired_plan_receives_notification_three_days_after_deadline(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $plan = $this->createPlan(
            $user,
            $book,
            '2026-11-19',
            ReadingPlanStatus::Expired
        );

        $this->artisan('reading-plans:send-deadline-notifications')
            ->expectsOutput('送信した通知件数: 1')
            ->assertExitCode(0);

        $this->assertSame(1, $user->notifications()->count());

        $this->assertSame(
            'after',
            $user->notifications()->first()->data['timing']
        );

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->fresh()->status
        );
    }

    public function test_running_command_twice_does_not_duplicate_notifications(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $this->createPlan($user, $book, '2026-11-25');
        $this->createPlan($user, $book, '2026-11-19');

        $this->artisan('reading-plans:send-deadline-notifications')
            ->expectsOutput('送信した通知件数: 2')
            ->assertExitCode(0);

        $this->artisan('reading-plans:send-deadline-notifications')
            ->expectsOutput('送信した通知件数: 0')
            ->assertExitCode(0);

        $this->assertSame(2, $user->notifications()->count());
    }

    public function test_completed_plan_is_not_expired_or_notified(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $plan = $this->createPlan(
            $user,
            $book,
            '2026-11-19',
            ReadingPlanStatus::Completed
        );

        $this->artisan('reading-plans:send-deadline-notifications')
            ->expectsOutput('送信した通知件数: 0')
            ->assertExitCode(0);

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $plan->fresh()->status
        );

        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_unrelated_target_date_is_not_notified(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $this->createPlan($user, $book, '2026-12-01');

        $this->artisan('reading-plans:send-deadline-notifications')
            ->expectsOutput('送信した通知件数: 0')
            ->assertExitCode(0);

        $this->assertSame(0, $user->notifications()->count());
    }

    private function createBook(User $user): Book
    {
        return Book::create([
            'user_id' => $user->id,
            'title' => '通知テスト用書籍',
            'author' => 'テスト著者',
            'isbn' => null,
            'published_date' => null,
            'description' => '通知テスト用',
        ]);
    }

    private function createPlan(
        User $user,
        Book $book,
        string $targetDate,
        ReadingPlanStatus $status = ReadingPlanStatus::InProgress
    ): ReadingPlan {
        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => $status,
            'completed_at' => $status === ReadingPlanStatus::Completed
                ? now()
                : null,
        ]);
    }
}
