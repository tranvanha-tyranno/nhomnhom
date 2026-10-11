"""Export the demo SQLite database to a portable MySQL 8 SQL dump."""
from __future__ import annotations

import argparse
import datetime as dt
import sqlite3
from pathlib import Path


SKIP_TABLES = {
    "restaurant_search",
    "restaurant_search_config",
    "restaurant_search_content",
    "restaurant_search_data",
    "restaurant_search_docsize",
    "restaurant_search_idx",
}
LONG_TEXT = {"description", "body", "metadata_json", "note", "content"}


def quote_identifier(value: str) -> str:
    return "`" + value.replace("`", "``") + "`"


def sql_value(value: object) -> str:
    if value is None:
        return "NULL"
    if isinstance(value, (int, float)) and not isinstance(value, bool):
        return str(value)
    if isinstance(value, bytes):
        return "X'" + value.hex() + "'"
    text = str(value).replace("\\", "\\\\").replace("'", "\\'")
    text = text.replace("\x00", "\\0").replace("\n", "\\n").replace("\r", "\\r").replace("\x1a", "\\Z")
    return "'" + text + "'"


def mysql_type(column: dict[str, object]) -> str:
    name = str(column["name"])
    raw = str(column["type"]).upper()
    if name in {"created_at", "updated_at", "completed_at", "joined_at", "applied_at", "expires_at", "booking_date"}:
        return "DATETIME"
    if name == "session_key":
        return "CHAR(64)"
    if name == "invite_code":
        return "CHAR(8)"
    if name == "email":
        return "VARCHAR(254)"
    if name in {"url", "slug"}:
        return "VARCHAR(512)"
    if name in LONG_TEXT:
        return "LONGTEXT"
    if raw.startswith("REAL"):
        return "DOUBLE"
    if raw.startswith("INT"):
        return "BIGINT"
    return "VARCHAR(255)"


def table_names(connection: sqlite3.Connection) -> list[str]:
    rows = connection.execute(
        "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY rowid"
    ).fetchall()
    return [str(row[0]) for row in rows if str(row[0]) not in SKIP_TABLES]


def columns(connection: sqlite3.Connection, table: str) -> list[dict[str, object]]:
    return [dict(row) for row in connection.execute(f"PRAGMA table_info({quote_identifier(table)})").fetchall()]


def create_table_sql(connection: sqlite3.Connection, table: str) -> str:
    table_columns = columns(connection, table)
    definitions: list[str] = []
    primary = [column for column in table_columns if int(column["pk"]) > 0]
    for column in table_columns:
        definition = f"{quote_identifier(str(column['name']))} {mysql_type(column)}"
        if int(column["pk"]) == 1 and len(primary) == 1 and str(column["type"]).upper().startswith("INTEGER"):
            definition += " AUTO_INCREMENT"
        if int(column["notnull"]) and int(column["pk"]) == 0:
            definition += " NOT NULL"
        default = column["dflt_value"]
        if default is not None:
            default_text = str(default)
            if default_text.upper() in {"CURRENT_TIMESTAMP", "CURRENT_DATE"}:
                definition += " DEFAULT CURRENT_TIMESTAMP"
            elif default_text.startswith("'"):
                definition += " DEFAULT " + default_text.replace("CURRENT_TIMESTAMP", "CURRENT_TIMESTAMP")
            elif default_text in {"0", "1"}:
                definition += " DEFAULT " + default_text
        definitions.append(definition)
    if primary:
        definitions.append("PRIMARY KEY (" + ", ".join(quote_identifier(str(c["name"])) for c in sorted(primary, key=lambda x: int(x["pk"]))) + ")")
    primary_names = {str(c["name"]) for c in primary}
    for index in connection.execute(f"PRAGMA index_list({quote_identifier(table)})").fetchall():
        index_name = str(index[1])
        unique = int(index[2])
        partial = int(index[4]) if len(index) > 4 else 0
        if not unique or partial:
            continue
        columns_in_index = [str(row[2]) for row in connection.execute(f"PRAGMA index_info({quote_identifier(index_name)})").fetchall()]
        if columns_in_index and set(columns_in_index) != primary_names:
            index_label = index_name if not index_name.startswith("sqlite_autoindex") else "uniq_" + table + "_" + "_".join(columns_in_index)
            definitions.append("UNIQUE KEY " + quote_identifier(index_label) + " (" + ", ".join(quote_identifier(c) for c in columns_in_index) + ")")
    return "CREATE TABLE IF NOT EXISTS " + quote_identifier(table) + " (\n  " + ",\n  ".join(definitions) + "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"


