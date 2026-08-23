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
| DEV-008 | 30 exact visual baselines | The v1.7.1 contract and harness are versioned with exact `home/archive/single/deposer/favoris × FR/EN/AR × desktop/mobile` IDs; candidate baselines are not yet captured and independently approved. | M1 | Cold acceptance and technical release gate remain blocked; no visual PASS is claimed. | Capture on the exact candidate, verify SHA256SUMS, review before/after evidence and obtain independent approval. | OPEN |
| DEV-009 | Production-equivalent capacity envelope | A local 1,000-listing HTTP signal exists; Apache/Nginx, PHP-FPM four-worker, cgroup RSS and sustained production envelope remain unprovisioned. | M1 | Local PASS cannot authorize Ultra-Premium. | Execute on the declared reference environment with CPU, RSS, SQL, p50/p95/p99 and saturation evidence. | OPEN |

An open deviation is not a PASS. The scope matrix remains the source of truth for which requirements may contribute to a release label.
