<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BookIndexTest extends TestCase
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
            'title' => '書籍一覧テスト書籍' . $number,
            'author' => '書籍一覧テスト著者',
            'isbn' => sprintf('978400000%04d', $number),
            'published_date' => '2026-01-01',
            'description' => '書籍一覧テスト用の説明',
            'user_id' => $owner->id,
        ]);
    }

    public function test_guest_can_view_book_index(): void
    {
        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewIs('books.index');
    }

    public function test_book_index_displays_book_information_and_genre(): void
    {
        $owner = User::factory()->create();

        $book = $this->createBook($owner, 1);

        $genre = Genre::create([
            'name' => '書籍一覧テストジャンル',
        ]);

        $book->genres()->attach($genre->id);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee($book->title)
            ->assertSee($book->author)
            ->assertSee($genre->name);
    }

    public function test_book_index_is_paginated_by_ten_books(): void
    {
        $owner = User::factory()->create();

        for ($number = 1; $number <= 11; $number++) {
            $this->createBook($owner, $number);
        }

        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewHas('books', function ($books) {
                return $books->perPage() === 10
                    && $books->total() === 11
                    && $books->count() === 10;
            });

        $this->get(route('books.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('books', function ($books) {
                return $books->currentPage() === 2
                    && $books->count() === 1;
            });
    }
}
