<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BookManagementTest extends TestCase
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

    private function bookData(Genre $genre): array
    {
        return [
            'title' => '書籍登録テスト',
            'author' => 'テスト著者',
            'isbn' => '9784000000088',
            'published_date' => '2026-01-01',
            'description' => 'テスト用説明',
            'image_url' => null,
            'genres' => [$genre->id],
        ];
    }

    private function createBook(User $owner, Genre $genre): Book
    {
        $book = Book::create([
            'title' => '編集前の書籍',
            'author' => '編集前の著者',
            'isbn' => '9784000000077',
            'published_date' => '2026-01-01',
            'user_id' => $owner->id,
        ]);

        $book->genres()->attach($genre->id);

        return $book;
    }

    public function test_guest_cannot_create_book(): void
    {
        $this->get(route('books.create'))
            ->assertRedirect(route('login'));

        $genre = Genre::create(['name' => '登録拒否テスト']);

        $this->post(route('books.store'), $this->bookData($genre))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('books', [
            'isbn' => '9784000000088',
        ]);
    }

    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '登録成功テスト']);

        $this->actingAs($user)
            ->post(route('books.store'), $this->bookData($genre))
            ->assertRedirect(route('books.index'));

        $book = Book::where('isbn', '9784000000088')->firstOrFail();

        $this->assertSame($user->id, $book->user_id);

        $this->assertTrue(
            $book->genres()->whereKey($genre->id)->exists()
        );
    }

    public function test_book_creation_rejects_invalid_data(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '入力検証テスト']);

        $data = $this->bookData($genre);
        $data['title'] = '';
        $data['isbn'] = 'invalid';

        $this->actingAs($user)
            ->post(route('books.store'), $data)
            ->assertSessionHasErrors(['title', 'isbn']);

        $this->assertDatabaseMissing('books', [
            'isbn' => 'invalid',
        ]);
    }

    public function test_owner_can_update_book(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::create(['name' => '更新成功テスト']);
        $book = $this->createBook($owner, $genre);

        $data = $this->bookData($genre);
        $data['title'] = '更新後の書籍';
        $data['isbn'] = $book->isbn;

        $this->actingAs($owner)
            ->put(route('books.update', $book), $data)
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後の書籍',
        ]);
    }

    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::create(['name' => '更新拒否テスト']);
        $book = $this->createBook($owner, $genre);

        $data = $this->bookData($genre);
        $data['title'] = '不正な更新';
        $data['isbn'] = $book->isbn;

        $this->actingAs($otherUser)
            ->put(route('books.update', $book), $data)
            ->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '編集前の書籍',
        ]);
    }

    public function test_owner_can_change_book_genre(): void
    {
        $owner = User::factory()->create();
        $oldGenre = Genre::create(['name' => '変更前ジャンル']);
        $newGenre = Genre::create(['name' => '変更後ジャンル']);
        $book = $this->createBook($owner, $oldGenre);

        $data = $this->bookData($newGenre);
        $data['isbn'] = $book->isbn;

        $this->actingAs($owner)
            ->put(route('books.update', $book), $data)
            ->assertRedirect(route('books.show', $book));

        $this->assertFalse(
            $book->genres()->whereKey($oldGenre->id)->exists()
        );

        $this->assertTrue(
            $book->genres()->whereKey($newGenre->id)->exists()
        );
    }
}
