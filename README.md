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
- initQueries: array of strings (optional) - SQL queries to execute on connection initialization

#### Timezone Configuration

By default, the extractor uses the MySQL server's session timezone setting. To ensure consistent UTC timestamps across all environments (especially when using Azure MySQL), you can set the session timezone explicitly using `initQueries`:

```json
{
  "db": {
    "host": "your-host",
    "port": "3306",
    "user": "your-user",
    "#password": "your-password",
    "database": "your-database",
    "initQueries": [
      "SET SESSION time_zone = '+00:00'"
    ]
  }
}
```

This is particularly important when:
- Extracting TIMESTAMP columns (which are converted based on session timezone)
- Working with Azure MySQL instances that may default to non-UTC timezones
- Ensuring consistent behavior across different cloud providers (AWS, GCP, Azure)

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
- enabled: boolean (optional)
- retries: integer (optional) number of times to retry failures
- convertBin2hex: boolean (optional) convert binary fields to hex (table option must be configured)

## License

MIT licensed, see [LICENSE](./LICENSE) file.
