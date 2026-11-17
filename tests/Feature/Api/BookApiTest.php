<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BookApiTest extends TestCase
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

    private function createBook(User $user, string $isbn = '9784000000001'): Book
    {
        return Book::create([
            'title' => 'APIテスト用書籍',
            'author' => 'テスト著者',
            'isbn' => $isbn,
            'published_date' => '2026-01-01',
            'description' => 'APIテスト用の説明',
            'user_id' => $user->id,
        ]);
    }

    private function bookPayload(User $user, Genre $genre, string $isbn): array
    {
        return [
            'title' => 'API登録テスト書籍',
            'author' => 'APIテスト著者',
            'isbn' => $isbn,
            'published_date' => '2026-02-01',
            'description' => 'APIから登録した書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'user_id' => $user->id,
            'genres' => [$genre->id],
        ];
    }

    public function test_guest_can_get_book_list(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $book->id,
                'title' => 'APIテスト用書籍',
            ]);
    }

    public function test_guest_can_get_book_detail(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this->getJson('/api/v1/books/'.$book->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', 'APIテスト用書籍');
    }

    public function test_guest_can_create_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'API登録テストジャンル']);

        $payload = $this->bookPayload($user, $genre, '9784000000002');

        $response = $this->postJson('/api/v1/books', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'API登録テスト書籍');

        $this->assertDatabaseHas('books', [
            'isbn' => '9784000000002',
            'user_id' => $user->id,
        ]);

        $book = Book::where('isbn', '9784000000002')->firstOrFail();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_guest_can_update_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'API更新テストジャンル']);
        $book = $this->createBook($user);

        $payload = $this->bookPayload($user, $genre, $book->isbn);
        $payload['title'] = 'API更新後の書籍';

        $response = $this->putJson(
            '/api/v1/books/'.$book->id,
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('data.title', 'API更新後の書籍');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'API更新後の書籍',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_guest_can_delete_book(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this->deleteJson('/api/v1/books/'.$book->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_create_book_requires_valid_fields(): void
    {
        $response = $this->postJson('/api/v1/books', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'user_id',
                'genres',
            ]);
    }

    public function test_create_book_rejects_duplicate_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'API重複テストジャンル']);

        $this->createBook($user, '9784000000003');

        $payload = $this->bookPayload($user, $genre, '9784000000003');

        $response = $this->postJson('/api/v1/books', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn']);
    }
}
