#!/usr/bin/env python3
"""Audit stable design-system contracts without replacing independent visual review."""
from __future__ import annotations

import json
import os
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CSS = ROOT / "theme" / "assets" / "css" / "style.css"
TEMPLATES = ROOT / "theme" / "templates"
OUTPUT = ROOT / "documentation" / "design-system-audit-v1.7.1.json"
commit = os.environ.get("PK_COMMIT", "")
version = os.environ.get("PK_VERSION", "6.17.22")

TOKEN_RE = re.compile(r"(--pk-[a-z0-9-]+)\s*:\s*([^;]+);")
CLASS_ATTR_RE = re.compile(r"class=[\"']([^\"']+)[\"']")
CSS_CLASS_RE = re.compile(r"\.(pk-[a-z0-9_-]+)\b")
REQUIRED_TOKENS = {
    "--pk-primary", "--pk-primary-dark", "--pk-ink", "--pk-dark", "--pk-text",
    "--pk-muted", "--pk-card-bg", "--pk-line", "--pk-white", "--pk-sand",
    "--pk-sage", "--pk-radius", "--pk-font", "--pk-container", "--pk-transition",
}
errors: list[str] = []
css_text = CSS.read_text(encoding="utf-8") if CSS.is_file() else ""
if not css_text:
    errors.append("style.css missing or empty")

values = dict(TOKEN_RE.findall(css_text))
tokens_missing = sorted(REQUIRED_TOKENS - values.keys())
if tokens_missing:
    errors.append(f"missing required tokens: {tokens_missing}")
if len(re.findall(r"(?m)^\s*:root\s*\{", css_text)) != 1:
    errors.append("expected exactly one :root token block")
if ":focus" not in css_text or "@media" not in css_text:
    errors.append("focus and responsive contracts must be present")

used: set[str] = set()
for path in sorted(TEMPLATES.rglob("*.php")):
    for class_attribute in CLASS_ATTR_RE.findall(path.read_text(encoding="utf-8", errors="replace")):
        used.update(token for token in class_attribute.split() if token.startswith("pk-") and re.fullmatch(r"pk-[a-z0-9_-]+", token))
css_classes = set(CSS_CLASS_RE.findall(css_text))
# State, generated variant and behavior-only classes are intentionally excluded.
dynamic_prefixes = ("pk-wish-", "pk-open", "pk-focus", "pk-current", "pk-status-", "pk-role-", "pk-badge-", "pk-grid-")
dynamic_classes = {"pk-flag", "pk-single-share"}
missing_components = sorted(c for c in used if c not in css_classes and c not in dynamic_classes and not c.startswith(dynamic_prefixes))
if missing_components:
    errors.append(f"template classes without CSS selector: {missing_components}")

checks = [
    {"id": "DS-TOKENS-001", "status": "PASS" if not tokens_missing else "FAIL", "required": sorted(REQUIRED_TOKENS), "found": len(values)},
    {"id": "DS-ROOT-001", "status": "PASS" if css_text.count(":root {") == 1 else "FAIL", "root_blocks": css_text.count(":root {")},
    {"id": "DS-COMPONENTS-001", "status": "PASS" if not missing_components else "FAIL", "template_classes": len(used), "missing": missing_components},
    {"id": "DS-RESPONSIVE-001", "status": "PASS" if "@media" in css_text else "FAIL", "media_queries": len(re.findall(r"@media", css_text))},
    {"id": "DS-FOCUS-001", "status": "PASS" if ":focus" in css_text else "FAIL", "focus_rules": len(re.findall(r":focus", css_text))},
]
payload = {
    "test_id": "DESIGN-SYSTEM-AUDIT-001",
    "candidate_version": version,
    "source_commit": commit,
    "source_ref": os.environ.get("GITHUB_REF", "local"),
    "run_id": os.environ.get("GITHUB_RUN_ID", "local"),
    "generated_at_utc": datetime.now(timezone.utc).isoformat().replace("+00:00", "Z"),
    "status": "PASS" if not errors and re.fullmatch(r"[0-9a-f]{40}", commit or "") else "FAIL",
    "checks": checks,
    "errors": errors if errors else [],
    "limitations": ["Automated token/component consistency does not replace independent visual design review."],
}
OUTPUT.parent.mkdir(parents=True, exist_ok=True)
OUTPUT.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
print(json.dumps(payload, ensure_ascii=False, indent=2))
sys.exit(0 if payload["status"] == "PASS" else 1)
