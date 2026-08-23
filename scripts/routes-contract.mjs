import fs from 'node:fs';
import process from 'node:process';

const contractPath = new URL('../tests/routes-contract.json', import.meta.url);
const contract = JSON.parse(fs.readFileSync(contractPath, 'utf8'));
const base = process.env.PK_BASE || contract.base;
const commit = process.env.PK_COMMIT || 'uncommitted';
const scenarios = [{ name: 'root', ...contract.root }, ...contract.routes];
const results = [];
const MAX_REDIRECTS = 8;

function header(headers, name) {
  return headers.get(name) || '';
}

function pathOf(value) {
  try { return new URL(value, base).pathname; } catch { return ''; }
}

async function fetchTrace(url) {
  const hops = [];
  const visited = new Set();
  let current = url;
  let response;

  for (let attempt = 0; attempt <= MAX_REDIRECTS; attempt += 1) {
    if (visited.has(current)) {
      return { response, initialStatus: response?.status || 0, hops, finalPath: pathOf(current), loop: true };
    }
    visited.add(current);
    response = await fetch(current, { redirect: 'manual', headers: { 'Cache-Control': 'no-cache' } });
    const location = header(response.headers, 'location');
    const isRedirect = response.status >= 300 && response.status < 400 && location;
    if (!isRedirect) {
      return { response, initialStatus: hops.length ? (hops[0].status || response.status) : response.status, hops, finalPath: pathOf(current), loop: false };
    }
    const next = new URL(location, current).toString();
    hops.push({ status: response.status, location, path: pathOf(next) });
    if (new URL(next).origin !== new URL(base).origin) {
      return { response, initialStatus: hops[0].status, hops, finalPath: pathOf(next), loop: false, external: true };
    }
    current = next;
  }

  return { response, initialStatus: hops[0]?.status || response?.status || 0, hops, finalPath: pathOf(current), loop: true };
}

async function checkScenario(scenario) {
  const url = new URL(scenario.path, base).toString();
  const trace = await fetchTrace(url);
  const response = trace.response;
  const firstLocation = trace.hops[0]?.location || '';
  const redirectCount = trace.hops.length;
  const finalPath = trace.finalPath;
  const errors = [];
  if (trace.initialStatus !== scenario.expected_status) errors.push(`status=${trace.initialStatus}, attendu=${scenario.expected_status}`);
  if (redirectCount !== scenario.expected_redirects) errors.push(`redirections=${redirectCount}, attendu=${scenario.expected_redirects}`);
  if (trace.loop) errors.push(`chaîne de redirection non terminale (>${MAX_REDIRECTS} ou boucle)`);
  if (trace.external) errors.push('chaîne de redirection sortante non suivie');
  if (scenario.expected_location && pathOf(scenario.expected_location) !== finalPath) errors.push(`location=${finalPath}, attendu=${scenario.expected_location}`);
  if (scenario.expected_location_prefix && !finalPath.startsWith(scenario.expected_location_prefix)) errors.push(`location=${finalPath}, préfixe=${scenario.expected_location_prefix}`);
  if (scenario.expected_final_path && finalPath !== scenario.expected_final_path) errors.push(`final=${finalPath}, attendu=${scenario.expected_final_path}`);
  let lang = null;
  let dir = null;
  if (scenario.expected_status === 200 && response.status === 200) {
    const html = await response.text();
    const rawLang = html.match(/<html[^>]*\blang=["']([^"']+)["']/i)?.[1]?.toLowerCase() || null;
    lang = rawLang ? rawLang.split(/[-_]/)[0] : null;
    dir = html.match(/<html[^>]*\bdir=["']([^"']+)["']/i)?.[1]?.toLowerCase() || null;
    if (lang !== scenario.expected_lang) errors.push(`lang=${lang}, attendu=${scenario.expected_lang}`);
    if (dir !== scenario.expected_dir) errors.push(`dir=${dir}, attendu=${scenario.expected_dir}`);
  }
  return { name: scenario.name, path: scenario.path, status: trace.initialStatus, terminal_status: response.status, redirects: redirectCount, location: firstLocation || null, final_path: finalPath, lang, dir, chain: trace.hops, result: errors.length ? 'FAIL' : 'PASS', errors };
}

for (const scenario of scenarios) {
  try { results.push(await checkScenario(scenario)); }
  catch (error) { results.push({ name: scenario.name, path: scenario.path, result: 'FAIL', errors: [error.message] }); }
}

const passed = results.filter((r) => r.result === 'PASS').length;
const failed = results.length - passed;
console.log(JSON.stringify({ version: contract.version, commit, base, total: results.length, passed, failed, results }, null, 2));
console.error(`E2E_ROUTE_SUMMARY version=${contract.version} commit=${commit} total=${results.length} pass=${passed} fail=${failed}`);
process.exit(failed === 0 ? 0 : 1);
