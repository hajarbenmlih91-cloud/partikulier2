#!/usr/bin/env python3
"""Verify a downloaded Partikulier v1.7.1 candidate artifact without trusting its prose."""
from __future__ import annotations

import argparse
import hashlib
import json
import re
import sys
import tarfile
import tempfile
import zipfile
from pathlib import Path
from typing import Any

VERSION = "6.17.17"
SHA40 = re.compile(r"^[0-9a-f]{40}$")
SHA64 = re.compile(r"^[0-9a-f]{64}$")


class VerificationError(Exception):
    pass


def fail(message: str) -> None:
    raise VerificationError(message)


def find_one(root: Path, name: str) -> Path:
    matches = sorted(p for p in root.rglob(name) if p.is_file())
    if len(matches) != 1:
        fail(f"expected exactly one {name}, found {len(matches)}: {matches}")
    return matches[0]


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def verify_checksum_sidecar(artifact: Path, sidecar: Path) -> str:
    lines = [line.strip() for line in sidecar.read_text(encoding="utf-8").splitlines() if line.strip()]
    if len(lines) != 1:
        fail(f"checksum sidecar must contain one line: {sidecar}")
    parts = lines[0].split()
    if len(parts) != 2 or not SHA64.fullmatch(parts[0]):
        fail(f"invalid checksum line in {sidecar}: {lines[0]}")
    expected, referenced = parts
    if Path(referenced).name != artifact.name:
        fail(f"checksum sidecar references {referenced}, expected {artifact.name}")
    actual = sha256(artifact)
    if actual != expected:
        fail(f"checksum mismatch for {artifact.name}: {actual} != {expected}")
    return actual


def load_json(path: Path) -> dict[str, Any]:
    try:
        value = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        fail(f"invalid JSON {path}: {exc}")
    if not isinstance(value, dict):
        fail(f"JSON root is not an object: {path}")
    return value


def assert_equal(actual: Any, expected: Any, label: str) -> None:
    if actual != expected:
        fail(f"{label}: {actual!r} != {expected!r}")


def assert_commit_run(data: dict[str, Any], commit: str, run_id: str, label: str) -> None:
    candidate_commit = data.get("commit") or data.get("source_commit") or (data.get("acceptance") or {}).get("commit")
    if candidate_commit != commit:
        fail(f"{label}.commit: {candidate_commit!r} != {commit!r}")
    if "run_id" in data and str(data["run_id"]) != str(run_id):
        fail(f"{label}.run_id: {data['run_id']!r} != {run_id!r}")
    if data.get("candidate_version") not in (None, VERSION):
        fail(f"{label}.candidate_version: {data.get('candidate_version')!r} != {VERSION!r}")


def assert_pass_counts(data: dict[str, Any], total: int, label: str) -> None:
    assert_equal(data.get("total"), total, f"{label}.total")
    assert_equal(data.get("passed"), total, f"{label}.passed")
    assert_equal(data.get("failed"), 0, f"{label}.failed")


def safe_extract(tar_path: Path, destination: Path) -> None:
    with tarfile.open(tar_path, "r:gz") as archive:
        members = archive.getmembers()
        for member in members:
            target = (destination / member.name).resolve()
            if target != destination.resolve() and destination.resolve() not in target.parents:
                fail(f"unsafe path in evidence archive: {member.name}")
        archive.extractall(destination)


