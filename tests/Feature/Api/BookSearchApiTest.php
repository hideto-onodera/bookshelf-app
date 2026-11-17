<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BookSearchApiTest extends TestCase
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
        User $user,
        string $title,
        string $author,
        int $number
    ): Book {
        return Book::create([
            'title' => $title,
            'author' => $author,
            'isbn' => sprintf('9784%09d', $number),
            'published_date' => '2026-01-01',
            'description' => '検索APIテスト用',
            'user_id' => $user->id,
        ]);
    }

    public function test_keyword_search_matches_title(): void
    {
        $user = User::factory()->create();

        $target = $this->createBook(
            $user,
            '特別な検索対象タイトル',
            '著者A',
            100001
        );

        $other = $this->createBook(
            $user,
            '別の書籍',
            '著者B',
            100002
        );

        $response = $this->getJson(
            '/api/v1/books?keyword='.urlencode('特別な検索対象')
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);

        $this->assertNotEquals(
            $other->id,
            $response->json('data.0.id')
        );
    }

    public function test_keyword_search_matches_author(): void
    {
        $user = User::factory()->create();

        $target = $this->createBook(
            $user,
            '著者検索テスト書籍',
            '特別な検索対象著者',
            100003
        );

        $this->createBook(
            $user,
            '別の書籍',
            '一般著者',
            100004
        );

        $response = $this->getJson(
            '/api/v1/books?keyword='.urlencode('特別な検索対象著者')
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);
    }

    public function test_genre_filter_returns_matching_books(): void
    {
        $user = User::factory()->create();

        $targetGenre = Genre::create([
            'name' => 'API検索対象ジャンル',
        ]);

        $otherGenre = Genre::create([
            'name' => 'API検索対象外ジャンル',
        ]);

        $target = $this->createBook(
            $user,
            'ジャンル対象書籍',
            '著者A',
            100005
        );

        $other = $this->createBook(
            $user,
            'ジャンル対象外書籍',
            '著者B',
            100006
        );

        $target->genres()->attach($targetGenre->id);
        $other->genres()->attach($otherGenre->id);

        $response = $this->getJson(
            '/api/v1/books?genre_id='.$targetGenre->id
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);
    }

    public function test_keyword_and_genre_filters_can_be_combined(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'API複合検索ジャンル',
        ]);

        $target = $this->createBook(
            $user,
            '複合検索一致書籍',
            '著者A',
            100007
        );

        $other = $this->createBook(
            $user,
            '複合検索対象外書籍',
            '著者B',
            100008
        );

        $target->genres()->attach($genre->id);
        $other->genres()->attach($genre->id);

        $response = $this->getJson(
            '/api/v1/books?keyword='
            .urlencode('複合検索一致')
            .'&genre_id='.$genre->id
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);
    }

    public function test_pagination_respects_per_page(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 5; $i++) {
            $this->createBook(
                $user,
                'ページ分割テスト書籍'.$i,
                '著者',
                200000 + $i
            );
        }

        $response = $this->getJson(
            '/api/v1/books?per_page=2&page=1'
        );

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_invalid_page_is_rejected(): void
    {
        $response = $this->getJson(
            '/api/v1/books?page=0'
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    public function test_invalid_per_page_is_rejected(): void
    {
        $response = $this->getJson(
            '/api/v1/books?per_page=101'
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_nonexistent_genre_is_rejected(): void
    {
        $response = $this->getJson(
            '/api/v1/books?genre_id=999999999'
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['genre_id']);
    }
}
