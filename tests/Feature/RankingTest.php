<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class RankingTest extends TestCase
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

    private function createBook(User $owner, int $number): Book
    {
        return Book::create([
            'title' => 'ランキングテスト書籍'.$number,
            'author' => 'テスト著者',
            'isbn' => sprintf('978400001%04d', $number),
            'published_date' => '2026-01-01',
            'description' => 'ランキングテスト用の説明',
            'user_id' => $owner->id,
        ]);
    }

    private function createReview(
        User $reviewer,
        Book $book,
        int $rating
    ): Review {
        return Review::create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
            'rating' => $rating,
            'comment' => 'ランキングテスト用レビュー',
        ]);
    }

    public function test_guest_can_view_ranking(): void
    {
        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewIs('ranking.index');
    }

    public function test_books_are_ordered_by_average_rating(): void
    {
        $owner = User::factory()->create();
        $reviewer1 = User::factory()->create();
        $reviewer2 = User::factory()->create();

        $high = $this->createBook($owner, 1);
        $middle = $this->createBook($owner, 2);
        $low = $this->createBook($owner, 3);

        $this->createReview($reviewer1, $high, 5);
        $this->createReview($reviewer2, $high, 5);

        $this->createReview($reviewer1, $middle, 4);
        $this->createReview($reviewer2, $middle, 4);

        $this->createReview($reviewer1, $low, 2);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewHas('rankedBooks', function ($books) use (
                $high,
                $middle,
                $low
            ) {
                return $books->pluck('id')->all() === [
                    $high->id,
                    $middle->id,
                    $low->id,
                ];
            });
    }

    public function test_books_without_reviews_are_excluded(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();

        $reviewed = $this->createBook($owner, 4);
        $unreviewed = $this->createBook($owner, 5);

        $this->createReview($reviewer, $reviewed, 5);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewHas('rankedBooks', function ($books) use (
                $reviewed,
                $unreviewed
            ) {
                return $books->contains('id', $reviewed->id)
                    && ! $books->contains('id', $unreviewed->id);
            });
    }

    public function test_ranking_displays_at_most_ten_books(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();

        for ($number = 1; $number <= 11; $number++) {
            $book = $this->createBook($owner, $number + 10);
            $this->createReview($reviewer, $book, 5);
        }

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewHas('rankedBooks', function ($books) {
                return $books->count() === 10;
            });
    }

    public function test_ranking_displays_review_average_and_count(): void
    {
        $owner = User::factory()->create();
        $reviewer1 = User::factory()->create();
        $reviewer2 = User::factory()->create();

        $book = $this->createBook($owner, 6);

        $this->createReview($reviewer1, $book, 5);
        $this->createReview($reviewer2, $book, 3);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewHas('rankedBooks', function ($books) use ($book) {
                $rankedBook = $books->firstWhere('id', $book->id);

                return $rankedBook !== null
                    && (float) $rankedBook->reviews_avg_rating === 4.0
                    && (int) $rankedBook->reviews_count === 2;
            });
    }
}
