<?php

namespace Tests\Feature;

use App\Providers\RuntimeCompatibilityServiceProvider;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RuntimeCompatibilityTest extends TestCase
{
    public function test_database_runtime_drivers_fall_back_when_tables_are_missing(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('session.driver', 'database');
        config()->set('cache.default', 'database');
        config()->set('queue.default', 'database');

        DB::purge('sqlite');

        (new RuntimeCompatibilityServiceProvider($this->app))->register();

        $this->assertSame('file', config('session.driver'));
        $this->assertSame('file', config('cache.default'));
        $this->assertSame('sync', config('queue.default'));
    }
}
