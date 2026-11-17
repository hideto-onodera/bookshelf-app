<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BookDeletionTest extends TestCase
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

    private function createBook(User $user): Book
    {
        return Book::create([
            'title' => '削除テスト用書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-01-01',
            'description' => '削除テスト用の説明',
            'user_id' => $user->id,
        ]);
    }

    public function test_owner_can_delete_own_book(): void
    {
        $owner = User::factory()->create();
        $book = $this->createBook($owner);

        $response = $this->actingAs($owner)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner);

        $response = $this->actingAs($otherUser)
            ->delete(route('books.destroy', $book));

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_guest_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $book = $this->createBook($owner);

        $response = $this->delete(
            route('books.destroy', $book)
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_deleting_book_cascades_related_records(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = $this->createBook($owner);

        $genre = Genre::create([
            'name' => '削除テスト用ジャンル',
        ]);

        $book->genres()->attach($genre->id);

        $book->favoritedByUsers()->attach($otherUser->id);

        $review = Review::create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '削除テスト用レビュー',
        ]);

        $review->likedByUsers()->attach($owner->id);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'book_id' => $book->id,
            'user_id' => $otherUser->id,
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'review_id' => $review->id,
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
        ]);
    }
}
