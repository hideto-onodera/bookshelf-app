<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class FavoriteToggleTest extends TestCase
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

    private function createBook(User $owner, string $isbn): Book
    {
        return Book::create([
            'title' => 'お気に入り切替テスト書籍',
            'author' => 'テスト著者',
            'isbn' => $isbn,
            'published_date' => '2026-01-01',
            'user_id' => $owner->id,
        ]);
    }

    public function test_guest_cannot_add_favorite(): void
    {
        $owner = User::factory()->create();
        $book = $this->createBook($owner, '9784000000101');

        $this->post(route('favorites.toggle', $book))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);
    }

    public function test_authenticated_user_can_add_favorite(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user, '9784000000102');

        $this->actingAs($user)
            ->post(route('favorites.toggle', $book))
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_authenticated_user_can_remove_favorite(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user, '9784000000103');

        $user->favoriteBooks()->attach($book->id);

        $this->actingAs($user)
            ->post(route('favorites.toggle', $book))
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_toggle_does_not_affect_other_users_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = $this->createBook($otherUser, '9784000000104');

        $otherUser->favoriteBooks()->attach($book->id);

        $this->actingAs($user)
            ->post(route('favorites.toggle', $book))
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
        ]);
    }
}
