<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The views must not reach out to another host at runtime.
 *
 * Flatpickr and ApexCharts used to be pulled from a CDN on the time sheet and
 * dashboard screens. That made those pages depend on someone else's uptime, and it
 * is the first thing that breaks when a content security policy is introduced —
 * which phase 7 will do. They are part of the build now, and this keeps them there.
 */
class FrontendAssetTest extends TestCase
{
    /**
     * Hosts a view may still link to, because they are destinations a person clicks
     * rather than something the page loads.
     *
     * @var list<string>
     */
    private const LINK_ONLY_HOSTS = [
        'tabler.io',
        'github.com',
        'laravel.com',
    ];

    #[Test]
    public function no_view_loads_a_script_stylesheet_or_font_from_another_host(): void
    {
        $offenders = [];

        foreach ($this->viewFiles() as $path) {
            $contents = file_get_contents($path);

            preg_match_all(
                '/<(?:script|link)\b[^>]*\b(?:src|href)\s*=\s*"(https?:)?\/\/([^\/"]+)[^"]*"/i',
                $contents,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $host = $match[2];

                if (in_array($host, self::LINK_ONLY_HOSTS, true)) {
                    continue;
                }

                $offenders[] = str_replace(base_path().'/', '', $path).' loads from '.$host;
            }
        }

        $this->assertSame([], $offenders, implode(PHP_EOL, $offenders));
    }

    /**
     * @return list<string>
     */
    private function viewFiles(): array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
