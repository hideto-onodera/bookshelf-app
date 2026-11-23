<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookIsbnSearchTest extends TestCase
{
    use DatabaseTransactions;

    private function isbnUrl(string $isbn): string
    {
        return route('books.isbn.show', ['isbn' => $isbn]);
    }

    public function test_guest_cannot_search_isbn(): void
    {
        $this->get($this->isbnUrl('9784000000000'))
            ->assertRedirect(route('login'));
    }

    public function test_valid_isbn_returns_book_information(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'テスト書籍',
                            'authors' => ['著者A', '著者B'],
                            'publishedDate' => '2024-05-10',
                            'description' => '書籍の説明',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/cover.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson($this->isbnUrl('9784000000000'))
            ->assertOk()
            ->assertJson([
                'title' => 'テスト書籍',
                'author' => '著者A、著者B',
                'published_date' => '2024-05-10',
                'description' => '書籍の説明',
                'image_url' => 'https://example.com/cover.jpg',
            ]);

        Http::assertSentCount(1);
    }

    public function test_invalid_isbn_returns_400_without_api_request(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->getJson($this->isbnUrl('invalid'))
            ->assertStatus(400)
            ->assertJsonStructure(['message']);

        Http::assertNothingSent();
    }

    public function test_missing_book_returns_404(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
            ], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson($this->isbnUrl('9784000000000'))
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_api_quota_error_returns_429(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([], 429),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson($this->isbnUrl('9784000000000'))
            ->assertStatus(429)
            ->assertJsonStructure(['message']);
    }

    public function test_api_server_error_returns_500(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([], 503),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson($this->isbnUrl('9784000000000'))
            ->assertStatus(500)
            ->assertJsonStructure(['message']);
    }

    public function test_api_connection_failure_returns_500(): void
    {
        Http::fake(function () {
            throw new ConnectionException(
                'Connection failed'
            );
        });

        $this->actingAs(User::factory()->create())
            ->getJson($this->isbnUrl('9784000000000'))
            ->assertStatus(500)
            ->assertJsonStructure(['message']);
    }

    public function test_partial_book_information_returns_null_for_missing_fields(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'タイトルのみの書籍',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson($this->isbnUrl('9784000000000'))
            ->assertOk()
            ->assertJson([
                'title' => 'タイトルのみの書籍',
                'author' => null,
                'published_date' => null,
                'description' => null,
                'image_url' => null,
            ]);
    }

    public function test_year_only_publication_date_is_normalized(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '出版年のみの書籍',
                            'publishedDate' => '2024',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson($this->isbnUrl('9784000000000'))
            ->assertOk()
            ->assertJsonPath('published_date', '2024-01-01');
    }
}
