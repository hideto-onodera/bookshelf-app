<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BookAverageRatingApiTest extends TestCase
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

    public function test_book_without_reviews_returns_null_average_rating(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'title' => '平均評価未登録テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000999999',
            'published_date' => '2026-01-01',
            'description' => 'レビュー0件の動作確認',
            'user_id' => $user->id,
        ]);

        $response = $this->getJson(
            '/api/v1/books/' . $book->id
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.average_rating', null)
            ->assertJsonPath('data.reviews_count', 0);

        $this->assertArrayHasKey(
            'average_rating',
            $response->json('data')
        );
    }
}
