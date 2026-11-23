<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('app.env') !== 'testing') {
            throw new RuntimeException('Testing environment is required.');
        }

        if (config('database.default') !== 'mysql') {
            throw new RuntimeException('MySQL connection is required.');
        }

        if (config('database.connections.mysql.database') !== 'testing') {
            throw new RuntimeException('Testing database is required.');
        }

        if (! empty(config('database.connections.mysql.url'))) {
            throw new RuntimeException('Database URL must be empty.');
        }

        $actualDatabase = DB::selectOne(
            'SELECT DATABASE() AS database_name'
        )->database_name;

        if ($actualDatabase !== 'testing') {
            throw new RuntimeException('Unexpected database connection.');
        }
    }

    private function createBook(User $owner): Book
    {
        return Book::create([
            'title' => '読書計画テスト書籍',
            'author' => 'テスト著者',
            'isbn' => null,
            'published_date' => null,
            'user_id' => $owner->id,
        ]);
    }

    private function createPlan(
        User $user,
        Book $book,
        ReadingPlanStatus $status = ReadingPlanStatus::InProgress
    ): ReadingPlan {
        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-12-20',
            'status' => $status,
            'completed_at' => $status === ReadingPlanStatus::Completed
                ? now()
                : null,
        ]);
    }

    public function test_guest_cannot_access_reading_plans(): void
    {
        $this->get(route('reading-plans.index'))
            ->assertRedirect(route('login'));

        $this->get(route('reading-plans.create'))
            ->assertRedirect(route('login'));

        $this->post(route('reading-plans.store'), [
            'book_id' => 1,
            'target_date' => '2026-12-20',
        ])->assertRedirect(route('login'));
    }

    public function test_user_can_open_reading_plan_pages(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $plan = $this->createPlan($user, $book);

        $this->actingAs($user)
            ->get(route('reading-plans.index'))
            ->assertOk()
            ->assertViewIs('reading-plans.index')
            ->assertSee($book->title);

        $this->get(route('reading-plans.create'))
            ->assertOk()
            ->assertViewIs('reading-plans.create')
            ->assertSee($book->title);

        $this->get(route('reading-plans.edit', $plan))
            ->assertOk()
            ->assertViewIs('reading-plans.edit')
            ->assertSee($book->title);
    }

    public function test_user_can_create_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '2026-12-20',
                'user_id' => 999999,
                'status' => ReadingPlanStatus::Completed->value,
            ])
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-12-20',
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    public function test_duplicate_in_progress_plan_is_rejected(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $this->createPlan($user, $book);

        $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '2026-12-25',
            ])
            ->assertSessionHasErrors('book_id');

        $this->assertSame(
            1,
            ReadingPlan::where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->count()
        );
    }

    public function test_same_book_can_have_plans_for_different_users(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner);
        $this->createPlan($owner, $book);

        $this->actingAs($otherUser)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '2026-12-25',
            ])
            ->assertRedirect(route('reading-plans.index'));

        $this->assertSame(
            2,
            ReadingPlan::where('book_id', $book->id)->count()
        );
    }

    public function test_user_can_filter_plans_by_status(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $this->createPlan($user, $book, ReadingPlanStatus::InProgress);
        $this->createPlan($user, $book, ReadingPlanStatus::Completed);

        $this->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => ReadingPlanStatus::Completed->value,
            ]))
            ->assertOk()
            ->assertViewHas('readingPlans', function ($plans): bool {
                return $plans->count() === 1
                    && $plans->first()->status === ReadingPlanStatus::Completed;
            });
    }

    public function test_user_cannot_view_other_users_plans(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner);
        $plan = $this->createPlan($owner, $book);

        $this->actingAs($otherUser)
            ->get(route('reading-plans.index'))
            ->assertOk()
            ->assertViewHas('readingPlans', function ($plans): bool {
                return $plans->count() === 0;
            });

        $this->get(route('reading-plans.edit', $plan))
            ->assertForbidden();
    }

    public function test_owner_can_update_target_date(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $plan = $this->createPlan($user, $book);

        $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '2027-01-10',
            ])
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => '2027-01-10',
        ]);
    }

    public function test_expired_plan_can_be_reactivated(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $plan = $this->createPlan(
            $user,
            $book,
            ReadingPlanStatus::Expired
        );

        $futureDate = now()->addDays(10)->toDateString();

        $this->actingAs($user)
            ->get(route('reading-plans.edit', $plan))
            ->assertOk();

        $this->put(route('reading-plans.update', $plan), [
            'target_date' => $futureDate,
        ])->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $futureDate,
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    public function test_expired_plan_cannot_reactivate_when_active_plan_exists(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $expiredPlan = $this->createPlan(
            $user,
            $book,
            ReadingPlanStatus::Expired
        );

        $activePlan = $this->createPlan($user, $book);
        $futureDate = now()->addDays(10)->toDateString();

        $this->actingAs($user)
            ->put(route('reading-plans.update', $expiredPlan), [
                'target_date' => $futureDate,
            ])
            ->assertSessionHasErrors('target_date');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $expiredPlan->id,
            'status' => ReadingPlanStatus::Expired->value,
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $activePlan->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_other_user_cannot_update_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner);
        $plan = $this->createPlan($owner, $book);

        $this->actingAs($otherUser)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '2027-01-10',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => '2026-12-20',
        ]);
    }

    public function test_owner_can_complete_plan(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $plan = $this->createPlan($user, $book);

        $this->actingAs($user)
            ->post(route('reading-plans.complete', $plan))
            ->assertRedirect(route('reading-plans.index'));

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $plan->status
        );

        $this->assertNotNull($plan->completed_at);
    }

    public function test_completed_plan_cannot_be_updated_or_completed_again(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $plan = $this->createPlan(
            $user,
            $book,
            ReadingPlanStatus::Completed
        );

        $this->actingAs($user)
            ->get(route('reading-plans.edit', $plan))
            ->assertForbidden();

        $this->put(route('reading-plans.update', $plan), [
            'target_date' => '2027-01-10',
        ])->assertForbidden();

        $this->post(route('reading-plans.complete', $plan))
            ->assertForbidden();
    }

    public function test_owner_can_delete_completed_plan(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $plan = $this->createPlan(
            $user,
            $book,
            ReadingPlanStatus::Completed
        );

        $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan))
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_deleting_plan_removes_only_its_deadline_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($user);
        $plan = $this->createPlan($user, $book);
        $otherPlan = $this->createPlan($user, $book);

        $makeNotification = function (
            User $recipient,
            string $type,
            int $planId
        ): string {
            $id = (string) \Illuminate\Support\Str::uuid();

            DB::table('notifications')->insert([
                'id' => $id,
                'type' => $type,
                'notifiable_type' => User::class,
                'notifiable_id' => $recipient->id,
                'data' => json_encode([
                    'reading_plan_id' => $planId,
                    'timing' => 'today',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $id;
        };

        $deadlineType = \App\Notifications\ReadingPlanDeadlineNotification::class;

        $deletedId = $makeNotification($user, $deadlineType, $plan->id);
        $otherPlanId = $makeNotification($user, $deadlineType, $otherPlan->id);
        $otherTypeId = $makeNotification($user, 'App\Notifications\OtherNotification', $plan->id);
        $otherUserId = $makeNotification($otherUser, $deadlineType, $plan->id);

        $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan))
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $deletedId,
        ]);

        foreach ([$otherPlanId, $otherTypeId, $otherUserId] as $id) {
            $this->assertDatabaseHas('notifications', [
                'id' => $id,
            ]);
        }
    }

    public function test_other_user_cannot_complete_or_delete_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner);
        $plan = $this->createPlan($owner, $book);

        $this->actingAs($otherUser)
            ->post(route('reading-plans.complete', $plan))
            ->assertForbidden();

        $this->delete(route('reading-plans.destroy', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_past_target_date_is_rejected_on_create(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $pastDate = now()->subDays(1)->toDateString();

        $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => $pastDate,
            ])
            ->assertSessionHasErrors('target_date');

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_past_target_date_is_rejected_on_update(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $plan = $this->createPlan($user, $book);
        $originalDate = $plan->target_date->toDateString();
        $pastDate = now()->subDays(1)->toDateString();

        $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => $pastDate,
            ])
            ->assertSessionHasErrors('target_date');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $originalDate,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_invalid_plan_data_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => 999999999,
                'target_date' => 'invalid-date',
            ])
            ->assertSessionHasErrors(['book_id', 'target_date']);
    }
}
