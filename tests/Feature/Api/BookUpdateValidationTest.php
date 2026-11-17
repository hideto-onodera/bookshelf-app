<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BookUpdateValidationTest extends TestCase
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

    private function createBook(User $user, string $isbn): Book
    {
        return Book::create([
            'title' => '更新前の書籍',
            'author' => 'テスト著者',
            'isbn' => $isbn,
            'published_date' => '2026-01-01',
            'description' => '更新バリデーションテスト',
            'user_id' => $user->id,
        ]);
    }

    private function validPayload(
        User $user,
        Genre $genre,
        string $isbn
    ): array {
        return [
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
            'isbn' => $isbn,
            'published_date' => '2026-02-01',
            'description' => '更新後の説明',
            'user_id' => $user->id,
            'genres' => [$genre->id],
        ];
    }

    public function test_update_rejects_isbn_used_by_another_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create([
            'name' => '更新ISBN重複テストジャンル',
        ]);

        $book = $this->createBook($user, '9784000111101');
        $other = $this->createBook($user, '9784000111102');

        $payload = $this->validPayload(
            $user,
            $genre,
            $other->isbn
        );

        $response = $this->putJson(
            '/api/v1/books/'.$book->id,
            $payload
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前の書籍',
            'isbn' => '9784000111101',
        ]);
    }

    public function test_update_rejects_nonexistent_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create([
            'name' => '更新ジャンル検証テスト',
        ]);

        $book = $this->createBook($user, '9784000111103');

        $payload = $this->validPayload(
            $user,
            $genre,
            $book->isbn
        );

        $payload['genres'] = [999999999];

        $response = $this->putJson(
            '/api/v1/books/'.$book->id,
            $payload
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['genres.0']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前の書籍',
        ]);
    }

    public function test_update_rejects_missing_required_fields(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user, '9784000111104');

        $response = $this->putJson(
            '/api/v1/books/'.$book->id,
            []
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'user_id',
                'genres',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前の書籍',
            'isbn' => '9784000111104',
        ]);
    }
}
