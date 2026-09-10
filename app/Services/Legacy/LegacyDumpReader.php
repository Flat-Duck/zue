<?php

namespace App\Services\Legacy;

use Generator;
use RuntimeException;

/**
 * Streams one converted legacy dump file.
 *
 * Each file holds a single multi-row `INSERT INTO <table> (<cols>) VALUES` statement
 * with one tuple per line. The file is read line by line and never held in memory,
 * because the time_sheets dumps run to hundreds of thousands of rows apiece.
 */
class LegacyDumpReader
{
    private ?string $table = null;

    /** @var list<string> */
    private array $columns = [];

    public function __construct(private readonly string $path) {}

    public function path(): string
    {
        return $this->path;
    }

    public function table(): string
    {
        $this->readHeader();

        return $this->table ?? throw new RuntimeException("Dump file has no INSERT header: {$this->path}");
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        $this->readHeader();

        return $this->columns;
    }

    /**
     * @return Generator<int, array<string, string|null>>
     */
    public function rows(): Generator
    {
        $handle = fopen($this->path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open dump file: {$this->path}");
        }

        try {
            $columns = null;
            $pending = '';

            while (($line = fgets($handle)) !== false) {
                $line = $this->stripBom(rtrim($line, "\r\n"));

                if ($columns === null) {
                    if (trim($line) === '') {
                        continue;
                    }

                    $columns = $this->parseHeader($line)['columns'];

                    continue;
                }

                $pending = $pending === '' ? $line : $pending."\n".$line;
                $trimmed = trim($pending);

                if ($trimmed === '' || $trimmed === ';') {
                    $pending = '';

                    continue;
                }

                [$values, $unterminated] = $this->parseTuple($trimmed);

                if ($unterminated) {
                    continue;
                }

                $pending = '';

                if ($values === []) {
                    continue;
                }

                if (count($values) !== count($columns)) {
                    throw new RuntimeException(sprintf(
                        'Column count mismatch in %s: expected %d, got %d.',
                        basename($this->path),
                        count($columns),
                        count($values),
                    ));
                }

                yield array_combine($columns, $values);
            }
        } finally {
            fclose($handle);
        }
    }

    private function readHeader(): void
    {
        if ($this->table !== null) {
            return;
        }

        $handle = fopen($this->path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open dump file: {$this->path}");
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = $this->stripBom(trim($line));

                if ($line === '') {
                    continue;
                }

                $header = $this->parseHeader($line);
                $this->table = $header['table'];
                $this->columns = $header['columns'];

                return;
            }
        } finally {
            fclose($handle);
        }

        throw new RuntimeException("Dump file is empty: {$this->path}");
    }

    /**
     * @return array{table: string, columns: list<string>}
     */
    private function parseHeader(string $line): array
    {
        if (! preg_match('/^INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?\s*\((.+)\)\s*VALUES/i', trim($line), $matches)) {
            throw new RuntimeException('Unrecognised INSERT header in '.basename($this->path).': '.substr($line, 0, 120));
        }

        $columns = array_map(
            static fn (string $column): string => trim($column, " \t`"),
            explode(',', $matches[2]),
        );

        return ['table' => $matches[1], 'columns' => $columns];
    }

    /**
     * Parses a single `(...)` tuple into raw string values.
     *
     * The second element of the return value reports that the tuple ended while still
     * inside a quoted string, which means the row is wrapped over further lines.
     *
     * @return array{0: list<string|null>, 1: bool}
     */
    private function parseTuple(string $line): array
    {
        $open = strpos($line, '(');

        if ($open === false) {
            return [[], false];
        }

        $body = substr($line, $open + 1);
        $body = preg_replace('/\)\s*[,;]?\s*$/', '', $body) ?? $body;

        return $this->parseValues($body);
    }

    /**
     * @return array{0: list<string|null>, 1: bool}
     */
    private function parseValues(string $body): array
    {
        $values = [];
        $length = strlen($body);
        $index = 0;

        while ($index < $length) {
            while ($index < $length && ($body[$index] === ' ' || $body[$index] === "\t")) {
                $index++;
            }

            if ($index >= $length) {
                break;
            }

            if ($body[$index] === "'") {
                $index++;
                $buffer = '';
                $closed = false;

                while ($index < $length) {
                    $character = $body[$index];

                    if ($character === '\\' && $index + 1 < $length) {
                        $buffer .= $this->unescape($body[$index + 1]);
                        $index += 2;

                        continue;
                    }

                    if ($character === "'") {
                        if ($index + 1 < $length && $body[$index + 1] === "'") {
                            $buffer .= "'";
                            $index += 2;

                            continue;
                        }

                        $index++;
                        $closed = true;

                        break;
                    }

                    $buffer .= $character;
                    $index++;
                }

                if (! $closed) {
                    return [[], true];
                }

                $values[] = $buffer;
            } else {
                $start = $index;

                while ($index < $length && $body[$index] !== ',') {
                    $index++;
                }

                $raw = trim(substr($body, $start, $index - $start));
                $values[] = strcasecmp($raw, 'NULL') === 0 ? null : $raw;
            }

            while ($index < $length && ($body[$index] === ' ' || $body[$index] === "\t")) {
                $index++;
            }

            if ($index < $length && $body[$index] === ',') {
                $index++;
            }
        }

        return [$values, false];
    }

    private function unescape(string $character): string
    {
        return match ($character) {
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            'b' => chr(8),
            'Z' => chr(26),
            '0' => "\0",
            default => $character,
        };
    }

    private function stripBom(string $line): string
    {
        return str_starts_with($line, "\xEF\xBB\xBF") ? substr($line, 3) : $line;
    }
}
