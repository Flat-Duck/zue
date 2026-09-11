<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->refuseToRunAgainstTheDevelopmentDatabase($app);

        return $app;
    }

    /**
     * `RefreshDatabase` drops every table it finds, so the one thing a test run
     * must never get wrong is which database it is pointed at.
     *
     * It got it wrong once. A `bootstrap/cache/config.php` was present, and a
     * cached config is read instead of the environment files — so `.env.testing`
     * was never loaded, the connection stayed on the development database, and
     * the suite emptied it. Nothing failed; the tests passed.
     *
     * The check is that the environment file which *should* have been loaded is
     * the one actually in effect. Anything else stops the run before a single
     * table is dropped.
     */
    private function refuseToRunAgainstTheDevelopmentDatabase(Application $app): void
    {
        $connection = $app['config']->get('database.default');
        $live = (string) $app['config']->get("database.connections.{$connection}.database");

        if ($connection === 'sqlite' && $live === ':memory:') {
            return;
        }

        $permitted = $this->databasesDeclaredForTesting();

        if ($live !== '' && $permitted !== [] && in_array($live, $permitted, true)) {
            return;
        }

        $cached = file_exists(__DIR__.'/../bootstrap/cache/config.php')
            ? "\n\n  bootstrap/cache/config.php exists. A cached config is read instead of"
                ."\n  the .env files, which is how this happens. Clear it:\n\n"
                ."      php artisan config:clear\n"
            : '';

        throw new RuntimeException(
            "Refusing to run: the test suite is connected to '{$live}', which is not one of the"
            .' databases declared for testing ('.implode(', ', $permitted).').'
            ."\n\n  RefreshDatabase would drop every table in it.{$cached}"
        );
    }

    /**
     * The databases the environment files set aside for tests: `.env.testing` for
     * the suite, `.env.dusk.local` for browser tests.
     *
     * @return list<string>
     */
    protected function databasesDeclaredForTesting(): array
    {
        static $declared = null;

        if (is_array($declared)) {
            return $declared;
        }

        $declared = [];

        foreach (['.env.testing', '.env.dusk.local'] as $file) {
            $path = __DIR__.'/../'.$file;

            if (! is_readable($path)) {
                continue;
            }

            if (preg_match('/^\s*DB_DATABASE\s*=\s*"?([^"\r\n]*)"?/m', (string) file_get_contents($path), $matches)) {
                $name = trim($matches[1]);

                if ($name !== '') {
                    $declared[] = $name;
                }
            }
        }

        return $declared = array_values(array_unique($declared));
    }
}
