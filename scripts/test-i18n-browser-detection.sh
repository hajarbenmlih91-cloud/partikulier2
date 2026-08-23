#!/usr/bin/env bash
set -u

BASE_URL="${PK_URL:-http://localhost:8090}"
VERSION="${PK_VERSION:-6.17.12}"
REPORT="${PK_REPORT:-/tmp/partikulier-${VERSION}-browser-detection.json}"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

failures=()
checks=()
add_check() {
  local name="$1" passed="$2" detail="$3"
  detail="${detail//$'\n'/ }"; detail="${detail//$'\r'/ }"; detail="${detail//\\/\\\\}"; detail="${detail//\"/\\\"}"
  checks+=("$(printf '{\"name\":\"%s\",\"passed\":%s,\"detail\":\"%s\"}' "$name" "$passed" "$detail")")
  [ "$passed" = true ] || failures+=("$name: $detail")
}
run_case() {
  local name="$1" ua="$2" accept="$3" cookie="$4" expected_status="$5" expected_location="$6"
  local headers="$TMP_DIR/$name.headers" body="$TMP_DIR/$name.html"
  curl -sS -A "$ua" -H "Accept-Language: $accept" ${cookie:+-H "Cookie: $cookie"} -D "$headers" -o "$body" "$BASE_URL/" >/dev/null
  local status location cache vary
  status="$(awk 'NR==1{print $2}' "$headers")"
  location="$(awk 'BEGIN{IGNORECASE=1}/^Location:/{print $2}' "$headers" | tr -d '\r')"
  cache="$(awk 'BEGIN{IGNORECASE=1}/^Cache-Control:/{sub(/^Cache-Control: /,""); print}' "$headers" | tr -d '\r')"
  vary="$(awk 'BEGIN{IGNORECASE=1}/^Vary:/{sub(/^Vary: /,""); print}' "$headers" | tr -d '\r')"
  local ok=true
  [ "$status" = "$expected_status" ] || ok=false
  if [ -n "$expected_location" ]; then [ "$location" = "$BASE_URL/$expected_location/" ] || ok=false; else [ -z "$location" ] || ok=false; fi
  [[ "$cache" == *private* && "$cache" == *no-store* ]] || ok=false
  [[ "$vary" == *Accept-Language* && "$vary" == *Cookie* ]] || ok=false
  add_check "$name" "$ok" "status=$status location=$location cache=$cache vary=$vary"
}

run_case human_ar "Mozilla/5.0" "ar-MA,ar;q=0.9" "" 302 ar
run_case human_en "Mozilla/5.0" "en-US,en;q=0.9" "" 302 en
run_case human_fr "Mozilla/5.0" "fr-FR,fr;q=0.9" "" 200 ""
run_case cookie_priority "Mozilla/5.0" "ar" "pll_language=en" 302 en
for ua in "Googlebot/2.1" "bingbot/2.0" "YandexBot/3.0" "DuckDuckBot/1.0" "Applebot/0.1" "facebookexternalhit/1.1"; do
  slug="$(printf '%s' "$ua" | tr '[:upper:]' '[:lower:]' | tr -cd 'a-z0-9')"
  run_case "robot_$slug" "$ua" "ar" "" 200 ""
done

printf '{"version":"%s","passed":%s,"failures":[' "$VERSION" "$([ "${#failures[@]}" -eq 0 ] && echo true || echo false)" > "$REPORT"
if [ "${#failures[@]}" -gt 0 ]; then
  printf '%s' "$(printf '"%s",' "${failures[@]}" | sed 's/,$//')" >> "$REPORT"
fi
printf '],"checks":[' >> "$REPORT"
printf '%s,' "${checks[@]}" | sed 's/,$//' >> "$REPORT"
printf ']}\n' >> "$REPORT"
cat "$REPORT"
[ "${#failures[@]}" -eq 0 ]
