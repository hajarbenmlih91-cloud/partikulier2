import json
from collections import defaultdict
from pathlib import Path

files = {"6.13.1": Path("rapport-sql-6.13.1-raw.jsonl"), "6.14.1": Path("rapport-sql-6.14.1-raw.jsonl")}
out = {"protocol": {"rounds": 3, "urls_per_round": 4}, "versions": {}}
for version, path in files.items():
    rows = [json.loads(line) for line in path.read_text().splitlines() if line.strip()]
    grouped = defaultdict(list)
    for row in rows:
        grouped[row["uri"]].append(row)
    summary = {}
    for uri, items in sorted(grouped.items()):
        def avg(key):
            return round(sum(float(item.get(key, 0) or 0) for item in items) / len(items), 3)
        summary[uri] = {
            "samples": len(items),
            "avg_queries": avg("queries"),
            "avg_sql_time_ms": avg("sql_time_ms"),
            "avg_duplicate_patterns": avg("duplicate_patterns"),
            "max_queries": max(int(item.get("queries", 0) or 0) for item in items),
            "max_duplicate_patterns": max(int(item.get("duplicate_patterns", 0) or 0) for item in items),
        }
    out["versions"][version] = summary

before = out["versions"]["6.13.1"]
after = out["versions"]["6.14.1"]
comparison = {}
for uri in sorted(set(before) | set(after)):
    b, a = before.get(uri, {}), after.get(uri, {})
    comparison[uri] = {
        "avg_queries_delta": round(a.get("avg_queries", 0) - b.get("avg_queries", 0), 3),
        "avg_sql_time_ms_delta": round(a.get("avg_sql_time_ms", 0) - b.get("avg_sql_time_ms", 0), 3),
        "avg_duplicate_patterns_delta": round(a.get("avg_duplicate_patterns", 0) - b.get("avg_duplicate_patterns", 0), 3),
        "before": b,
        "after": a,
    }
out["comparison"] = comparison
Path("rapport-sql-comparison-6.13.1-6.14.1.json").write_text(json.dumps(out, indent=2, ensure_ascii=False) + "\n")
print(json.dumps(out, indent=2, ensure_ascii=False))