def foreign_key_sql(connection: sqlite3.Connection, table: str) -> list[str]:
    statements = []
    for row in connection.execute(f"PRAGMA foreign_key_list({quote_identifier(table)})").fetchall():
        constraint = f"fk_{table}_{row[3]}_{row[4]}"
        on_delete = str(row[6]).upper()
        suffix = " ON DELETE " + on_delete if on_delete in {"CASCADE", "SET NULL", "RESTRICT"} else ""
        statements.append(
            f"ALTER TABLE {quote_identifier(table)} ADD CONSTRAINT {quote_identifier(constraint)} "
            f"FOREIGN KEY ({quote_identifier(str(row[3]))}) REFERENCES {quote_identifier(str(row[2]))} ({quote_identifier(str(row[4]))}){suffix};"
        )
    return statements


def index_sql(connection: sqlite3.Connection, table: str) -> list[str]:
    statements = []
    primary_names = {str(column["name"]) for column in columns(connection, table) if int(column["pk"]) > 0}
    for index in connection.execute(f"PRAGMA index_list({quote_identifier(table)})").fetchall():
        index_name = str(index[1])
        unique = int(index[2])
        partial = int(index[4]) if len(index) > 4 else 0
        if unique or index_name.startswith("sqlite_autoindex") or partial:
            continue
        index_columns = [str(row[2]) for row in connection.execute(f"PRAGMA index_info({quote_identifier(index_name)})").fetchall()]
        if index_columns and set(index_columns) != primary_names:
            statements.append(f"CREATE INDEX {quote_identifier(index_name)} ON {quote_identifier(table)} (" + ", ".join(quote_identifier(c) for c in index_columns) + ");")
    return statements


def insert_statements(connection: sqlite3.Connection, table: str, batch_size: int = 500):
    table_columns = [str(column["name"]) for column in columns(connection, table)]
    quoted_columns = ", ".join(quote_identifier(column) for column in table_columns)
    cursor = connection.execute(f"SELECT {', '.join(quote_identifier(column) for column in table_columns)} FROM {quote_identifier(table)}")
    while True:
        rows = cursor.fetchmany(batch_size)
        if not rows:
            break
        values = ["(" + ", ".join(sql_value(value) for value in row) + ")" for row in rows]
        yield f"INSERT INTO {quote_identifier(table)} ({quoted_columns}) VALUES\n" + ",\n".join(values) + ";"


def write_dump(source: Path, target: Path) -> None:
    connection = sqlite3.connect(source)
    connection.row_factory = sqlite3.Row
    tables = table_names(connection)
    target.parent.mkdir(parents=True, exist_ok=True)
    with target.open("w", encoding="utf-8", newline="\n") as output:
        output.write("-- NhomNhom MySQL 8 dump generated from SQLite\n")
        output.write("-- Generated at " + dt.datetime.now(dt.UTC).isoformat() + "\n")
        output.write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n")
        output.write("CREATE DATABASE IF NOT EXISTS `nhomnhom` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE `nhomnhom`;\n\n")
        for table in tables:
            output.write(create_table_sql(connection, table) + "\n")
        output.write("\n-- Foreign keys\n")
        for table in tables:
            for statement in foreign_key_sql(connection, table):
                output.write(statement + "\n")
        output.write("\n-- Indexes\n")
        for table in tables:
            for statement in index_sql(connection, table):
                output.write(statement + "\n")
        output.write("\n-- Data\n")
        for table in tables:
            output.write(f"\n-- {table}\n")
            for statement in insert_statements(connection, table):
                output.write(statement + "\n")
        output.write("\nCREATE OR REPLACE VIEW `restaurant_rating_summary` AS SELECT id AS restaurant_id, review_count, CASE WHEN review_count=0 THEN 0.0 ELSE ROUND(rating_sum/review_count, 1) END AS average_rating FROM restaurants;\n")
        output.write("SET FOREIGN_KEY_CHECKS=1;\n")
    connection.close()


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--source", type=Path, default=Path("data/nhomnhom.sqlite"))
    parser.add_argument("--target", type=Path, default=Path("database/mysql/nhomnhom.sql"))
    args = parser.parse_args()
    write_dump(args.source, args.target)
    print(f"Wrote {args.target}")


if __name__ == "__main__":
    main()
