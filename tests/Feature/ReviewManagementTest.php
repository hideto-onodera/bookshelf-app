<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ReviewManagementTest extends TestCase
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

        if (!empty(config('database.connections.mysql.url'))) {
            throw new RuntimeException('Database URL must be empty.');
        }

        $actualDatabase = DB::selectOne(
            'SELECT DATABASE() AS database_name'
        )->database_name;

        if ($actualDatabase !== 'testing') {
            throw new RuntimeException('Unexpected database connection.');
        }
    }

    private function createReview(User $owner): Review
    {
        $book = Book::create([
            'title' => 'レビューテスト用書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000002',
            'published_date' => '2026-01-01',
            'user_id' => $owner->id,
        ]);

        return Review::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '変更前のコメント',
        ]);
    }

    public function test_owner_can_open_review_edit_page(): void
    {
        $owner = User::factory()->create();
        $review = $this->createReview($owner);

        $response = $this->actingAs($owner)
            ->get(route('reviews.edit', $review));

        $response->assertOk();
        $response->assertViewIs('reviews.edit');
    }

    public function test_owner_can_update_review(): void
    {
        $owner = User::factory()->create();
        $review = $this->createReview($owner);

        $response = $this->actingAs($owner)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '更新後のコメント',
            ]);

        $response->assertRedirect(
            route('books.show', $review->book_id)
        );

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '更新後のコメント',
        ]);
    }

    public function test_other_user_cannot_edit_or_update_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = $this->createReview($owner);

        $this->actingAs($otherUser)
            ->get(route('reviews.edit', $review))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '不正な更新',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '変更前のコメント',
        ]);
    }

    public function test_owner_can_delete_review(): void
    {
        $owner = User::factory()->create();
        $review = $this->createReview($owner);

        $response = $this->actingAs($owner)
            ->delete(route('reviews.destroy', $review));

        $response->assertRedirect(
            route('books.show', $review->book_id)
        );

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_other_user_cannot_delete_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = $this->createReview($owner);

        $this->actingAs($otherUser)
            ->delete(route('reviews.destroy', $review))
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_guest_cannot_edit_update_or_delete_review(): void
    {
        $owner = User::factory()->create();
        $review = $this->createReview($owner);

        $this->get(route('reviews.edit', $review))
            ->assertRedirect(route('login'));

        $this->put(route('reviews.update', $review), [
            'rating' => 5,
            'comment' => '未ログイン更新',
        ])->assertRedirect(route('login'));

        $this->delete(route('reviews.destroy', $review))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
        ]);
    }

    public function test_review_update_rejects_invalid_rating(): void
    {
        $owner = User::factory()->create();
        $review = $this->createReview($owner);

        $this->actingAs($owner)
            ->from(route('reviews.edit', $review))
            ->put(route('reviews.update', $review), [
                'rating' => 6,
                'comment' => '不正な評価値',
            ])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '変更前のコメント',
        ]);
    }
}
