#!/usr/bin/env python3
"""
Convert SQL dumps into chunked multi-row INSERT files.

Supported input formats:
- MSSQL exports (example: INSERT [dbo].[table] (...) VALUES (...))
- MySQL dumps (example: INSERT INTO `table` (...) VALUES (...), (...);)

Example:
  python3 database/seeders/sql_dump/convert_new_sql_to_chunks.py \
    --input database/seeders/sql_dump/new.sql \
    --output-dir database/seeders/sql_dump/converted \
    --chunk-rows 500000
"""

from __future__ import annotations

import argparse
import codecs
import re
from dataclasses import dataclass
from pathlib import Path
from typing import Iterator, TextIO


MSSQL_INSERT_RE = re.compile(
    r"^INSERT\s+\[dbo\]\.\[([^\]]+)\]\s*\((.*?)\)\s*VALUES\s*\((.*)\)\s*;?\s*$",
    re.IGNORECASE | re.DOTALL,
)

MYSQL_INSERT_RE = re.compile(
    r"^INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?\s*\((.*?)\)\s*VALUES\s*(.+?)\s*;?\s*$",
    re.IGNORECASE,
)

CAST_STRING_RE = re.compile(
    r"^CAST\(\s*N?'((?:''|[^'])*)'\s+AS\s+[A-Za-z0-9_]+\s*\)$",
    re.IGNORECASE,
)

UNICODE_STRING_RE = re.compile(r"^N'((?:''|[^'])*)'$", re.IGNORECASE)


TABLE_RENAMES = {
    "time_sheet": "time_sheets",
}

COLUMN_RENAMES = {
    "employees": {
        "name": "english_name",
    },
    "time_sheets": {
        "val": "value",
        "revised_by": "admin_id",
    },
}


@dataclass
class ActiveWriter:
    handle: TextIO
    table: str
    columns: tuple[str, ...]
    rows_in_file: int
    file_path: Path


@dataclass
class ParsedInsert:
    line_no: int
    source_kind: str  # mssql | mysql
    table: str
    columns: tuple[str, ...]
    values_sql: str


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Convert MSSQL/MySQL dump to chunked multi-row INSERT files."
    )
    parser.add_argument(
        "--input",
        default="database/seeders/sql_dump/new.sql",
        help="Path to source SQL file.",
    )
    parser.add_argument(
        "--output-dir",
        default="database/seeders/sql_dump/converted",
        help="Directory to write output chunk files.",
    )
    parser.add_argument(
        "--chunk-rows",
        type=int,
        default=500000,
        help="Maximum rows per output file.",
    )
    parser.add_argument(
        "--start-index",
        type=int,
        default=1,
        help="Start index for output file naming.",
    )
    parser.add_argument(
        "--extension",
        default=".sql",
        help="Output extension. Default: .sql",
    )
    parser.add_argument(
        "--max-inserts",
        type=int,
        default=0,
        help="For testing: stop after this many INSERT rows (0 = all).",
    )
    parser.add_argument(
        "--encoding",
        default="auto",
        choices=["auto", "utf-8", "utf-8-sig", "utf-16"],
        help="Input encoding. Default: auto",
    )
    parser.add_argument(
        "--table",
        default=None,
        help="Only export this table name (case-insensitive), e.g. --table time_sheets",
    )
    return parser.parse_args()


def split_csv_sql_values(raw: str) -> list[str]:
    parts: list[str] = []
    buff: list[str] = []
    in_string = False
    escape_next = False
    paren_depth = 0
    i = 0
    while i < len(raw):
        ch = raw[i]

        if in_string:
            buff.append(ch)

            if escape_next:
                escape_next = False
                i += 1
                continue

            if ch == "\\":
                escape_next = True
                i += 1
                continue

            if ch == "'":
                if i + 1 < len(raw) and raw[i + 1] == "'":
                    buff.append("'")
                    i += 1
                else:
                    in_string = False
            i += 1
            continue

        if ch == "'":
            buff.append(ch)
            in_string = True
            i += 1
            continue

        if not in_string:
            if ch == "(":
                paren_depth += 1
            elif ch == ")":
                paren_depth -= 1
            elif ch == "," and paren_depth == 0:
                parts.append("".join(buff).strip())
                buff = []
                i += 1
                continue

        buff.append(ch)
        i += 1

    if buff:
        parts.append("".join(buff).strip())
    return parts


