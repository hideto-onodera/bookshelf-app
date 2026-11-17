<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class GenreManagementTest extends TestCase
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

    public function test_authenticated_user_can_view_genre_details(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '詳細表示テスト']);

        $this->actingAs($user)
            ->get(route('genres.show', $genre))
            ->assertOk()
            ->assertSee('詳細表示テスト');
    }

    public function test_authenticated_user_can_open_genre_edit_page(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '編集画面テスト']);

        $this->actingAs($user)
            ->get(route('genres.edit', $genre))
            ->assertOk()
            ->assertViewIs('genres.edit');
    }

    public function test_authenticated_user_can_update_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '変更前ジャンル']);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '変更後ジャンル',
            ])
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '変更後ジャンル',
        ]);
    }

    public function test_genre_can_be_saved_without_changing_its_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '変更しないジャンル']);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '変更しないジャンル',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('genres.index'));
    }

    public function test_duplicate_genre_name_is_rejected(): void
    {
        $user = User::factory()->create();

        Genre::create(['name' => '既存ジャンル']);
        $genre = Genre::create(['name' => '変更対象ジャンル']);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '既存ジャンル',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '変更対象ジャンル',
        ]);
    }

    public function test_empty_genre_name_is_rejected(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '必須入力テスト']);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_guest_cannot_manage_genres(): void
    {
        $genre = Genre::create(['name' => 'ゲスト制限テスト']);

        $this->get(route('genres.show', $genre))
            ->assertRedirect(route('login'));

        $this->get(route('genres.edit', $genre))
            ->assertRedirect(route('login'));

        $this->put(route('genres.update', $genre), [
            'name' => '不正な更新',
        ])->assertRedirect(route('login'));

        $this->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ゲスト制限テスト',
        ]);
    }
}
