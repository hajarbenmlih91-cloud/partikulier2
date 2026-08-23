# Runbook opérations — Partikulier v1.7.1

## Objectives

| Objective | Target | Measurement |
|---|---:|---|
| RPO database | ≤ 24 h | Latest verified backup timestamp versus incident time |
| RTO database | ≤ 15 min | Timer from restore invocation to health and data checks passing |
| Backup retention | 7 daily + 4 weekly | Backup inventory and deletion audit |
| Rollback decision | ≤ 10 min | Approved incident record to atomic release switch |
| Health detection | ≤ 5 min | Health check interval and alert timestamp |

These values are the v1.7.1 contract targets and must be approved before a production release. A local test demonstrates the procedure, not production availability.

## Backup

Run `scripts/backup.sh <output-dir> <wp-dir> <db-name> <db-user> <db-pass>`. The command creates `database.sql`, `wp-content.tar.gz`, `SHA256SUMS` and `manifest.json`. The SQL is taken with a transaction and the files are archived with normalized owner, group and mtime metadata. Secrets are supplied as arguments only to the process and are never written to the manifest or logs.

## Restore

Run `scripts/restore.sh <backup-dir> <db-name> <db-user> <db-pass> <wp-dir>`. The command refuses to continue without the checksum manifest, verifies both files, imports into the target database and restores the content. Verify the core schema version, the number of properties, `/wp-json/partikulier/v1/health` and one public listing after restore.

## Rollback

A rollback requires a release directory, a pre-approved file containing exactly `APPROVED`, and an atomic symlink switch. The previous release is never deleted by the rollback command. The operator records the target release, incident ID, decision time, health result and subsequent restore/forward-fix decision.

## Observability

Application errors must be written to the WordPress/PHP error log with a correlation ID where a request crosses the core boundary. Audit metadata is non-secret. Health status is public but contains no credentials, tokens, database host, filesystem path or stack trace. Server logs and test logs are archived as sidecars and excluded from the deterministic ZIP hash when they contain timestamps or runner-specific data.