def extract_value_tuples(raw: str) -> list[str]:
    tuples: list[str] = []
    in_string = False
    escape_next = False
    depth = 0
    start: int | None = None

    i = 0
    while i < len(raw):
        ch = raw[i]

        if in_string:
            if escape_next:
                escape_next = False
                i += 1
                continue
            if ch == "\\":
                escape_next = True
                i += 1
                continue
            if ch == "'":
                if i + 1 < len(raw) and raw[i + 1] == "'":
                    i += 2
                    continue
                in_string = False
            i += 1
            continue

        if ch == "'":
            in_string = True
            i += 1
            continue

        if ch == "(":
            if depth == 0:
                start = i
            depth += 1
            i += 1
            continue

        if ch == ")":
            depth -= 1
            if depth < 0:
                raise ValueError("Unexpected ')' while parsing VALUES tuples.")
            if depth == 0 and start is not None:
                tuples.append(raw[start : i + 1].strip())
                start = None
            i += 1
            continue

        i += 1

    if depth != 0 or in_string:
        raise ValueError("Unbalanced VALUES tuple block.")

    return tuples


def has_unquoted_semicolon(text: str) -> bool:
    in_string = False
    escape_next = False
    i = 0
    while i < len(text):
        ch = text[i]
        if in_string:
            if escape_next:
                escape_next = False
                i += 1
                continue
            if ch == "\\":
                escape_next = True
                i += 1
                continue
            if ch == "'":
                if i + 1 < len(text) and text[i + 1] == "'":
                    i += 2
                    continue
                in_string = False
            i += 1
            continue

        if ch == "'":
            in_string = True
        elif ch == ";":
            return True

        i += 1

    return False


def detect_encoding(source_path: Path) -> str:
    with source_path.open("rb") as source:
        head = source.read(4)

    if head.startswith(codecs.BOM_UTF16_LE) or head.startswith(codecs.BOM_UTF16_BE):
        return "utf-16"
    if head.startswith(codecs.BOM_UTF8):
        return "utf-8-sig"
    return "utf-8"


def iter_insert_statements(source_path: Path, encoding: str) -> Iterator[tuple[int, str]]:
    buffer: str | None = None
    buffer_start_line = 0

    with source_path.open("r", encoding=encoding, errors="strict") as source:
        for line_no, line in enumerate(source, start=1):
            statement = line.strip()
            if buffer is None:
                if not statement or not statement.upper().startswith("INSERT "):
                    continue

                # MSSQL dump is one INSERT per line without semicolon.
                if statement.upper().startswith("INSERT [DBO]."):
                    yield line_no, statement
                    continue

                # MySQL INSERT may be multiline and usually ends with ';'
                buffer = statement
                buffer_start_line = line_no
                if ";" in statement and has_unquoted_semicolon(statement):
                    yield buffer_start_line, buffer
                    buffer = None
                    buffer_start_line = 0
                continue

            if not statement:
                continue

            buffer += " " + statement
            if ";" in statement and has_unquoted_semicolon(buffer):
                yield buffer_start_line, buffer
                buffer = None
                buffer_start_line = 0

    if buffer is not None:
        raise ValueError(
            f"Unterminated INSERT statement starting at line {buffer_start_line}"
        )


def normalize_value(token: str) -> str:
    token = token.strip()

    cast_match = CAST_STRING_RE.match(token)
    if cast_match:
        return f"'{cast_match.group(1)}'"

    unicode_match = UNICODE_STRING_RE.match(token)
    if unicode_match:
        return f"'{unicode_match.group(1)}'"

    return token


def normalize_table(table: str) -> str:
    lower = table.strip().lower()
    return TABLE_RENAMES.get(lower, lower)


def normalize_columns(table: str, raw_columns: str) -> tuple[str, ...]:
    rename_map = COLUMN_RENAMES.get(table, {})
    cols: list[str] = []
    for item in split_csv_sql_values(raw_columns):
        col = item.strip()
        if col.startswith("[") and col.endswith("]"):
            col = col[1:-1]
        elif col.startswith("`") and col.endswith("`"):
            col = col[1:-1]
        col = col.strip()
        col = rename_map.get(col.lower(), col.lower())
        cols.append(col)
    return tuple(cols)


def parse_insert(statement: str, line_no: int) -> ParsedInsert:
    mssql_match = MSSQL_INSERT_RE.match(statement)
    if mssql_match:
        raw_table, raw_columns, raw_values = mssql_match.groups()
        table = normalize_table(raw_table)
        columns = normalize_columns(table, raw_columns)
        return ParsedInsert(
            line_no=line_no,
            source_kind="mssql",
            table=table,
            columns=columns,
            values_sql=f"({raw_values.strip()})",
        )

    mysql_match = MYSQL_INSERT_RE.match(statement)
    if mysql_match:
        raw_table, raw_columns, raw_values = mysql_match.groups()
        table = normalize_table(raw_table)
        columns = normalize_columns(table, raw_columns)
        values_sql = raw_values.strip().rstrip(";").strip()
        return ParsedInsert(
            line_no=line_no,
            source_kind="mysql",
            table=table,
            columns=columns,
            values_sql=values_sql,
        )

    raise ValueError(f"Unsupported INSERT format at line {line_no}")


