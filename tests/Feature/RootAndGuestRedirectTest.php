<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class RootAndGuestRedirectTest extends TestCase
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

    public function test_root_displays_book_index(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('books.index');
    }

    public function test_books_path_displays_book_index(): void
    {
        $this->get('/books')
            ->assertOk()
            ->assertViewIs('books.index');
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/books');
    }

    public function test_authenticated_user_is_redirected_from_register(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/register')
            ->assertRedirect('/books');
    }
}
