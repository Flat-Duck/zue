<?php

namespace Tests;

use Closure;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use Throwable;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Every browser test ends by checking the console.
     *
     * A refused script, a failed request or a JavaScript exception all land in
     * the browser log and nowhere else — the page looks fine and the assertion
     * that was made still passes. Checking after every test rather than in the
     * few that remembered to is what makes the suite a real canary for the
     * content security policy: a script that loses its nonce fails whichever
     * test next visits that page.
     */
    public function browse(Closure $callback)
    {
        return parent::browse(function (Browser ...$browsers) use ($callback): void {
            $callback(...$browsers);

            foreach ($browsers as $browser) {
                $this->assertNoBrowserErrors($browser, 'The page');
            }
        });
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Severe console entries mean a script or stylesheet failed to load — exactly
     * what moving assets out of a view could cause, and invisible to any assertion
     * made on the server.
     *
     * WebDriver occasionally refuses the log call outright. That is a driver hiccup
     * rather than a page fault, and failing on it would make this suite flaky for no
     * information at all.
     *
     * @return list<string>
     */
    protected function browserErrors(Browser $browser): array
    {
        try {
            $entries = $browser->driver->manage()->getLog('browser');
        } catch (Throwable) {
            return [];
        }

        return collect($entries)
            ->filter(fn (array $entry): bool => ($entry['level'] ?? '') === 'SEVERE')
            ->map(fn (array $entry): string => $entry['message'])
            ->values()
            ->all();
    }

    protected function assertNoBrowserErrors(Browser $browser, string $context = 'The page'): void
    {
        $errors = $this->browserErrors($browser);

        $this->assertSame([], $errors, $context.' logged browser errors: '.implode(' | ', $errors));
    }
}
