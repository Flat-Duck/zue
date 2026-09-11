<?php

namespace Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\CreatesApplication;

class TestDatabaseGuardTest extends TestCase
{
    #[DataProvider('unsafeDatabases')]
    public function test_unsafe_connections_are_rejected(string $driver, string $database, array $permitted): void
    {
        $this->expectException(RuntimeException::class);
        $this->guard($driver, $database, $permitted);
    }

    public static function unsafeDatabases(): array
    {
        return [
            ['mysql', 'zue', []],
            ['mysql', '', []],
            ['mysql', 'zue', ['zue_testing']],
            ['sqlite', '/tmp/development.sqlite', []],
            ['mysql', ':memory:', []],
        ];
    }

    public function test_explicit_test_database_and_sqlite_memory_are_allowed(): void
    {
        $this->guard('mysql', 'zue_testing', ['zue_testing']);
        $this->guard('sqlite', ':memory:', []);
        $this->addToAssertionCount(2);
    }

    private function guard(string $driver, string $database, array $permitted): void
    {
        $guard = new class($permitted)
        {
            use CreatesApplication;

            public function __construct(private array $permitted) {}

            protected function databasesDeclaredForTesting(): array
            {
                return $this->permitted;
            }

            public function check(Application $app): void
            {
                $this->refuseToRunAgainstTheDevelopmentDatabase($app);
            }
        };
        $app = new Application;
        $app->instance('config', new Repository([
            'database' => ['default' => $driver, 'connections' => [$driver => ['database' => $database]]],
        ]));
        $guard->check($app);
    }
}
