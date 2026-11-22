<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ReviewInteractionTest extends TestCase
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
            'title' => 'レビュー投稿テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000099',
            'published_date' => '2026-01-01',
            'user_id' => $owner->id,
        ]);
    }

    public function test_guest_cannot_post_review(): void
    {
        $owner = User::factory()->create();
        $book = $this->createBook($owner);

        $this->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => '未ログイン投稿',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'comment' => '未ログイン投稿',
        ]);
    }

    public function test_authenticated_user_can_post_review(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $book = $this->createBook($owner);

        $this->actingAs($reviewer)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => '投稿テストの感想',
            ])
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('reviews', [
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '投稿テストの感想',
        ]);
    }

    public function test_review_post_rejects_invalid_rating(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $book = $this->createBook($owner);

        $this->actingAs($reviewer)
            ->post(route('reviews.store', $book), [
                'rating' => 6,
                'comment' => '不正な評価値',
            ])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
        ]);
    }

    public function test_review_cannot_be_posted_without_comment(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $book = $this->createBook($owner);

        $this->actingAs($reviewer)
            ->post(route('reviews.store', $book), [
                'rating' => 4,
            ])
            ->assertSessionHasErrors([
                'comment' => 'コメントを入力してください。',
            ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
        ]);
    }

    public function test_review_can_be_posted_with_1000_character_comment(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $book = $this->createBook($owner);
        $comment = str_repeat('あ', 1000);

        $this->actingAs($reviewer)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => $comment,
            ])
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', 'レビューを投稿しました。');

        $this->assertDatabaseHas('reviews', [
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => $comment,
        ]);
    }

    public function test_review_cannot_be_posted_with_1001_character_comment(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $book = $this->createBook($owner);

        $this->actingAs($reviewer)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => str_repeat('あ', 1001),
            ])
            ->assertSessionHasErrors([
                'comment' => 'コメントは1000文字以内で入力してください。',
            ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
        ]);
    }

    public function test_review_post_success_message_is_correct(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $book = $this->createBook($owner);

        $this->actingAs($reviewer)
            ->post(route('reviews.store', $book), [
                'rating' => 4,
                'comment' => '投稿成功メッセージ確認',
            ])
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', 'レビューを投稿しました。');
    }

    public function test_user_can_like_and_unlike_review(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $user = User::factory()->create();
        $book = $this->createBook($owner);

        $review = Review::create([
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'いいねテスト',
        ]);

        $this->actingAs($user)
            ->post(route('reviews.like', $review))
            ->assertRedirect(route('books.show', $book));

        $this->assertTrue(
            $user->likedReviews()->whereKey($review->id)->exists()
        );

        $this->actingAs($user)
            ->post(route('reviews.like', $review))
            ->assertRedirect(route('books.show', $book));

        $this->assertFalse(
            $user->likedReviews()->whereKey($review->id)->exists()
        );
    }
}
