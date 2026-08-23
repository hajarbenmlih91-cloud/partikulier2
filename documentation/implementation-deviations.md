# Implementation deviations — CDC v1.7.1

| ID | CDC requirement | Current fact | Level | Impact | Resolution test | Status |
|---|---|---|---|---|---|---|
| DEV-001 | `partikulier-core` required as M0 | Core was absent at baseline; initial core now exists and is installed in a dedicated cold runtime. | M0 | Existing theme data remains Estatik-backed until migration/sync is fully certified. | Core cold install and REST contract. | OPEN — integration hardening pending |
| DEV-002 | `partikulier-pro` optional M2 | No pro module is delivered in this branch. | M2 | Payments and orders cannot contribute to Ultra-Premium. | Scope matrix and absence assertion. | DECLARED NOT_IMPLEMENTED |
| DEV-003 | 1000-listing load test | No load harness or production-equivalent environment has been executed. | M1 | Ultra-Premium label is blocked. | Load test report with fixed envelope. | OPEN |
| DEV-004 | Native FR/EN/AR attestations | Automated language checks exist; independent native attestations are not supplied. | M1 | UX/content sign-off is blocked. | Signed attestations with scope and commit. | OPEN |
| DEV-005 | Three-reviewer independent UX review | No independent UX review has been performed. | M1 | Ultra-Premium label is blocked. | Signed UX JSON and score thresholds. | OPEN |
| DEV-006 | Backup, restore and rollback | Runbooks and incident proof are incomplete for the new core tables. | M0/M1 | Operational release is blocked. | Restore/rollback exercise with RPO/RTO. | OPEN |
| DEV-007 | Tag and release approval | Human approval is intentionally not automated or fabricated. | M2/governance | Certification remains pending. | Signed release approval. | OPEN by design |

An open deviation is not a PASS. The scope matrix remains the source of truth for which requirements may contribute to a release label.