def evidence_json(evidence_root: Path, name: str) -> tuple[Path, dict[str, Any]]:
    path = evidence_root / "documentation" / name
    if not path.is_file():
        fail(f"missing evidence file: documentation/{name}")
    return path, load_json(path)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("artifact_dir", type=Path, help="directory containing downloaded GitHub artifact files")
    parser.add_argument("--commit", required=True, help="expected 40-character source commit")
    parser.add_argument("--run-id", required=True, help="expected GitHub run id")
    parser.add_argument("--output", type=Path, default=Path("candidate-artifact-verification-v1.7.1.json"))
    args = parser.parse_args()

    checks: list[dict[str, Any]] = []
    try:
        if not SHA40.fullmatch(args.commit):
            fail("--commit must be an exact 40-character lowercase SHA")
        root = args.artifact_dir.resolve()
        if not root.is_dir():
            fail(f"artifact directory does not exist: {root}")

        package = find_one(root, f"partikulier-{VERSION}-deterministic.zip")
        package_sha_file = find_one(root, f"partikulier-{VERSION}-deterministic.zip.sha256")
        evidence = find_one(root, f"partikulier-{VERSION}-evidence.tar.gz")
        evidence_sha_file = find_one(root, f"partikulier-{VERSION}-evidence.tar.gz.sha256")
        reproducibility_file = find_one(root, "reproducibility-v1.7.1.json")
        package_sha = verify_checksum_sidecar(package, package_sha_file)
        evidence_sha = verify_checksum_sidecar(evidence, evidence_sha_file)
        checks.extend([
            {"id": "PACKAGE-SHA-001", "status": "PASS", "sha256": package_sha},
            {"id": "SIDECAR-SHA-001", "status": "PASS", "sha256": evidence_sha},
        ])

        with zipfile.ZipFile(package) as archive:
            if archive.testzip() is not None:
                fail("deterministic ZIP integrity test failed")
            names = set(archive.namelist())
            required = {
                "documentation/candidate-6.17.17.json",
                "documentation/capacity-envelope.json",
                "documentation/scope-matrix.csv",
                "documentation/visual-scenarios-v1.7.1.json",
            }
            missing = sorted(required - names)
            if missing:
                fail(f"deterministic ZIP missing required stable inputs: {missing}")
            candidate = json.loads(archive.read("documentation/candidate-6.17.17.json"))
            assert_equal(candidate.get("source_commit"), args.commit, "ZIP candidate source_commit")
            assert_equal(candidate.get("volatile_evidence"), "sidecar", "ZIP candidate volatile_evidence")
            checks.append({"id": "PACKAGE-CONTENT-001", "status": "PASS", "required_files": sorted(required)})

        with tempfile.TemporaryDirectory(prefix="cdc-v171-verify-") as temporary:
            evidence_root = Path(temporary)
            safe_extract(evidence, evidence_root)

            evidence_specs = {
                "accessibility-v6.17.17.json": (6, "accessibility"),
                "core-contract-v6.17.17.json": (8, "core"),
                "core-services-contract-v6.17.17.json": (7, "services"),
                "theme-contract-v6.17.17.json": (6, "theme"),
                "routes-contract-v6.17.17.json": (16, "routes"),
                "visual-contract-v6.17.17.json": (30, "visual"),
            }
            loaded: dict[str, dict[str, Any]] = {}
            for filename, (total, label) in evidence_specs.items():
                _, data = evidence_json(evidence_root, filename)
                loaded[label] = data
                assert_commit_run(data, args.commit, args.run_id, label)
                assert_pass_counts(data, total, label)
            checks.append({"id": "CONTRACT-COUNTS-001", "status": "PASS", "counts": {k: v[0] for k, v in evidence_specs.items()}})

            hmac_path, hmac = evidence_json(evidence_root, "hmac-http-v6.17.17.json")
            assert_commit_run(hmac, args.commit, args.run_id, "hmac")
            assert_equal(hmac.get("rounds"), 5, "hmac.rounds")
            assert_equal(len(hmac.get("rounds_detail", [])), 5, "hmac.rounds_detail")
            assert_equal(hmac.get("secret_included"), False, "hmac.secret_included")
            negative = hmac.get("negative") or {}
            for case in ("invalid_secret", "invalid_signature", "expired_timestamp", "missing_shared_header"):
                assert_equal(negative.get(case), "401", f"hmac.negative.{case}")
            assert_equal(len(negative.get("details", [])), 4, "hmac.negative.details")
            checks.append({"id": "HMAC-HTTP-001", "status": "PASS", "rounds": 5, "negative_401": 4})

            _, semgrep = evidence_json(evidence_root, "semgrep-v6.17.17.json")
            assert_commit_run(semgrep, args.commit, args.run_id, "semgrep")
            acceptance = semgrep.get("acceptance") or {}
            for key in ("targets_scanned", "raw_targets_scanned"):
                assert_equal(acceptance.get(key), 66, f"semgrep.acceptance.{key}")
            assert_equal(acceptance.get("blocking_findings"), 0, "semgrep.acceptance.blocking_findings")
            assert_equal(acceptance.get("errors_count"), 0, "semgrep.acceptance.errors_count")
            checks.append({"id": "SEMGREP-001", "status": "PASS", "raw_targets": 66, "blocking_findings": 0})

            _, sql = evidence_json(evidence_root, "sql-v6.17.17-summary.json")
            assert_commit_run(sql, args.commit, args.run_id, "sql")
            runs = sql.get("runs") or []
            assert_equal(len(runs), 3, "sql.runs.length")
            assert_equal(sql.get("threshold"), 56, "sql.threshold")
            if not all(isinstance(value, int) and value <= 56 for value in runs):
                fail(f"sql.runs exceed threshold: {runs}")
            assert_equal(sql.get("all_below_threshold"), True, "sql.all_below_threshold")
            checks.append({"id": "SQL-001", "status": "PASS", "runs": runs, "threshold": 56})

            _, fixture = evidence_json(evidence_root, "load-fixture-v6.17.17.json")
            assert_commit_run(fixture, args.commit, args.run_id, "load_fixture")
            assert_equal(fixture.get("status"), "PASS", "load_fixture.status")
            if not isinstance(fixture.get("after"), int) or fixture["after"] < 1000:
                fail(f"load_fixture.after below 1000: {fixture.get('after')!r}")
            _, load = evidence_json(evidence_root, "load-test-v6.17.17.json")
            assert_commit_run(load, args.commit, args.run_id, "load_test")
            assert_equal(load.get("status"), "PASS", "load_test.status")
            metrics = load.get("metrics") or {}
            assert_equal(metrics.get("errors"), 0, "load_test.metrics.errors")
            if metrics.get("p95_seconds", 999) > 1.5 or metrics.get("p99_seconds", 999) > 3.0:
                fail(f"load metrics exceed threshold: {metrics}")
            checks.append({"id": "LOAD-001", "status": "PASS", "fixture_after": fixture["after"], "metrics": metrics})

            _, qualification = evidence_json(evidence_root, "qualification-v1.7.1.json")
            assert_equal(qualification.get("source_commit"), args.commit, "qualification.source_commit")
            assert_equal(str(qualification.get("run_id")), str(args.run_id), "qualification.run_id")
            assert_equal(qualification.get("package_sha256"), package_sha, "qualification.package_sha256")
            labels = qualification.get("labels") or {}
            technical_status = labels.get("TECHNICAL_CANDIDATE_STATUS", labels.get("TECHNICAL_STATUS"))
            assert_equal(technical_status, "PASS", "qualification.TECHNICAL_CANDIDATE_STATUS")
            if "TECHNICAL_STATUS" in labels:
                assert_equal(labels.get("TECHNICAL_STATUS"), "PASS", "qualification.TECHNICAL_STATUS")
            assert_equal(labels.get("RELEASE_STATUS"), "CANDIDATE", "qualification.RELEASE_STATUS")
            assert_equal(labels.get("TECHNICAL_RELEASE_CANDIDATE"), "PASS", "qualification.TECHNICAL_RELEASE_CANDIDATE")
            assert_equal(qualification.get("human_validation"), "PENDING_NOT_SIMULATED", "qualification.human_validation")
            checks.append({"id": "QUALIFICATION-LINK-001", "status": "PASS", "package_sha256": package_sha})

        reproducibility = load_json(reproducibility_file)
        assert_equal(reproducibility.get("source_commit"), args.commit, "reproducibility.source_commit")
        assert_equal(reproducibility.get("comparison"), "cmp byte-identical", "reproducibility.comparison")
        assert_equal(reproducibility.get("source_to_release_reproducible"), "PENDING_UNTIL_RELEASE_ASSET_COMPARE", "reproducibility.release_compare")
        checks.append({"id": "SOURCE-CMP-001", "status": "PASS", "comparison": "cmp byte-identical"})

        result = {
            "test_id": "CANDIDATE-ARTIFACT-VERIFY-001",
            "candidate_version": VERSION,
            "source_commit": args.commit,
            "run_id": str(args.run_id),
            "status": "PASS",
            "package": str(package),
            "package_sha256": package_sha,
            "evidence_sha256": evidence_sha,
            "checks": checks,
            "release_asset_comparison": "PENDING_UNTIL_RELEASE_ASSET_COMPARE",
            "human_validation": "PENDING_NOT_SIMULATED",
        }
    except VerificationError as exc:
        result = {
            "test_id": "CANDIDATE-ARTIFACT-VERIFY-001",
            "candidate_version": VERSION,
            "source_commit": args.commit,
            "run_id": str(args.run_id),
            "status": "FAIL",
            "error": str(exc),
            "checks": checks,
        }
        args.output.parent.mkdir(parents=True, exist_ok=True)
        args.output.write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(json.dumps(result, ensure_ascii=False, indent=2))
        return 1

    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(result, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    sys.exit(main())

