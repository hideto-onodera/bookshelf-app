<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BookAverageRatingTest extends TestCase
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

    public function test_book_index_displays_average_review_rating(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'title' => '平均評価テスト書籍',
            'author' => '平均評価テスト著者',
            'isbn' => '9784000099999',
            'published_date' => '2026-01-01',
            'description' => '平均評価表示のテスト',
            'user_id' => $owner->id,
        ]);

        $reviewer1 = User::factory()->create();
        $reviewer2 = User::factory()->create();

        Review::create([
            'user_id' => $reviewer1->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い書籍です。',
        ]);

        Review::create([
            'user_id' => $reviewer2->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '良い書籍です。',
        ]);

        $response = $this->get(route('books.index'));

        $response
            ->assertOk()
            ->assertSee('平均評価テスト書籍')
            ->assertSee('(4.0)');

        $response->assertViewHas('books', function ($books) use ($book) {
            $listedBook = $books->firstWhere('id', $book->id);

            return $listedBook !== null
                && (float) $listedBook->reviews_avg_rating === 4.0;
        });

        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/★\s*★\s*★\s*★\s*☆/u',
            $html
        );
    }
}
