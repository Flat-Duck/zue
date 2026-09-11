<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * The interface is offered in Arabic and English, which only holds if every
 * string a person can read comes from a lang file. A hard-coded label is
 * invisible in review — it renders perfectly in whichever language it happens to
 * be written in — so it is caught here instead.
 */
class TranslationCoverageTest extends TestCase
{
    /**
     * Nothing renders these: no route, no `view()` call, no `@include`. Two are
     * Tabler leftovers, one is a prototype that queries the database from the
     * template, and one is only referenced from commented-out Blade.
     *
     * @var list<string>
     */
    private const UNRENDERED = [
        'app/time_sheets/print.blade.php',
        'components/print-header.blade.php',
        'components/inputs/radio.blade.php',
        'livewire/time-sheeter.blade.php',
    ];

    /**
     * @return list<string>
     */
    private function views(): array
    {
        $views = [];
        $root = resource_path('views');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
            if (! str_ends_with((string) $file, '.blade.php')) {
                continue;
            }

            $relative = str_replace($root.DIRECTORY_SEPARATOR, '', (string) $file);

            if (! in_array($relative, self::UNRENDERED, true)) {
                $views[] = $relative;
            }
        }

        sort($views);

        return $views;
    }

    /**
     * Strips everything that is code, so what is left is what a reader sees.
     * Interpolations become a placeholder rather than vanishing, because
     * `Signed in as {{ $user->name }}` is a hard-coded label and the `->` inside
     * it would otherwise look like a tag boundary.
     */
    private function readableText(string $blade): string
    {
        $patterns = [
            '/\{\{--.*?--\}\}/s' => '',
            '/<!--.*?-->/s' => '',
            '/<(script|style)\b[^>]*>.*?<\/\1>/si' => '',
            '/\{\{.*?\}\}|\{!!.*?!!\}/s' => '',
            '/@(php|verbatim).*?@end\1/s' => '',
            '/@[a-zA-Z]+\s*\((?:[^()]|\([^()]*\))*\)/' => '',
            '/@[a-zA-Z]+/' => '',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $blade = (string) preg_replace($pattern, $replacement, $blade);
        }

        return $blade;
    }

    /**
     * A comparison or an arrow inside a Blade expression -- `$y >= date('Y') - 2`,
     * `$user->name` -- closes a text node early, leaving the tail of the
     * expression looking exactly like a label. These are the shapes that gives.
     */
    private function isCodeFragment(string $text): bool
    {
        foreach (['->', '$', '==', '=>', '&&', '||', '::'] as $token) {
            if (str_contains($text, $token)) {
                return true;
            }
        }

        if (substr_count($text, ')') !== substr_count($text, '(')) {
            return true;
        }

        return preg_match('/^[a-z][a-z0-9]*(_[a-z0-9]+)+$/', $text) === 1;
    }

    private function isReadableLabel(string $text): bool
    {
        $text = trim((string) preg_replace('/\s+|&[a-z]+;|&#\d+;/', ' ', $text));

        if ($text === '' || $this->isCodeFragment($text)) {
            return false;
        }

        return preg_match('/\p{Arabic}/u', $text) === 1 || preg_match('/[A-Za-z]{2,}/', $text) === 1;
    }

    #[Test]
    public function no_view_renders_a_hard_coded_label(): void
    {
        $offenders = [];

        foreach ($this->views() as $view) {
            $readable = $this->readableText(File::get(resource_path('views/'.$view)));

            preg_match_all('/>([^<>]*)</', $readable, $textNodes);
            preg_match_all('/\b(placeholder|title|aria-label|alt)\s*=\s*"([^"]+)"/', $readable, $attributes);

            foreach (array_merge($textNodes[1], $attributes[2]) as $candidate) {
                if ($this->isReadableLabel($candidate)) {
                    $offenders[] = $view.': '.trim($candidate);
                }
            }
        }

        $this->assertSame([], $offenders, "These strings are written into the view instead of a lang file:\n".implode("\n", $offenders));
    }

    #[Test]
    public function no_confirmation_dialog_is_written_in_one_language(): void
    {
        $offenders = [];

        foreach ($this->views() as $view) {
            preg_match_all("/confirm\(\s*'([^']{4,}?)'\s*\)/", File::get(resource_path('views/'.$view)), $matches);

            foreach ($matches[1] as $message) {
                $offenders[] = $view.': '.$message;
            }
        }

        $this->assertSame([], $offenders, "These confirmation dialogs are hard-coded:\n".implode("\n", $offenders));
    }

    /**
     * @return list<string>
     */
    private function flatten(array $lines, string $prefix = ''): array
    {
        $keys = [];

        foreach ($lines as $key => $value) {
            $keys = array_merge($keys, is_array($value)
                ? $this->flatten($value, $prefix.$key.'.')
                : [$prefix.$key]);
        }

        return $keys;
    }

    #[Test]
    public function both_catalogues_carry_the_same_keys(): void
    {
        $files = array_map(
            fn (string $path): string => basename($path),
            File::files(lang_path('en'))
        );

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $english = $this->flatten(require lang_path('en/'.$file));
            $arabic = $this->flatten(require lang_path('ar/'.$file));

            sort($english);
            sort($arabic);

            $this->assertSame($english, $arabic, "lang/en/{$file} and lang/ar/{$file} have drifted apart.");
        }
    }

    /**
     * A key that reads the same in both languages has almost always been copied
     * across without being translated. The exceptions are deliberate: proper
     * nouns, file extensions, and the labels on printed forms that are the same
     * on the paper originals.
     */
    #[Test]
    public function arabic_is_actually_translated(): void
    {
        $untranslated = [];

        foreach (File::files(lang_path('en')) as $file) {
            $name = basename($file);
            $english = require lang_path('en/'.$name);
            $arabic = require lang_path('ar/'.$name);

            foreach ($this->flatten($english) as $key) {
                $source = data_get($english, $key);
                $target = data_get($arabic, $key);

                if (! is_string($source) || ! is_string($target) || $source !== $target) {
                    continue;
                }

                if (preg_match('/^[\W\d]*$/u', $source) === 1 || preg_match('/\p{Arabic}/u', $source) === 1) {
                    continue;
                }

                $untranslated[] = $name.':'.$key.' = '.$source;
            }
        }

        $expected = [
            'appraisals.php:form_3_admin_fin = FORM_3_ADMIN_FIN',
            'flights.php:manifest_company_header = ZUEITINA OIL COMPANY',
            'reports.php:excel = Excel',
            'ui.php:csv_utf_8 = CSV UTF-8',
            'ui.php:xlsx = .xlsx',
        ];

        sort($untranslated);
        sort($expected);

        $this->assertSame($expected, $untranslated, 'These keys were copied into the Arabic catalogue rather than translated.');
    }
}
