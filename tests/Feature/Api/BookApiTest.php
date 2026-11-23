<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
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

    private function bookPayload(Genre $genre, string $isbn): array
    {
        return [
            'title' => 'API登録テスト書籍',
            'author' => 'APIテスト著者',
            'isbn' => $isbn,
            'published_date' => '2026-02-01',
            'description' => 'APIから登録した書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];
    }

    public function test_guest_can_get_book_list(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $this->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $book->id,
                'title' => 'APIテスト用書籍',
            ]);
    }

    public function test_guest_can_get_book_detail(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $this->getJson('/api/v1/books/'.$book->id)
            ->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', 'APIテスト用書籍');
    }

    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'API登録テストジャンル']);

        Sanctum::actingAs($user);

        $payload = $this->bookPayload($genre, '9784000000002');

        $this->postJson('/api/v1/books', $payload)
            ->assertCreated()
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

    public function test_owner_can_update_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'API更新テストジャンル']);
        $book = $this->createBook($user);

        Sanctum::actingAs($user);

        $payload = $this->bookPayload($genre, $book->isbn);
        $payload['title'] = 'API更新後の書籍';

        $this->putJson('/api/v1/books/'.$book->id, $payload)
            ->assertOk()
            ->assertJsonPath('data.title', 'API更新後の書籍');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'API更新後の書籍',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_owner_can_delete_book(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/books/'.$book->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_create_book_requires_valid_fields(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/books', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'author',
                'genres',
            ]);
    }

    public function test_create_book_rejects_duplicate_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'API重複テストジャンル']);

        $this->createBook($user, '9784000000003');

        Sanctum::actingAs($user);

        $payload = $this->bookPayload($genre, '9784000000003');

        $this->postJson('/api/v1/books', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn']);
    }

    public function test_guest_cannot_create_book(): void
    {
        $genre = Genre::create(['name' => '未認証登録テスト']);

        $this->postJson(
            '/api/v1/books',
            $this->bookPayload($genre, '9784000000004')
        )->assertUnauthorized();
    }

    public function test_guest_cannot_update_book(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $this->putJson('/api/v1/books/'.$book->id, [])
            ->assertUnauthorized();
    }

    public function test_guest_cannot_delete_book(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $this->deleteJson('/api/v1/books/'.$book->id)
            ->assertUnauthorized();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner);
        $genre = Genre::create(['name' => '他人更新禁止テスト']);

        Sanctum::actingAs($otherUser);

        $payload = $this->bookPayload($genre, $book->isbn);
        $payload['title'] = '不正な更新';

        $this->putJson('/api/v1/books/'.$book->id, $payload)
            ->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'APIテスト用書籍',
            'user_id' => $owner->id,
        ]);
    }

    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner);

        Sanctum::actingAs($otherUser);

        $this->deleteJson('/api/v1/books/'.$book->id)
            ->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_create_book_ignores_forged_user_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::create(['name' => '登録者改ざん防止テスト']);

        Sanctum::actingAs($user);

        $payload = $this->bookPayload($genre, '9784000000005');
        $payload['user_id'] = $otherUser->id;

        $this->postJson('/api/v1/books', $payload)
            ->assertCreated();

        $this->assertDatabaseHas('books', [
            'isbn' => '9784000000005',
            'user_id' => $user->id,
        ]);
    }

    public function test_update_book_ignores_forged_user_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($user);
        $genre = Genre::create(['name' => '更新者改ざん防止テスト']);

        Sanctum::actingAs($user);

        $payload = $this->bookPayload($genre, $book->isbn);
        $payload['user_id'] = $otherUser->id;

        $this->putJson('/api/v1/books/'.$book->id, $payload)
            ->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
        ]);
    }
}
