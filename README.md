# MySQL DB Extractor
[![GitHub Actions](https://github.com/keboola/db-extractor-mysql/actions/workflows/push.yml/badge.svg)](https://github.com/keboola/db-extractor-mysql/actions/workflows/push.yml)

### Development

- Clone the repository.
- Create `.env` file with `MYSQL_VERSION=latest`.
- Run `docker compose build`.

#### Tools

- codesniffer: `docker compose run --rm dev composer phpcs` 
- static analysis: `docker compose run --rm dev composer phpstan`
- unit tests: `docker compose run --rm dev composer tests`

#### Configuration Options

The configuration requires a `db` node with the following properties: 

- host: string (required) the hostname of the database
- port: numeric (required)
- user: string (required)
- \#password: string (required)
- networkCompression: boolean 
- ssl: array (optional, but if present, enabled, ca, cert, and key are required)
  - enabled: boolean 
  - ca: string
  - cert: string
  - key: string
  - cipher: string
  - verifyServerCert: boolean (default true)
- ssh: array (optional, but if present, enabled, keys/public, user, and sshHost are required)
  - enabled: boolean 
  - keys: array 
    - \#private: string
    - public: string                
  - user string
  - sshHost string
  - sshPort string
  - maxRetries integer
- transactionIsolationLevel: enum (optional) - possible values `REPEATABLE READ`, `READ COMMITTED`, `READ UNCOMMITTED`, `SERIALIZABLE`
   
There are 2 possible types of table extraction.  
1. A table defined by `schema` and `tableName`, this option can also include a columns list.
2. A `query` which is the SQL SELECT statement to be executed to produce the result table.

The extraction has the following configuration options:

- id: numeric (required),
- name: string (required),
- query: string (optional, but required if table not present)
- table: array (optional, but required if table not present)
  - tableName: string
  - schema: string
- columns: array of strings (only for table type configurations)
- outputTable: string (required)
- incremental: boolean (optional)
- primaryKey: array of strings (optional)
- incrementalFetchingColumn: string (optional)
- incrementalFetchingLimit: integer (optional)
- incrementalFetchingMode: enum (optional) - `watermark` (default) or `window`. `watermark` resumes from the last fetched value; `window` fetches a fixed range and ignores the stored watermark.
- incrementalFetchingLookback: string (optional, `watermark` mode only) - re-fetch this far behind the last fetched value so rows committed below the watermark ("late commits") are not missed. A duration like `20 minutes` for timestamp columns, or a number for numeric columns. A primary key is required so incremental loading can deduplicate the re-fetched rows.
- incrementalFetchingStart: string (optional, `window` mode only) - lower bound of the fetched range, absolute or relative (e.g. `2024-01-01` or `-2 days` for timestamp columns, a number for numeric columns).
- incrementalFetchingEnd: string (optional, `window` mode only) - upper bound of the fetched range (same formats as `incrementalFetchingStart`).
- enabled: boolean (optional)
- retries: integer (optional) number of times to retry failures
- convertBin2hex: boolean (optional) convert binary fields to hex (table option must be configured)
- propagateDescriptions: boolean (optional, default `true`) copy the MySQL table and column `COMMENT` values into the Storage table and column descriptions

## Table and column descriptions

By default the extractor reads the `COMMENT` attribute of the extracted table and of all its
columns on every run, and writes the values to the description of the corresponding table and
columns in Storage. Set `propagateDescriptions` to `false` to turn this off -- no descriptions
are then written at all.

MySQL returns an empty string for a table or column without a `COMMENT`, which is reported as
no description rather than as an empty one.

Views never get a table description. MySQL has no syntax for commenting a view and reports the
literal string `VIEW` in `INFORMATION_SCHEMA.TABLES.TABLE_COMMENT` for every one of them, which
is not a user value. Columns of a view do get descriptions -- MySQL copies the comments over
from the underlying table.

Descriptions are only available when a table is configured via `table`. In advanced query mode
(`query`) the column list comes from the query result itself, which carries no comments, so
nothing is propagated.

## License

MIT licensed, see [LICENSE](./LICENSE) file.
