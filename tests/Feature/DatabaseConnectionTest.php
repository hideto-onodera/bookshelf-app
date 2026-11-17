<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function test_testing_database_is_selected(): void
    {
        $this->assertSame('testing', config('app.env'));

        $this->assertSame('mysql', config('database.default'));

        $this->assertSame(
            'testing',
            config('database.connections.mysql.database')
        );

        $this->assertEmpty(
            config('database.connections.mysql.url')
        );
    }

    public function test_actual_mysql_connection_uses_testing_database(): void
    {
        $this->assertSame(
            'testing',
            DB::selectOne('SELECT DATABASE() AS database_name')->database_name
        );
    }
}
