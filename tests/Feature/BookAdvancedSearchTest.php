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

class BookAdvancedSearchTest extends TestCase
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

    private function createBook(
        User $owner,
        int $number,
        string $title,
        string $author = 'テスト著者'
    ): Book {
        return Book::create([
            'title' => $title,
            'author' => $author,
            'isbn' => sprintf('978499900%04d', $number),
            'published_date' => '2026-01-01',
            'description' => '高度な検索機能のテスト',
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
            'comment' => '評価順テスト',
        ]);
    }

    public function test_keyword_search_matches_title_or_author(): void
    {
        $owner = User::factory()->create();

        $titleMatch = $this->createBook(
            $owner,
            1,
            'Laravel検索テスト'
        );

        $authorMatch = $this->createBook(
            $owner,
            2,
            '別の書籍',
            'Laravel著者'
        );

        $nonMatch = $this->createBook(
            $owner,
            3,
            '無関係な書籍',
            '別の著者'
        );

        $this->get(route('books.index', ['keyword' => 'Laravel']))
            ->assertOk()
            ->assertViewHas('books', function ($books) use (
                $titleMatch,
                $authorMatch,
                $nonMatch
            ) {
                $ids = $books->getCollection()->modelKeys();

                return in_array($titleMatch->id, $ids, true)
                    && in_array($authorMatch->id, $ids, true)
                    && ! in_array($nonMatch->id, $ids, true);
            });
    }

    public function test_genre_filter_returns_only_matching_books(): void
    {
        $owner = User::factory()->create();

        $genreA = Genre::create([
            'name' => '高度検索ジャンルA',
        ]);

        $genreB = Genre::create([
            'name' => '高度検索ジャンルB',
        ]);

        $bookA = $this->createBook($owner, 4, 'ジャンルAの書籍');
        $bookB = $this->createBook($owner, 5, 'ジャンルBの書籍');

        $bookA->genres()->attach($genreA->id);
        $bookB->genres()->attach($genreB->id);

        $this->get(route('books.index', ['genre' => $genreA->id]))
            ->assertOk()
            ->assertViewHas('books', function ($books) use ($bookA, $bookB) {
                $ids = $books->getCollection()->modelKeys();

                return in_array($bookA->id, $ids, true)
                    && ! in_array($bookB->id, $ids, true);
            });
    }

    public function test_keyword_and_genre_can_be_combined(): void
    {
        $owner = User::factory()->create();

        $genre = Genre::create([
            'name' => '高度検索組み合わせジャンル',
        ]);

        $matching = $this->createBook($owner, 6, 'Laravel入門');
        $other = $this->createBook($owner, 7, 'PHP入門');

        $matching->genres()->attach($genre->id);
        $other->genres()->attach($genre->id);

        $this->get(route('books.index', [
            'keyword' => 'Laravel',
            'genre' => $genre->id,
        ]))
            ->assertOk()
            ->assertViewHas('books', function ($books) use ($matching) {
                return $books->total() === 1
                    && $books->first()->id === $matching->id;
            });
    }

    public function test_latest_and_oldest_sorting(): void
    {
        $owner = User::factory()->create();

        $old = $this->createBook($owner, 8, '古い書籍');
        $new = $this->createBook($owner, 9, '新しい書籍');

        $old->timestamps = false;
        $old->created_at = '2026-01-01 00:00:00';
        $old->save();

        $new->timestamps = false;
        $new->created_at = '2026-02-01 00:00:00';
        $new->save();

        $this->get(route('books.index', [
            'keyword' => '書籍',
            'sort' => 'latest',
        ]))
            ->assertOk()
            ->assertViewHas('books', function ($books) use ($old, $new) {
                $ids = $books->getCollection()->modelKeys();

                return array_search($new->id, $ids, true)
                    < array_search($old->id, $ids, true);
            });

        $this->get(route('books.index', [
            'keyword' => '書籍',
            'sort' => 'oldest',
        ]))
            ->assertOk()
            ->assertViewHas('books', function ($books) use ($old, $new) {
                $ids = $books->getCollection()->modelKeys();

                return array_search($old->id, $ids, true)
                    < array_search($new->id, $ids, true);
            });
    }

    public function test_title_sorting(): void
    {
        $owner = User::factory()->create();

        $bookB = $this->createBook($owner, 10, 'ZZZタイトル');
        $bookA = $this->createBook($owner, 11, 'AAAタイトル');

        $this->get(route('books.index', [
            'keyword' => 'タイトル',
            'sort' => 'title',
        ]))
            ->assertOk()
            ->assertViewHas('books', function ($books) use ($bookA, $bookB) {
                $ids = $books->getCollection()->modelKeys();

                return array_search($bookA->id, $ids, true)
                    < array_search($bookB->id, $ids, true);
            });
    }

    public function test_rating_sort_places_unreviewed_books_last(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();

        $unreviewed = $this->createBook($owner, 12, '評価テスト未評価');
        $low = $this->createBook($owner, 13, '評価テスト低評価');
        $high = $this->createBook($owner, 14, '評価テスト高評価');

        $this->createReview($reviewer, $low, 2);
        $this->createReview($reviewer, $high, 5);

        $this->get(route('books.index', [
            'keyword' => '評価テスト',
            'sort' => 'rating',
        ]))
            ->assertOk()
            ->assertViewHas('books', function ($books) use (
                $high,
                $low,
                $unreviewed
            ) {
                return $books->getCollection()->modelKeys() === [
                    $high->id,
                    $low->id,
                    $unreviewed->id,
                ];
            });
    }

    public function test_pagination_preserves_search_conditions(): void
    {
        $owner = User::factory()->create();

        $genre = Genre::create([
            'name' => '高度検索ページングジャンル',
        ]);

        for ($number = 20; $number <= 30; $number++) {
            $book = $this->createBook(
                $owner,
                $number,
                'ページング対象'.$number
            );

            $book->genres()->attach($genre->id);
        }

        $parameters = [
            'keyword' => 'ページング対象',
            'genre' => $genre->id,
            'sort' => 'title',
        ];

        $this->get(route('books.index', $parameters))
            ->assertOk()
            ->assertViewHas('books', function ($books) use ($parameters) {
                $nextPageUrl = $books->url(2);

                return $books->total() === 11
                    && $books->perPage() === 10
                    && str_contains($nextPageUrl, 'page=2')
                    && str_contains($nextPageUrl, 'keyword=')
                    && str_contains($nextPageUrl, 'genre='.$parameters['genre'])
                    && str_contains($nextPageUrl, 'sort=title');
            });

        $this->get(route('books.index', array_merge(
            $parameters,
            ['page' => 2]
        )))
            ->assertOk()
            ->assertViewHas('books', function ($books) {
                return $books->currentPage() === 2
                    && $books->count() === 1;
            });
    }

    public function test_invalid_sort_is_rejected(): void
    {
        $this->get(route('books.index', ['sort' => 'invalid']))
            ->assertSessionHasErrors('sort');
    }

    public function test_nonexistent_genre_is_rejected(): void
    {
        $this->get(route('books.index', ['genre' => 999999999]))
            ->assertSessionHasErrors('genre');
    }
}
