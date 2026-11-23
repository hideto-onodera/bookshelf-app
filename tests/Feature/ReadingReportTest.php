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

class ReadingReportTest extends TestCase
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

    private function createBook(User $owner, string $title): Book
    {
        static $sequence = 0;
        $sequence++;

        return Book::create([
            'title' => $title,
            'author' => 'テスト著者',
            'isbn' => sprintf('978%010d', $sequence),
            'published_date' => '2026-01-01',
            'user_id' => $owner->id,
        ]);
    }

    private function createReview(
        User $user,
        Book $book,
        int $rating
    ): Review {
        return Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => $rating,
            'comment' => '読書レポートのテストレビュー',
        ]);
    }

    public function test_guest_cannot_access_reading_report(): void
    {
        $this->get(route('reading-reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_with_no_reviews_can_view_empty_report(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reading-reports.index'));

        $response->assertOk()
            ->assertViewIs('reading-reports.index')
            ->assertViewHas('totalReviews', 0)
            ->assertViewHas('reviewedBooksCount', 0)
            ->assertViewHas('averageRating', null)
            ->assertViewHas('ratingDistribution', [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
            ]);

        $this->assertCount(0, $response->viewData('topRatedBooks'));
        $this->assertCount(0, $response->viewData('topGenres'));
    }

    public function test_report_counts_only_current_users_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book1 = $this->createBook($user, '自分の書籍A');
        $book2 = $this->createBook($otherUser, '自分の書籍B');

        $this->createReview($user, $book1, 5);
        $this->createReview($user, $book1, 3);
        $this->createReview($user, $book2, 4);
        $this->createReview($otherUser, $book1, 1);

        $response = $this->actingAs($user)
            ->get(route('reading-reports.index'));

        $response->assertOk()
            ->assertViewHas('totalReviews', 3)
            ->assertViewHas('reviewedBooksCount', 2)
            ->assertViewHas('ratingDistribution', [
                1 => 0,
                2 => 0,
                3 => 1,
                4 => 1,
                5 => 1,
            ]);

        $this->assertEqualsWithDelta(
            4.0,
            (float) $response->viewData('averageRating'),
            0.0001
        );
    }

    public function test_deleted_reviews_are_excluded_from_report(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user, '削除対象の書籍');

        $remainingReview = $this->createReview($user, $book, 4);
        $deletedReview = $this->createReview($user, $book, 1);

        $deletedReview->delete();

        $response = $this->actingAs($user)
            ->get(route('reading-reports.index'));

        $response->assertOk()
            ->assertViewHas('totalReviews', 1)
            ->assertViewHas('reviewedBooksCount', 1)
            ->assertViewHas('ratingDistribution', [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 1,
                5 => 0,
            ]);

        $this->assertEqualsWithDelta(
            4.0,
            (float) $response->viewData('averageRating'),
            0.0001
        );

        $this->assertDatabaseHas('reviews', [
            'id' => $remainingReview->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $deletedReview->id,
        ]);
    }

    public function test_top_rated_books_exclude_low_ratings_and_limit_to_five(): void
    {
        $user = User::factory()->create();

        for ($index = 1; $index <= 6; $index++) {
            $book = $this->createBook($user, '高評価書籍'.$index);
            $this->createReview($user, $book, 5);
        }

        $lowRatedBook = $this->createBook($user, '低評価書籍');
        $this->createReview($user, $lowRatedBook, 3);

        $response = $this->actingAs($user)
            ->get(route('reading-reports.index'));

        $response->assertOk();

        $topBooks = $response->viewData('topRatedBooks');

        $this->assertCount(5, $topBooks);
        $this->assertEquals(
            ['高評価書籍1', '高評価書籍2', '高評価書籍3', '高評価書籍4', '高評価書籍5'],
            $topBooks->pluck('title')->all()
        );
        $this->assertNotContains(
            '低評価書籍',
            $topBooks->pluck('title')->all()
        );
    }

    public function test_top_genres_use_users_reviews_and_limit_to_five(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        for ($index = 1; $index <= 6; $index++) {
            $genre = Genre::create([
                'name' => 'レポート用ジャンル'.$index,
            ]);

            $book = $this->createBook($user, 'ジャンル別書籍'.$index);
            $book->genres()->attach($genre->id);

            $this->createReview($user, $book, 5);
            $this->createReview($otherUser, $book, 1);
        }

        $response = $this->actingAs($user)
            ->get(route('reading-reports.index'));

        $response->assertOk();

        $topGenres = $response->viewData('topGenres');

        $this->assertCount(5, $topGenres);
        $this->assertEquals(
            [
                'レポート用ジャンル1',
                'レポート用ジャンル2',
                'レポート用ジャンル3',
                'レポート用ジャンル4',
                'レポート用ジャンル5',
            ],
            $topGenres->pluck('name')->all()
        );

        foreach ($topGenres as $genre) {
            $this->assertEqualsWithDelta(
                5.0,
                (float) $genre->average_rating,
                0.0001
            );
        }
    }

    public function test_one_review_contributes_to_each_associated_genre(): void
    {
        $user = User::factory()->create();

        $genre1 = Genre::create(['name' => '複数ジャンルA']);
        $genre2 = Genre::create(['name' => '複数ジャンルB']);

        $book = $this->createBook($user, '複数ジャンル書籍');
        $book->genres()->attach([$genre1->id, $genre2->id]);

        $this->createReview($user, $book, 4);

        $response = $this->actingAs($user)
            ->get(route('reading-reports.index'));

        $response->assertOk()
            ->assertViewHas('totalReviews', 1);

        $topGenres = $response->viewData('topGenres');

        $this->assertCount(2, $topGenres);

        foreach ($topGenres as $genre) {
            $this->assertEqualsWithDelta(
                4.0,
                (float) $genre->average_rating,
                0.0001
            );
            $this->assertSame(1, (int) $genre->review_count);
        }
    }

    public function test_top_rated_books_are_ordered_by_average_rating(): void
    {
        $user = User::factory()->create();

        $bookA = $this->createBook($user, '平均4の書籍');
        $bookB = $this->createBook($user, '平均5の書籍');
        $bookC = $this->createBook($user, '平均4.5の書籍');

        $this->createReview($user, $bookA, 4);
        $this->createReview($user, $bookB, 5);
        $this->createReview($user, $bookC, 4);
        $this->createReview($user, $bookC, 5);

        $response = $this->actingAs($user)
            ->get(route('reading-reports.index'));

        $response->assertOk();

        $topBooks = $response->viewData('topRatedBooks');

        $this->assertEquals(
            ['平均5の書籍', '平均4.5の書籍', '平均4の書籍'],
            $topBooks->pluck('title')->all()
        );

        $this->assertEqualsWithDelta(
            4.5,
            (float) $topBooks[1]->average_rating,
            0.0001
        );
    }

    public function test_top_rated_books_use_review_count_as_tiebreaker(): void
    {
        $user = User::factory()->create();

        $bookA = $this->createBook($user, 'レビュー1件の書籍');
        $bookB = $this->createBook($user, 'レビュー2件の書籍');

        $this->createReview($user, $bookA, 5);
        $this->createReview($user, $bookB, 5);
        $this->createReview($user, $bookB, 5);

        $response = $this->actingAs($user)
            ->get(route('reading-reports.index'));

        $response->assertOk();

        $topBooks = $response->viewData('topRatedBooks');

        $this->assertEquals(
            ['レビュー2件の書籍', 'レビュー1件の書籍'],
            $topBooks->pluck('title')->all()
        );

        $this->assertSame(2, (int) $topBooks[0]->review_count);
    }

    public function test_top_genres_are_ordered_by_average_rating(): void
    {
        $user = User::factory()->create();

        $ratings = [
            '評価3ジャンル' => 3,
            '評価5ジャンル' => 5,
            '評価4ジャンル' => 4,
        ];

        foreach ($ratings as $name => $rating) {
            $genre = Genre::create(['name' => $name]);
            $book = $this->createBook($user, $name.'の書籍');

            $book->genres()->attach($genre->id);
            $this->createReview($user, $book, $rating);
        }

        $response = $this->actingAs($user)
            ->get(route('reading-reports.index'));

        $response->assertOk();

        $topGenres = $response->viewData('topGenres');

        $this->assertEquals(
            ['評価5ジャンル', '評価4ジャンル', '評価3ジャンル'],
            $topGenres->pluck('name')->all()
        );

        $this->assertEqualsWithDelta(
            5.0,
            (float) $topGenres[0]->average_rating,
            0.0001
        );
    }
}
