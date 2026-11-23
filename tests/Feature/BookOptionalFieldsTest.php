<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class BookOptionalFieldsTest extends TestCase
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

    private function payload(Genre $genre): array
    {
        return [
            'title' => '任意項目テスト書籍',
            'author' => 'テスト著者',
            'genres' => [$genre->id],
        ];
    }

    private function existingBook(User $user, Genre $genre): Book
    {
        $book = Book::create([
            'title' => '変更前の書籍',
            'author' => '変更前の著者',
            'isbn' => '9784000999901',
            'published_date' => '2026-01-01',
            'user_id' => $user->id,
        ]);

        $book->genres()->attach($genre->id);

        return $book;
    }

    public function test_web_can_create_book_without_isbn_and_published_date(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Web任意登録テスト']);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $this->payload($genre));

        $book = Book::where('title', '任意項目テスト書籍')->firstOrFail();

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => null,
            'published_date' => null,
            'user_id' => $user->id,
        ]);
    }

    public function test_web_can_clear_isbn_and_published_date(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Web任意更新テスト']);
        $book = $this->existingBook($user, $genre);

        $payload = $this->payload($genre);
        $payload['isbn'] = null;
        $payload['published_date'] = null;

        $this->actingAs($user)
            ->put(route('books.update', $book), $payload)
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => null,
            'published_date' => null,
        ]);
    }

    public function test_api_can_create_book_without_isbn_and_published_date(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'API任意登録テスト']);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/books', $this->payload($genre))
            ->assertCreated();

        $book = Book::where('title', '任意項目テスト書籍')->firstOrFail();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => null,
            'published_date' => null,
            'user_id' => $user->id,
        ]);
    }

    public function test_api_can_clear_isbn_and_published_date(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'API任意更新テスト']);
        $book = $this->existingBook($user, $genre);

        Sanctum::actingAs($user);

        $payload = $this->payload($genre);
        $payload['isbn'] = null;
        $payload['published_date'] = null;

        $this->putJson('/api/v1/books/'.$book->id, $payload)
            ->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => null,
            'published_date' => null,
        ]);
    }
}
