<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class FavoriteIndexTest extends TestCase
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

    private function createBook(User $owner, int $number): Book
    {
        return Book::create([
            'title' => 'お気に入りテスト書籍' . $number,
            'author' => 'テスト著者',
            'isbn' => sprintf('978400000%04d', $number),
            'published_date' => '2026-01-01',
            'description' => 'お気に入り一覧テスト用の説明',
            'user_id' => $owner->id,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('favorites.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_empty_favorites(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertViewIs('favorites.index')
            ->assertSee('お気に入りに登録された書籍はありません。');
    }

    public function test_only_authenticated_users_favorite_books_are_displayed(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $myBook = $this->createBook($user, 1);
        $otherBook = $this->createBook($otherUser, 2);

        $user->favoriteBooks()->attach($myBook->id);
        $otherUser->favoriteBooks()->attach($otherBook->id);

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee($myBook->title)
            ->assertDontSee($otherBook->title);
    }

    public function test_favorites_are_paginated_by_ten_books(): void
    {
        $user = User::factory()->create();

        for ($number = 1; $number <= 11; $number++) {
            $book = $this->createBook($user, $number);
            $user->favoriteBooks()->attach($book->id);
        }

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertViewHas('books', function ($paginatedBooks) {
                return $paginatedBooks->perPage() === 10
                    && $paginatedBooks->total() === 11
                    && $paginatedBooks->count() === 10;
            });

        $this->actingAs($user)
            ->get(route('favorites.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('books', function ($paginatedBooks) {
                return $paginatedBooks->currentPage() === 2
                    && $paginatedBooks->count() === 1;
            });
    }
}
