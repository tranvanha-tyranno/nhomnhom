# MySQL/XAMPP database

`nhomnhom.sql` is a complete MySQL/MariaDB dump generated from the current
SQLite demo database. It includes the catalog, community data, ratings, users,
menus and empty `nomnom_*` feature tables. SQLite FTS5 internal tables are not
included because the PHP MySQL path uses accent-insensitive `LIKE` queries.

## Import with XAMPP

1. Start **Apache** and **MySQL** in XAMPP.
2. Open `http://localhost/phpmyadmin`.
3. Import `database/mysql/nhomnhom.sql`. The dump creates and selects a database
   named `nhomnhom`; use the Import tab and keep the file encoding as UTF-8.
4. Copy the whole project directory to `C:\xampp\htdocs\nhomnhom`.
5. Set these Apache environment variables in `C:\xampp\apache\conf\httpd.conf`
   or the VirtualHost that serves the project:

```apache
SetEnv NHOMNHOM_DB_DRIVER "mysql"
SetEnv NHOMNHOM_DB_HOST "127.0.0.1"
SetEnv NHOMNHOM_DB_PORT "3306"
SetEnv NHOMNHOM_DB_NAME "nhomnhom"
SetEnv NHOMNHOM_DB_USER "root"
SetEnv NHOMNHOM_DB_PASSWORD ""
```

Restart Apache after changing the variables, then open
`http://localhost/nhomnhom/`. XAMPP PHP must have `pdo_mysql` enabled.

Restaurant filters, menu details and saved restaurants also support this driver.
`api/saved.php` creates `nomnom_saved_restaurants` on first use, so the database
account needs CREATE TABLE permission. Existing installations do not need to
re-import the catalog dump. Saved entries are scoped to the current PHP session.
See the root README for the new endpoints and localhost HTTP smoke test.

## Regenerate the dump

The export is repeatable and reads the local SQLite source:

```powershell
python scripts/export_mysql.py
```

The default output is `database/mysql/nhomnhom.sql`. The generated dump is a
deployment artifact; do not edit it by hand. Re-run the exporter after changing
the SQLite source data.
