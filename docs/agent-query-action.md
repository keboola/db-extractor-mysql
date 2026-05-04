# `query` sync action — agent guide

This component exposes a synchronous **`query`** action for use by an LLM agent that is configuring the MySQL extractor on behalf of a user. The action lets the agent execute an arbitrary SQL statement against the configured database and receive the result rows as JSON in the action response.

It is intended for **fast, exploratory introspection**: enumerate schemas and tables, peek at column types, sample a handful of rows, and validate that the configured connection actually returns the expected data — before committing to a long-running extraction (`action: "run"`).

## Prerequisites

The `query` action reuses the exact same `db` configuration as `testConnection`, `getTables`, and `run`. Set up authentication first; see the **Configuration Options** section in the project [README](../README.md) for the full schema of the `db` node (host, port, user, `#password`, optional `ssl`, optional `ssh`, optional `transactionIsolationLevel`, etc.).

A typical workflow:

1. Call `testConnection` to confirm the credentials work.
2. Call `query` repeatedly to introspect the database.
3. Once the agent knows what the user wants, write the real `run` configuration.

## Invocation

Set `action` to `"query"` and put the SQL string in `parameters.query`:

```json
{
  "action": "query",
  "parameters": {
    "db": {
      "host": "mysql.example.com",
      "port": 3306,
      "user": "extractor",
      "#password": "***",
      "database": "shop"
    },
    "query": "SELECT table_name, table_rows FROM information_schema.tables WHERE table_schema = DATABASE() LIMIT 50"
  }
}
```

The component returns its result as a single JSON document on stdout.

## Response shape

```json
{
  "status": "success",
  "rows": [
    { "id": 1, "name": "John" },
    { "id": 2, "name": "Jane" }
  ]
}
```

- `rows` is an array of objects, one per result row, with **column names as keys** and the raw PDO-decoded scalar/null as the value. No reshaping is performed: numeric columns may arrive as strings if the underlying MySQL driver returns them that way (this is normal for `mysqlnd` without `PDO::ATTR_STRINGIFY_FETCHES` overrides).
- An empty result set returns `"rows": []`.
- On error (bad SQL, connection failure, missing `parameters.query`, etc.) the component exits non-zero with a `UserException` whose message contains the underlying database error.

## Limits and guidance

There is **no server-side row cap and no read-only enforcement**. Both are deliberately the agent's responsibility:

- **Always include a `LIMIT`** on exploratory queries. Sync-action responses travel through the Keboola platform's stdout channel and very large payloads will be truncated or rejected. A few hundred rows is generally safe; tens of thousands is not.
- The action will execute `INSERT`, `UPDATE`, `DELETE`, and DDL statements if you send them — there is no whitelist. Don't. Use this action for `SELECT` against `information_schema` or against the user's tables. Writes belong in a real transformation, not in agent introspection.
- Don't run analytical queries that scan the whole table. If you need cardinality, use `information_schema.tables.table_rows` (approximate) or `SELECT COUNT(*) FROM ... LIMIT 1` only when you have already checked the table is small.
- The connection reuses the same SSH-tunnel and SSL setup as `run`, so the action is safe to call against production databases that are only reachable via tunnel.

## Recommended discovery sequence

1. **Enumerate tables in the configured database**
   ```sql
   SELECT table_name, table_rows
   FROM information_schema.tables
   WHERE table_schema = DATABASE()
   ORDER BY table_rows DESC
   LIMIT 50
   ```
2. **Inspect columns of a candidate table**
   ```sql
   SELECT column_name, data_type, is_nullable, column_key
   FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'orders'
   ORDER BY ordinal_position
   ```
3. **Sample a few rows to verify content and types**
   ```sql
   SELECT * FROM orders LIMIT 5
   ```
4. **Check that an incremental-fetching column is monotonic**
   ```sql
   SELECT MIN(updated_at), MAX(updated_at), COUNT(*) FROM orders
   ```

After this loop, the agent has enough information to write the real row-based `run` config (with `table`, `columns`, `incrementalFetchingColumn`, etc.) without trial-and-error.

## Related actions

- `testConnection` — boolean health check, returns `{"status":"success"}` on success.
- `getTables` — preformatted catalog dump (`{tables: [...], status: "success"}`); cheaper than `query` for a one-shot listing but offers no filtering.
- `run` — the actual extraction (asynchronous). Not for use during agent configuration.
