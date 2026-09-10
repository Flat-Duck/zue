<?php

namespace Tests\Unit\Legacy;

use App\Services\Legacy\LegacyDumpReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LegacyDumpReaderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/legacy-reader-'.uniqid();
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->directory);

        parent::tearDown();
    }

    public function test_it_reads_the_table_and_column_names_from_the_insert_header(): void
    {
        $reader = $this->write("INSERT INTO `employees` (`id`, `number`, `english_name`) VALUES\n  (1, 2, 'A')\n;\n");

        $this->assertSame('employees', $reader->table());
        $this->assertSame(['id', 'number', 'english_name'], $reader->columns());
    }

    public function test_it_yields_rows_keyed_by_column_name(): void
    {
        $reader = $this->write("INSERT INTO `t` (`id`, `name`) VALUES\n  (1, 'Alpha'),\n  (2, 'Beta')\n;\n");

        $this->assertSame(
            [['id' => '1', 'name' => 'Alpha'], ['id' => '2', 'name' => 'Beta']],
            iterator_to_array($reader->rows(), false),
        );
    }

    public function test_it_distinguishes_the_null_keyword_from_the_literal_string(): void
    {
        $reader = $this->write("INSERT INTO `t` (`a`, `b`) VALUES\n  (NULL, 'NULL')\n;\n");

        $rows = iterator_to_array($reader->rows(), false);

        $this->assertNull($rows[0]['a']);
        $this->assertSame('NULL', $rows[0]['b']);
    }

    public function test_it_unescapes_backslash_sequences_so_json_columns_survive(): void
    {
        $reader = $this->write("INSERT INTO `t` (`settings`) VALUES\n  ('{\\\"job_title\\\": null}')\n;\n");

        $rows = iterator_to_array($reader->rows(), false);

        $this->assertSame('{"job_title": null}', $rows[0]['settings']);
        $this->assertIsArray(json_decode($rows[0]['settings'], true));
    }

    public function test_it_handles_quotes_inside_values(): void
    {
        $reader = $this->write("INSERT INTO `t` (`a`, `b`) VALUES\n  ('O\\'Brien', 'He said ''hi'''),\n  ('comma, inside', 'x')\n;\n");

        $rows = iterator_to_array($reader->rows(), false);

        $this->assertSame("O'Brien", $rows[0]['a']);
        $this->assertSame("He said 'hi'", $rows[0]['b']);
        $this->assertSame('comma, inside', $rows[1]['a']);
    }

    public function test_it_rejects_a_row_whose_column_count_does_not_match_the_header(): void
    {
        $reader = $this->write("INSERT INTO `t` (`a`, `b`) VALUES\n  (1)\n;\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Column count mismatch');

        iterator_to_array($reader->rows(), false);
    }

    public function test_it_rejects_a_file_without_an_insert_header(): void
    {
        $reader = $this->write("SELECT 1;\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unrecognised INSERT header');

        $reader->table();
    }

    private function write(string $contents): LegacyDumpReader
    {
        $path = $this->directory.'/000001_t.sql';
        file_put_contents($path, $contents);

        return new LegacyDumpReader($path);
    }
}
