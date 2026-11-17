<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class GenreDeletionTest extends TestCase
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

    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'title' => 'ジャンル削除制限テスト用書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000002',
            'published_date' => '2026-01-01',
            'user_id' => $user->id,
        ]);

        $protectedGenre = Genre::create([
            'name' => '削除制限対象ジャンル',
        ]);

        $otherGenre = Genre::create([
            'name' => '保持対象ジャンル',
        ]);

        $book->genres()->attach([
            $protectedGenre->id,
            $otherGenre->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $protectedGenre));

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'error',
            '書籍が紐付いているジャンルは削除できません。'
        );

        $this->assertDatabaseHas('genres', [
            'id' => $protectedGenre->id,
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'ジャンル削除制限テスト用書籍',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $protectedGenre->id,
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $otherGenre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $otherGenre->id,
        ]);
    }

    public function test_genre_without_books_can_be_deleted(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '紐付きなし削除対象ジャンル',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'success',
            'ジャンルを削除しました。'
        );

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_guest_cannot_delete_genre(): void
    {
        $genre = Genre::create([
            'name' => 'ゲスト削除禁止対象ジャンル',
        ]);

        $response = $this->delete(
            route('genres.destroy', $genre)
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_genre_index_disables_deletion_when_books_exist(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'title' => 'ジャンル一覧表示テスト用書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000003',
            'published_date' => '2026-01-01',
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => '一覧削除制限ジャンル',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)
            ->get(route('genres.index'));

        $response->assertOk();

        $response->assertSee('一覧削除制限ジャンル');

        $response->assertSee(
            '書籍が紐付いているため削除できません。'
        );
    }
}