import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { chromium, request } from 'playwright';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';

const ROOT = process.cwd();
const CONTRACT_PATH = path.join(ROOT, 'documentation', 'visual-scenarios-v1.7.1.json');
const contract = JSON.parse(fs.readFileSync(CONTRACT_PATH, 'utf8'));
const BASE_URL = process.env.PK_BASE || 'http://localhost:8090';
const VERSION = process.env.PK_VERSION || '6.17.17';
const COMMIT = process.env.PK_COMMIT || '';
const RUN_ID = process.env.PK_RUN_ID || 'local';
const generate = process.argv.includes('--generate');
const threshold = Number(process.env.PK_VISUAL_THRESHOLD || '0.5');
const baselineDir = path.join(ROOT, contract.baseline_policy.directory);
const currentDir = path.join(ROOT, 'tests', `current-${VERSION}`);
const diffDir = path.join(ROOT, 'tests', `diff-${VERSION}`);
const viewports = contract.viewports;
const scenarios = contract.scenarios;

function sha256(file) { return crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex'); }
function fail(message) { throw new Error(message); }
function ensureDir(dir) { fs.mkdirSync(dir, { recursive: true }); }
function validateShape() {
  if (!/^[0-9a-f]{40}$/.test(COMMIT)) fail('PK_COMMIT doit être un SHA Git de 40 caractères');
  if (scenarios.length !== 30) fail(`Le contrat visuel contient ${scenarios.length} scénarios au lieu de 30`);
  const ids = new Set(scenarios.map((s) => s.id));
  if (ids.size !== 30) fail('Identifiants visuels dupliqués');
  if (contract.baseline_policy.regenerate_in_ci !== false) fail('La CI ne doit jamais régénérer les baselines');
}
function readManifest() {
  const manifestPath = path.join(ROOT, contract.baseline_policy.manifest);
  if (!fs.existsSync(manifestPath)) fail(`Manifest SHA256SUMS manquant: ${manifestPath}`);
  const rows = fs.readFileSync(manifestPath, 'utf8').trim().split(/\r?\n/).filter(Boolean);
  const entries = new Map(rows.map((line) => { const [hash, name] = line.trim().split(/\s+\*?/, 2); return [name, hash]; }));
  if (rows.length !== 30) fail(`Manifest visuel contient ${rows.length} lignes au lieu de 30`);
  for (const scenario of scenarios) {
    const relative = path.posix.relative(ROOT, path.join(ROOT, scenario.baseline));
    const file = path.join(ROOT, scenario.baseline);
    if (!fs.existsSync(file) || fs.statSync(file).size === 0) fail(`Baseline absente ou vide: ${relative}`);
    if (entries.get(relative) !== sha256(file)) fail(`Hash baseline invalide: ${relative}`);
  }
}
function writeManifest() {
  const lines = scenarios.map((scenario) => {
    const file = path.join(ROOT, scenario.baseline);
    return `${sha256(file)}  ${scenario.baseline}`;
  });
  ensureDir(baselineDir);
  fs.writeFileSync(path.join(baselineDir, 'SHA256SUMS'), `${lines.join('\n')}\n`);
}

validateShape();
if (!generate) readManifest();
if (generate && contract.baseline_policy.regenerate_in_ci === false && process.env.PK_ALLOW_LOCAL_REBASELINE !== '1') fail('Rebaseline interdit sans PK_ALLOW_LOCAL_REBASELINE=1; la CI ne régénère jamais');
ensureDir(currentDir);
ensureDir(diffDir);

const browser = await chromium.launch({ headless: true });
const api = await request.newContext({ baseURL: BASE_URL, maxRedirects: 0, extraHTTPHeaders: { 'Cache-Control': 'no-cache' } });
const results = [];
try {
  for (const scenario of scenarios) {
    const viewport = viewports[scenario.viewport];
    const direct = await api.get(scenario.url);
    const directStatus = direct.status();
    const errors = [];
    if (directStatus !== scenario.expected_http) errors.push(`http=${directStatus}, attendu=${scenario.expected_http}`);
    const page = await browser.newPage({ viewport: { width: viewport.width, height: viewport.height } });
    const output = path.join(generate ? baselineDir : currentDir, scenario.baseline.replace(`${contract.baseline_policy.directory}/`, ''));
    try {
      const navigated = await page.goto(new URL(scenario.url, BASE_URL).toString(), { waitUntil: 'domcontentloaded', timeout: 30000 });
      await page.waitForTimeout(500);
      const htmlLang = ((await page.locator('html').getAttribute('lang')) || '').toLowerCase().split(/[-_]/)[0];
      const htmlDir = ((await page.locator('html').getAttribute('dir')) || '').toLowerCase();
      if (!navigated) errors.push('navigation sans réponse');
      if (scenario.expected_http === 200 && htmlLang !== scenario.locale) errors.push(`lang=${htmlLang}, attendu=${scenario.locale}`);
      if (scenario.expected_dir !== htmlDir) errors.push(`dir=${htmlDir}, attendu=${scenario.expected_dir}`);
      await page.screenshot({ path: output, fullPage: true });
      if (generate) {
        results.push({ id: scenario.id, status: errors.length ? 'FAIL' : 'GENERATED', errors });
      } else {
        const baseline = PNG.sync.read(fs.readFileSync(path.join(ROOT, scenario.baseline)));
        const current = PNG.sync.read(fs.readFileSync(output));
        if (baseline.width !== current.width || baseline.height !== current.height) errors.push(`dimensions=${current.width}x${current.height}`);
        let diffPercent = 100;
        if (!errors.some((e) => e.startsWith('dimensions='))) {
          const diff = new PNG({ width: baseline.width, height: baseline.height });
          const pixels = pixelmatch(baseline.data, current.data, diff.data, baseline.width, baseline.height, { threshold: 0.1 });
          diffPercent = (pixels / (baseline.width * baseline.height)) * 100;
          if (diffPercent > threshold) { fs.writeFileSync(path.join(diffDir, `${scenario.id}.png`), PNG.sync.write(diff)); errors.push(`diff=${diffPercent.toFixed(2)}%, seuil=${threshold}%`); }
        }
        results.push({ id: scenario.id, status: errors.length ? 'FAIL' : 'PASS', diff_percent: Number(diffPercent.toFixed(4)), errors });
      }
    } catch (error) { results.push({ id: scenario.id, status: 'ERROR', errors: [error.message] }); }
    finally { await page.close(); }
  }
} finally { await api.dispose(); await browser.close(); }
if (generate) {
  const generated = results.filter((r) => r.status === 'GENERATED').length;
  if (generated === scenarios.length) writeManifest();
  else console.error(`BASELINE_MANIFEST_NOT_WRITTEN generated=${generated} expected=${scenarios.length}`);
}
const passed = results.filter((r) => r.status === (generate ? 'GENERATED' : 'PASS')).length;
const failed = results.length - passed;
console.log(JSON.stringify({ test_id: 'VISUAL-CONTRACT-001', candidate_version: VERSION, source_commit: COMMIT, source_ref: process.env.GITHUB_REF || 'local', run_id: RUN_ID, mode: generate ? 'GENERATE_LOCAL_ONLY' : 'CHECK', total: results.length, passed, failed, threshold_percent: threshold, baseline_policy: contract.baseline_policy, results }, null, 2));
console.error(`VISUAL_CONTRACT_SUMMARY version=${VERSION} commit=${COMMIT} total=${results.length} pass=${passed} fail=${failed}`);
process.exit(failed === 0 && passed === 30 ? 0 : 1);