def iter_insert_rows(parsed: ParsedInsert) -> Iterator[list[str]]:
    tuple_blocks: list[str]
    if parsed.source_kind == "mssql":
        tuple_blocks = [parsed.values_sql]
    else:
        tuple_blocks = extract_value_tuples(parsed.values_sql)

    for tuple_sql in tuple_blocks:
        text = tuple_sql.strip()
        if not (text.startswith("(") and text.endswith(")")):
            raise ValueError(
                f"Malformed tuple in {parsed.table} near line {parsed.line_no}"
            )

        values = [normalize_value(v) for v in split_csv_sql_values(text[1:-1])]
        if len(values) != len(parsed.columns):
            raise ValueError(
                f"Column/value mismatch at line {parsed.line_no} "
                f"for table {parsed.table}: {len(parsed.columns)} columns vs "
                f"{len(values)} values"
            )
        yield values


def start_new_file(
    output_dir: Path,
    index: int,
    extension: str,
    table: str,
    columns: tuple[str, ...],
) -> ActiveWriter:
    filename = f"{index:06d}_{table}{extension}"
    file_path = output_dir / filename
    handle = file_path.open("w", encoding="utf-8", newline="\n")
    columns_sql = ", ".join(f"`{c}`" for c in columns)
    handle.write(f"INSERT INTO `{table}` ({columns_sql}) VALUES\n")
    return ActiveWriter(
        handle=handle,
        table=table,
        columns=columns,
        rows_in_file=0,
        file_path=file_path,
    )


def close_active(active: ActiveWriter | None) -> None:
    if active is None:
        return
    active.handle.write("\n;\n")
    active.handle.close()


def convert_dump(
    source_path: Path,
    output_dir: Path,
    chunk_rows: int,
    start_index: int,
    extension: str,
    max_inserts: int,
    encoding: str,
    table: str | None,
) -> None:
    output_dir.mkdir(parents=True, exist_ok=True)

    file_index = start_index
    active: ActiveWriter | None = None
    total_inserts = 0
    generated_files = 0
    current_key: tuple[str, tuple[str, ...]] | None = None
    table_filter = normalize_table(table) if table else None

    for line_no, statement in iter_insert_statements(source_path, encoding):
        parsed = parse_insert(statement, line_no)
        if table_filter and parsed.table != table_filter:
            continue
        key = (parsed.table, parsed.columns)

        for values in iter_insert_rows(parsed):
            if active is None:
                active = start_new_file(
                    output_dir, file_index, extension, parsed.table, parsed.columns
                )
                generated_files += 1
                file_index += 1
                current_key = key
            else:
                needs_new_file = active.rows_in_file >= chunk_rows
                key_changed = key != current_key
                if needs_new_file or key_changed:
                    close_active(active)
                    active = start_new_file(
                        output_dir, file_index, extension, parsed.table, parsed.columns
                    )
                    generated_files += 1
                    file_index += 1
                    current_key = key

            row_sql = f"({', '.join(values)})"
            if active.rows_in_file == 0:
                active.handle.write(f"  {row_sql}")
            else:
                active.handle.write(f",\n  {row_sql}")
            active.rows_in_file += 1

            total_inserts += 1
            if total_inserts % 100000 == 0:
                print(f"Processed {total_inserts} INSERT rows...")

            if max_inserts > 0 and total_inserts >= max_inserts:
                close_active(active)
                print(f"Done. Processed INSERT rows: {total_inserts}")
                print(f"Generated files: {generated_files}")
                print(f"Output directory: {output_dir}")
                return

    close_active(active)
    print(f"Done. Processed INSERT rows: {total_inserts}")
    print(f"Generated files: {generated_files}")
    print(f"Output directory: {output_dir}")
    if table_filter and total_inserts == 0:
        print(f"Warning: no rows found for table '{table_filter}'.")


def main() -> None:
    args = parse_args()
    detected = detect_encoding(Path(args.input))
    chosen_encoding = detected if args.encoding == "auto" else args.encoding
    print(f"Input encoding: {chosen_encoding}")

    convert_dump(
        source_path=Path(args.input),
        output_dir=Path(args.output_dir),
        chunk_rows=args.chunk_rows,
        start_index=args.start_index,
        extension=args.extension,
        max_inserts=args.max_inserts,
        encoding=chosen_encoding,
        table=args.table,
    )


if __name__ == "__main__":
    main()
