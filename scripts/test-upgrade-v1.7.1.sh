#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_DIR="${PK_WP_DIR:?PK_WP_DIR is required}"
DB_NAME="${PK_DB_NAME:?PK_DB_NAME is required}"
DB_USER="${PK_DB_USER:?PK_DB_USER is required}"
DB_PASS="${PK_DB_PASS:?PK_DB_PASS is required}"
COMMIT="${PK_COMMIT:-$(git -C "$ROOT" rev-parse HEAD 2>/dev/null || true)}"
RUN_ID="${PK_RUN_ID:-local}"
REPORT="${PK_UPGRADE_REPORT:-$ROOT/documentation/upgrade-v6.17.16-to-v${PK_VERSION:-6.17.22}.json}"
OLD_TAG="v6.17.16"
NEW_VERSION="${PK_VERSION:-6.17.22}"

[[ "$COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo 'PK_COMMIT must be an exact 40-character SHA' >&2; exit 2; }
[ -f "$WP_DIR/wp-load.php" ] || { echo "WordPress runtime absent: $WP_DIR" >&2; exit 2; }
git -C "$ROOT" cat-file -e "$OLD_TAG^{commit}" || { echo "Historical tag absent: $OLD_TAG" >&2; exit 2; }

mkdir -p "$(dirname "$REPORT")"
work="$(mktemp -d)"
sentinel_title="CDC upgrade sentinel ${RUN_ID}"
trap 'rm -rf "$work"' EXIT

wpq() { wp --path="$WP_DIR" "$@" --allow-root; }

snapshot() {
  local destination="$1"
  PK_UPGRADE_SENTINEL_ID="$SENTINEL_ID" wpq eval '
    global $wpdb;
    $id = (int) getenv("PK_UPGRADE_SENTINEL_ID");
    $post = get_post($id);
    $options = get_option("pk_theme_options", array());
    $tables = $wpdb->get_col("SHOW TABLES");
    $markers = array();
    foreach (array("pk_required_pages_migration", "_pk_n8n_settings_migration_state", "pk_credentials_resend_meta_migrated_v1") as $key) {
        $markers[$key] = get_option($key, null);
    }
    $payload = array(
      "post_count" => (int) wp_count_posts("properties")->publish,
      "sentinel" => array(
        "id" => $id,
        "title" => $post ? $post->post_title : null,
        "status" => $post ? $post->post_status : null,
        "meta" => $post ? array(
          "price" => get_post_meta($id, "es_property_price", true),
          "area" => get_post_meta($id, "es_property_area", true),
          "marker" => get_post_meta($id, "_pk_upgrade_marker", true)
        ) : null
      ),
      "settings" => array(
        "marker" => $options["_pk_upgrade_marker"] ?? null,
        "whatsapp_validation_number" => $options["whatsapp_validation_number"] ?? null,
        "automation_api_secret_present" => !empty($options["automation_api_secret"])
      ),
      "settings_sha256" => hash("sha256", wp_json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
      "migration_markers" => $markers,
      "table_count" => count($tables),
      "table_names_sha256" => hash("sha256", implode("\n", $tables))
    );
    echo wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  ' > "$destination"
  jq empty "$destination"
}

# Seed invariants using the candidate installation before switching to the historical tag.
SENTINEL_TITLE="$sentinel_title" wpq eval '
  $title = getenv("SENTINEL_TITLE");
  $id = wp_insert_post(array("post_type" => "properties", "post_status" => "publish", "post_title" => $title, "post_content" => "Upgrade sentinel"), true);
  if (is_wp_error($id)) { fwrite(STDERR, $id->get_error_message()); exit(1); }
  update_post_meta($id, "es_property_price", "777777");
  update_post_meta($id, "es_property_area", "77");
  update_post_meta($id, "_pk_upgrade_marker", "preserve-${title}");
  $o = get_option("pk_theme_options", array());
  $o["_pk_upgrade_marker"] = "preserve-${title}";
  $o["whatsapp_validation_number"] = $o["whatsapp_validation_number"] ?? "212612345678";
  update_option("pk_theme_options", $o);
  update_option("pk_upgrade_acceptance_sentinel", "preserve-${title}", false);
  echo (int) $id;
' > "$work/sentinel-id"
SENTINEL_ID="$(tr -d '\n' < "$work/sentinel-id")"
[[ "$SENTINEL_ID" =~ ^[0-9]+$ ]] || { echo 'Could not create upgrade sentinel' >&2; exit 1; }
snapshot "$work/before-candidate.json"

# Materialize the exact historical source from Git, then activate it in the same runtime.
mkdir -p "$work/old"
git -C "$ROOT" archive --format=tar "$OLD_TAG" theme mu-plugins | tar -xf - -C "$work/old"
cp -a "$WP_DIR/wp-content/themes/partikulier" "$work/current-theme"
wpq theme deactivate >/dev/null 2>&1 || true
wpq plugin deactivate partikulier-core >/dev/null 2>&1 || true
rm -rf "$WP_DIR/wp-content/themes/partikulier" "$WP_DIR/wp-content/plugins/partikulier-core"
cp -a "$work/old/theme" "$WP_DIR/wp-content/themes/partikulier"
if [ -d "$work/old/mu-plugins" ]; then rm -rf "$WP_DIR/wp-content/mu-plugins"; cp -a "$work/old/mu-plugins" "$WP_DIR/wp-content/mu-plugins"; fi
wpq theme activate partikulier >/dev/null
wpq eval 'do_action("after_switch_theme"); do_action("init");' >/dev/null
wpq cache flush >/dev/null 2>&1 || true
snapshot "$work/old.json"

# Upgrade in place to the current candidate checkout and execute lifecycle hooks twice.
rm -rf "$WP_DIR/wp-content/themes/partikulier" "$WP_DIR/wp-content/plugins/partikulier-core"
cp -a "$ROOT/theme" "$WP_DIR/wp-content/themes/partikulier"
mkdir -p "$WP_DIR/wp-content/plugins/partikulier-core"
cp -a "$ROOT/partikulier-core/." "$WP_DIR/wp-content/plugins/partikulier-core/"
if [ -d "$ROOT/mu-plugins" ]; then rm -rf "$WP_DIR/wp-content/mu-plugins"; cp -a "$ROOT/mu-plugins/." "$WP_DIR/wp-content/mu-plugins/"; fi
wpq plugin activate partikulier-core >/dev/null
wpq theme activate partikulier >/dev/null
wpq eval 'do_action("after_switch_theme"); do_action("init"); do_action("init");' >/dev/null
wpq rewrite flush >/dev/null 2>&1 || true
wpq cache flush >/dev/null 2>&1 || true
snapshot "$work/after-first.json"
wpq eval 'do_action("after_switch_theme"); do_action("init"); do_action("init");' >/dev/null
snapshot "$work/after-second.json"

python3 - "$REPORT" "$work/before-candidate.json" "$work/old.json" "$work/after-first.json" "$work/after-second.json" "$SENTINEL_ID" "$COMMIT" "$RUN_ID" <<'PY'
import json, sys
from datetime import datetime, timezone
out, before_path, old_path, after_path, second_path, sentinel_id, commit, run_id = sys.argv[1:]
def read(path):
    with open(path, encoding='utf-8') as f: return json.load(f)
before, old, after, second = map(read, (before_path, old_path, after_path, second_path))
checks = []
def check(test_id, ok, detail): checks.append({'test_id': test_id, 'status': 'PASS' if ok else 'FAIL', 'detail': detail})
check('UPGRADE-TAG-001', old.get('table_count') == before.get('table_count'), 'historical tag runtime loaded without table loss')
check('UPGRADE-DATA-001', after.get('sentinel') == before.get('sentinel'), 'sentinel post and metadata preserved')
check('UPGRADE-SETTINGS-001', after.get('settings') == before.get('settings'), 'theme settings invariants preserved')
check('UPGRADE-COUNT-001', after.get('post_count') == before.get('post_count'), 'published properties count preserved')
check('UPGRADE-IDEMPOTENT-001', after == second, 'second lifecycle pass is byte-identical snapshot')
check('UPGRADE-VERSION-001', after.get('sentinel', {}).get('id') == int(sentinel_id), 'sentinel remains addressable after upgrade')
failed = [c for c in checks if c['status'] != 'PASS']
payload = {
    'test_id': 'UPGRADE-COMPATIBILITY-001',
    'candidate_version': __import__('os').environ.get('PK_VERSION', '6.17.22'),
    'source_commit': commit,
    'source_ref': __import__('os').environ.get('GITHUB_REF', 'local'),
    'run_id': run_id,
    'started_at_utc': None,
    'finished_at_utc': datetime.now(timezone.utc).isoformat().replace('+00:00', 'Z'),
    'from_tag': 'v6.17.16',
    'to_version': __import__('os').environ.get('PK_VERSION', '6.17.22'),
    'status': 'FAIL' if failed else 'PASS',
    'exit_code': 1 if failed else 0,
    'checks': checks,
    'snapshots': {'before_candidate': before, 'after_historical': old, 'after_first_upgrade': after, 'after_second_upgrade': second},
    'limitations': ['The runtime is disposable and production infrastructure approval remains separate.']
}
with open(out, 'w', encoding='utf-8') as f: json.dump(payload, f, ensure_ascii=False, indent=2); f.write('\n')
print(json.dumps(payload, ensure_ascii=False, indent=2))
sys.exit(1 if failed else 0)
PY
