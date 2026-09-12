#!/usr/bin/env python3
"""
Convert a full legacy MySQL/MSSQL backup into the chunk files consumed by
`php artisan legacy:import`.

The script streams the source backup and writes only tables that the current
Laravel importer knows how to preserve or reshape. It does not execute SQL and it
never loads the whole dump into memory.

Example:
  python3 database/seeders/sql_dump/convert_legacy_backup_to_current_chunks.py \
    --input /Users/mahidwei/Downloads/backup_20260912_073135.sql \
    --output-dir database/seeders/sql_dump/converted-from-backup \
    --clean-output

Then dry-run the Laravel import:
  php artisan legacy:import --path=database/seeders/sql_dump/converted-from-backup --dry-run
"""

from __future__ import annotations

import argparse
import json
import shutil
from pathlib import Path
from typing import Iterable

from convert_new_sql_to_chunks import (
    ActiveWriter,
    close_active,
    detect_encoding,
    iter_insert_rows,
    iter_insert_statements,
    normalize_table,
    parse_insert,
    start_new_file,
)

DEFAULT_TABLES = {
    "locations",
    "administrations",
    "centers",
    "departments",
    "appraisal_forms",
    "appraisal_items",
    "appraisal_form_versions",
    "appraisal_form_version_items",
    "appraisal_periods",
    "appraisal_reviews",
    "appraisal_review_scores",
    "appraisals_official",
    "appraisal_official_scores",
    "approval_flows",
    "approval_flow_steps",
    "employees",
    "users",
    "roles",
    "model_has_roles",
    "signatures",
    "management_scopes",
    "management_scope_manager",
    "scope_policies",
    "scope_policy_actors",
    "timesheet_approval_steps",
    "time_sheets",
}


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Stream a legacy backup into current legacy:import chunk files."
    )
    parser.add_argument("--input", required=True, help="Path to the full SQL backup.")
    parser.add_argument(
        "--output-dir",
        default="database/seeders/sql_dump/converted-from-backup",
        help="Directory that will receive NNNNNN_table.sql chunk files.",
    )
    parser.add_argument(
        "--tables",
        default=",".join(sorted(DEFAULT_TABLES)),
        help="Comma separated table allow-list. Defaults to the current importer set.",
    )
    parser.add_argument("--chunk-rows", type=int, default=500000)
    parser.add_argument("--start-index", type=int, default=1)
    parser.add_argument(
        "--encoding",
        default="auto",
        choices=["auto", "utf-8", "utf-8-sig", "utf-16"],
    )
    parser.add_argument(
        "--clean-output",
        action="store_true",
        help="Delete existing .sql and manifest files in the output directory first.",
    )
    parser.add_argument(
        "--manifest",
        default="manifest.json",
        help="Manifest filename written inside the output directory.",
    )
    return parser.parse_args()


def normalized_tables(raw: str) -> set[str]:
    return {normalize_table(part.strip()) for part in raw.split(",") if part.strip()}


def clean_output_dir(output_dir: Path) -> None:
    output_dir.mkdir(parents=True, exist_ok=True)

    for path in output_dir.iterdir():
        if path.is_file() and (path.suffix == ".sql" or path.name.endswith(".json")):
            path.unlink()
        elif path.is_dir() and path.name == "__pycache__":
            shutil.rmtree(path)


def convert_selected_tables(
    source_path: Path,
    output_dir: Path,
    table_allow_list: set[str],
    chunk_rows: int,
    start_index: int,
    encoding: str,
) -> dict[str, object]:
    output_dir.mkdir(parents=True, exist_ok=True)

    active: ActiveWriter | None = None
    current_key: tuple[str, tuple[str, ...]] | None = None
    file_index = start_index
    total_rows = 0
    generated_files: list[str] = []
    included_rows: dict[str, int] = {}
    skipped_insert_tables: dict[str, int] = {}

    try:
        for line_no, statement in iter_insert_statements(source_path, encoding):
            parsed = parse_insert(statement, line_no)

            if parsed.table not in table_allow_list:
                skipped_insert_tables[parsed.table] = skipped_insert_tables.get(parsed.table, 0) + 1
                continue

            key = (parsed.table, parsed.columns)

            for values in iter_insert_rows(parsed):
                if active is None or active.rows_in_file >= chunk_rows or key != current_key:
                    if active is not None:
                        generated_files.append(active.file_path.name)
                        close_active(active)

                    active = start_new_file(
                        output_dir,
                        file_index,
                        ".sql",
                        parsed.table,
                        parsed.columns,
                    )
                    file_index += 1
                    current_key = key

                row_sql = f"({', '.join(values)})"
                active.handle.write(("  " if active.rows_in_file == 0 else ",\n  ") + row_sql)
                active.rows_in_file += 1
                total_rows += 1
                included_rows[parsed.table] = included_rows.get(parsed.table, 0) + 1

                if total_rows % 100000 == 0:
                    print(f"Processed {total_rows:,} rows...")
    finally:
        if active is not None:
            generated_files.append(active.file_path.name)
            close_active(active)

    return {
        "source": str(source_path),
        "output_dir": str(output_dir),
        "included_tables": dict(sorted(included_rows.items())),
        "skipped_insert_tables": dict(sorted(skipped_insert_tables.items())),
        "generated_files": generated_files,
        "total_rows": total_rows,
    }


def main() -> None:
    args = parse_args()
    source_path = Path(args.input)
    output_dir = Path(args.output_dir)

    if not source_path.is_file():
        raise SystemExit(f"Input backup not found: {source_path}")

    if args.clean_output:
        clean_output_dir(output_dir)
    else:
        output_dir.mkdir(parents=True, exist_ok=True)

    detected = detect_encoding(source_path)
    encoding = detected if args.encoding == "auto" else args.encoding
    tables = normalized_tables(args.tables)

    manifest = convert_selected_tables(
        source_path=source_path,
        output_dir=output_dir,
        table_allow_list=tables,
        chunk_rows=args.chunk_rows,
        start_index=args.start_index,
        encoding=encoding,
    )
    manifest["encoding"] = encoding
    manifest["allowed_tables"] = sorted(tables)

    manifest_path = output_dir / args.manifest
    manifest_path.write_text(json.dumps(manifest, indent=2, ensure_ascii=False), encoding="utf-8")

    print(f"Done. Wrote {manifest['total_rows']:,} rows to {output_dir}")
    print(f"Manifest: {manifest_path}")


if __name__ == "__main__":
    main()
