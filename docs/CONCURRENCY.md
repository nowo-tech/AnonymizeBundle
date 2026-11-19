# Concurrency (REQ-RUNTIME-003)

How the bundle writes when the application keeps writing to the same database, and what the operator should do about
what it cannot prevent.

## Table of contents

- [Summary](#summary)
- [Write paths](#write-paths)
- [How a chunk is written](#how-a-chunk-is-written)
- [Conflict answer and codes](#conflict-answer-and-codes)
- [Host options](#host-options)
- [Rules 1 to 9](#rules-1-to-9)
- [Residual risks](#residual-risks)
- [Tests and CI](#tests-and-ci)

## Summary

- The bundle writes only from **operator console commands** (`nowo:anonymize:run`). It has no HTTP route, no
  Messenger handler and no Doctrine listener that writes.
- Since 1.7, each chunk of rows is read and written in **one transaction**, run by `nowo-tech/transaction-doctrine`
  (`DoctrineTransactionRunner::forConnection()` on the entity manager's connection).
- Outside a dry run, the chunk's rows are **locked by key** (`SELECT … FOR UPDATE`) between the read and the `UPDATE`.
  An application write to one of those rows waits until the chunk commits.
- Writes go through DBAL only. The bundle never calls `flush()`, `clear()`, `close()` or `resetManager()` on any
  entity manager, so the host's unit of work is never touched.

## Write paths

| Path | Transaction | Concurrency control | Conflict answer |
|------|-------------|---------------------|-----------------|
| `nowo:anonymize:run`, one chunk of one entity (`AnonymizeService::anonymizeEntity()`) | One transaction per chunk (`DoctrineTransactionRunner::forConnection()`); the size is `--batch-size` (default 100) | Keyset read on the primary key (also for composite keys since 1.7), then the chunk's rows read again by key with `FOR UPDATE` (`FOR UPDATE OF t0` on PostgreSQL and MySQL 8+, plain `FOR UPDATE` on MariaDB and MySQL 5.7, no lock on SQLite). `UPDATE … WHERE <primary key> = …` per row | A lock conflict rolls back the current chunk and is rethrown as `Nowo\TransactionContract\TransientStorageFailure`. The command prints a lock-conflict message and exits with `1`. Earlier chunks stay committed |
| `nowo:anonymize:run`, `truncate: true` (`AnonymizeService::truncateTables()`) | None: one autocommit statement per table (`TRUNCATE` is DDL and commits implicitly on MySQL and MariaDB) | The server's table lock: `ACCESS EXCLUSIVE` on PostgreSQL, a metadata lock on MySQL and MariaDB. Each waits for the application's open transactions on the table | The database error is rethrown; the command exits with `1` |
| `nowo:anonymize:run`, `truncate: true` on a polymorphic entity | One autocommit `DELETE … WHERE <discriminator> = …` | Row locks | As above |
| Run history (`AnonymizationHistoryService`) | No database: one JSON file per run, written after the run | A unique run id per file | Not a database write |
| `nowo:anonymize:export-db`, `nowo:anonymize:info`, the generate commands | Read-only, or they write files only | — | — |

## How a chunk is written

For each entity, until the table is done:

1. The runner opens a transaction on the entity manager's connection.
2. Keyset read: `SELECT t0.* … WHERE <key> > <last key> ORDER BY <key> LIMIT <batch size>`. Composite keys use
   `k1 >= v1 AND (k1 > v1 OR k2 > v2)`, so every database can use a range scan. This read takes no lock, so InnoDB
   does not lock the rows it scans past.
3. Outside a dry run, the chunk's rows are read again **by key**, with the lock clause:
   `… WHERE (<key> = 1 OR <key> = 2 …) ORDER BY <key> FOR UPDATE OF t0`. Only those rows are locked, in key order.
   A row the application deleted since step 2 is gone; every other row is its current version.
4. The values are computed from the locked rows: fakers, patterns, row anonymizer services and
   `AnonymizePropertyEvent` listeners. Then one `UPDATE` is run per row.
5. The runner commits. On any failure it rolls back the levels it opened, savepoint-aware, and closes the connection
   only if that rollback fails (DBAL reconnects on the next use). A deadlock, lock wait timeout or serialization
   failure is rethrown as `TransientStorageFailure`, with the DBAL exception as previous. Any other error is
   rethrown unchanged.
6. The progress callback runs after the commit. The cursor is the last row of the keyset read (step 2), even if the
   application deleted that row in the meantime.

What this prevents:

- **Values derived from a stale row.** Without the lock, an application write could land between the read and the
  `UPDATE`. For example, the application erases a customer's e-mail, and the run then writes back a masked or hashed
  form of the old value. It could also skip a column it read as `NULL` that the application had just filled in.
  Since 1.7 that write waits for the chunk's commit.
- **Rows skipped on composite keys.** Before 1.7, tables with a composite primary key were read with `LIMIT/OFFSET`.
  If the application deleted a row of a chunk already done, the next row shifted out of the run and stayed
  un-anonymized. Keyset chunks do not shift.
- **Half-written chunks.** A failure rolls back the whole chunk. Earlier chunks are complete.

## Conflict answer and codes

There is no HTTP, JSON or Messenger path, so no answer is a 409 and no `CONFLICT_001` or `CONFLICT_002` code is
emitted.

The console equivalent of the rule is:

- A lock conflict fails the run with exit code `1` and this message: "Lock conflict (deadlock, lock wait timeout or
  serialization failure) while anonymizing entity manager …: the current chunk was rolled back, earlier chunks are
  committed. Re-run the command …".
- In terms of rule 6 this is `CONFLICT_002`: part of the run is committed. Re-running is safe: every row is
  anonymized again with new values.
- Other database errors, including unique violations and lost connections, are not mapped. They are printed as
  "Error processing entity manager …" and also exit with `1`.

When you call `AnonymizeService::anonymizeEntity()` from your own code, catch
`Nowo\TransactionContract\TransientStorageFailure` for lock conflicts. `getPrevious()` returns the DBAL exception.

## Host options

- **Lock timeouts.** The bundle uses the connection's settings, so set a timeout there. Without one, PostgreSQL waits
  for ever (`lock_timeout = 0`) and InnoDB waits 50 s.
  - PostgreSQL: add `?options=-c%20lock_timeout%3D5s` to the DSN, or set it for the role with
    `ALTER ROLE … SET lock_timeout = '5s'`.
  - MySQL and MariaDB: set `options: { 1002: 'SET SESSION innodb_lock_wait_timeout = 5' }` in the Doctrine
    connection. `1002` is `PDO::MYSQL_ATTR_INIT_COMMAND`.
- **`--batch-size`.** This sets how many rows one transaction locks, and for how long. Lower it on tables the
  application writes to often.
- **Dry run** (`--dry-run`) takes no row lock.
- **Worker mode** (FrankenPHP, kernel reset off): the write path is a console command, so it never runs inside a
  worker request. The service keeps no entity or connection state between runs (see
  [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md)).
- **Standard and DDD hosts** behave the same. Row anonymizer services (`RowAnonymizerInterface`) and
  `AnonymizePropertyEvent` listeners run inside the chunk's transaction, while its rows are locked. They must not open
  or leave a transaction: the run aborts when the nesting level changes. They should not do slow I/O (HTTP, mail,
  files) either, since the rows stay locked meanwhile.
- **Commit tracking.** The bundle installs no DBAL middleware and does not wrap the host's connections.

## Rules 1 to 9

| Rule | Status |
|------|--------|
| 1. One transaction per write unit | One runner transaction per chunk. Events with side effects: `AfterEntityAnonymizeEvent` is dispatched after the last commit |
| 2. Never reset or clear the host EM | DBAL only; no `flush()`, `clear()` or `resetManager()` in `src/` |
| 3. Nested transactions repair themselves | `ConnectionRepairer`, through the runner: rollback to the entry level, savepoint-aware |
| 4. Concurrency control | Rows locked by key in key order; atomic per-row `UPDATE` by primary key; no counters and no "only one" rules |
| 5. Exact business keys | The `UPDATE` matches the primary key read from the row itself |
| 6. Conflict answer | Console only (see [Conflict answer and codes](#conflict-answer-and-codes)) |
| 7. Safe retries | Re-running anonymizes again; nothing is kept between attempts; no external side effects inside the transaction (the history file is written after the run) |
| 8. No silent swallowing | Transient failures are rethrown, and the command exits with `1`. Only the history file write is best effort |
| 9. Bounded work under locks | Chunks of `--batch-size` rows; no I/O by the bundle while rows are locked; the lock timeouts come from the connection (see above) |

## Residual risks

Accepted, and documented for the operator:

1. **Application writes after a row's chunk committed.** A write that lands after the chunk commits (including one
   that waited for the lock) is not anonymized again. It can put real data back into the row. On entities with
   `AnonymizableTrait`, the `anonymized` flag stays `true` on that row. **Run the command while the application does
   not write** (maintenance mode, workers and consumers stopped), or run it again afterwards.
2. **Rows inserted behind the cursor.** With non-monotonic keys (UUIDs, natural keys), a row the application inserts
   during the run with a key lower than the current chunk is not seen. The mitigation is the same as above.
3. **Two runs at once** on one database are not prevented. Both write fake values, so nothing leaks, but they can
   deadlock: the losing run fails with the lock-conflict message.
4. **MariaDB and MySQL 5.7** cannot limit `FOR UPDATE` to the main table. With relationship patterns
   (`includePatterns: {'type.name': …}`), the joined rows of the chunk are locked too.
5. **Truncate** takes a table lock and waits for the application's open transactions on that table. On MySQL and
   MariaDB, foreign key checks are switched off for the bundle's session only while it truncates.
6. **SQLite** takes no row lock: the database allows one writer at a time.

The environment protection (dev and test only, production DSNs refused) limits all of this to non-production copies.

## Tests and CI

- `tests/Integration/Concurrency/ChunkConcurrencyTest.php` (group `database`) uses two real connections: the
  anonymizer and the "application". Both sessions use a 1 s lock timeout. The tests check that:
  - an application write to a row of the chunk being anonymized waits for the chunk's commit, while a row of the next
    chunk can be written (and is then anonymized);
  - on PostgreSQL and MySQL 8 the joined row is not locked, and on MariaDB it is;
  - a lock conflict rolls back only the current chunk and is rethrown as `TransientStorageFailure`, the connection is
    back at nesting level 0, and a re-run completes;
  - a composite-key run does not skip a row when the application deletes a row of a finished chunk.
- `AnonymizeServiceTest` checks the SQL of the lock clause on each platform, that a dry run takes no lock, and the
  composite keyset. `AnonymizeCommandTest` checks the lock-conflict message.
- The CI job **Load readiness** runs the race tests on MySQL 8.4, MariaDB 11.4 and PostgreSQL 16, on DBAL 4 and
  DBAL 3. The **Dependency floor** job runs the suites with the lowest `nowo-tech/transaction-*` versions allowed and
  checks that the demo lock installs them. Both are required checks on `main`.
