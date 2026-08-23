import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { chromium, request } from 'playwright';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';

const VERSION = process.env.PK_VERSION || '6.17.13';
const BASE_URL = process.env.PK_BASE || 'http://localhost:8090';
const ROOT = process.cwd();
const BASELINE_DIR = path.join(ROOT, 'tests', `baselines-${VERSION}`);
const CURRENT_DIR = path.join(ROOT, 'tests', 'current');
const DIFF_DIR = path.join(ROOT, 'tests', 'diff');
const MANIFEST = path.join(BASELINE_DIR, 'SHA256SUMS');
const SEUIL = 0.5;
const scenarios = [
  { name: 'fr-accueil', url: '/fr/', status: 200, lang: 'fr', dir: 'ltr' },
  { name: 'en-accueil', url: '/en/', status: 200, lang: 'en', dir: 'ltr' },
  { name: 'ar-accueil', url: '/ar/', status: 200, lang: 'ar', dir: 'rtl' },
  { name: 'fr-annonces', url: '/fr/annonces/', status: 200, lang: 'fr', dir: 'ltr' },
  { name: 'en-annonces', url: '/en/annonces/', status: 200, lang: 'en', dir: 'ltr' },
  { name: 'ar-annonces', url: '/ar/annonces/', status: 200, lang: 'ar', dir: 'rtl' },
  { name: 'fr-deposer', url: '/fr/deposer/', status: 200, lang: 'fr', dir: 'ltr' },
  { name: 'en-deposer', url: '/en/deposer-en/', status: 200, lang: 'en', dir: 'ltr' },
  { name: 'ar-deposer', url: '/ar/deposer-ar/', status: 200, lang: 'ar', dir: 'rtl' },
  { name: 'fr-mes-annonces', url: '/fr/mes-annonces/', status: 302, finalPrefix: '/wp-login.php' },
  { name: 'en-mes-annonces', url: '/en/mes-annonces-en/', status: 302, finalPrefix: '/wp-login.php' },
  { name: 'ar-mes-annonces', url: '/ar/mes-annonces-ar/', status: 302, finalPrefix: '/wp-login.php' },
  { name: 'fr-favoris', url: '/fr/favoris/', status: 200, lang: 'fr', dir: 'ltr' },
  { name: 'en-favoris', url: '/en/favoris-en/', status: 200, lang: 'en', dir: 'ltr' },
  { name: 'ar-favoris', url: '/ar/favoris-ar/', status: 200, lang: 'ar', dir: 'rtl' }
];
const viewports = [
  { name: 'desktop', width: 1280, height: 800 },
  { name: 'mobile', width: 375, height: 667 }
];
const expectedFiles = scenarios.flatMap((s) => viewports.map((v) => `${s.name}-${v.name}.png`));
const generate = process.argv.includes('baseline') || process.env.PK_GENERATE === '1';
const commit = process.env.PK_COMMIT || 'uncommitted';

function sha256(file) { return crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex'); }
function fail(message) { throw new Error(message); }
function ensureDir(dir) { fs.mkdirSync(dir, { recursive: true }); }
function readManifest() {
  if (!fs.existsSync(MANIFEST)) fail(`SHA256SUMS manquant: ${MANIFEST}`);
  const rows = fs.readFileSync(MANIFEST, 'utf8').trim().split(/\r?\n/).filter(Boolean);
  if (rows.length !== expectedFiles.length) fail(`SHA256SUMS contient ${rows.length} lignes au lieu de ${expectedFiles.length}`);
  const entries = new Map(rows.map((line) => { const [hash, name] = line.trim().split(/\s+\*?/, 2); return [name, hash]; }));
  for (const name of expectedFiles) {
    const file = path.join(BASELINE_DIR, name);
    if (!fs.existsSync(file) || fs.statSync(file).size === 0) fail(`baseline absente ou vide: ${name}`);
    if (entries.get(name) !== sha256(file)) fail(`hash invalide: ${name}`);
  }
}
function writeManifest() {
  const lines = expectedFiles.map((name) => `${sha256(path.join(BASELINE_DIR, name))}  ${name}`);
  fs.writeFileSync(MANIFEST, `${lines.join('\n')}\n`);
}

if (!generate) {
  if (!fs.existsSync(BASELINE_DIR)) fail(`répertoire baseline manquant: ${BASELINE_DIR}`);
  readManifest();
} else {
  ensureDir(BASELINE_DIR);
  for (const file of expectedFiles) fs.rmSync(path.join(BASELINE_DIR, file), { force: true });
}
ensureDir(CURRENT_DIR);
ensureDir(DIFF_DIR);

const browser = await chromium.launch({ headless: true });
const api = await request.newContext({ baseURL: BASE_URL, maxRedirects: 0, extraHTTPHeaders: { 'Cache-Control': 'no-cache' } });
const results = [];
try {
  for (const scenario of scenarios) {
    const direct = await api.get(scenario.url);
    const directStatus = direct.status();
    const location = direct.headers().location || '';
    const finalPath = location ? new URL(location, BASE_URL).pathname : scenario.url;
    const directErrors = [];
    if (directStatus !== scenario.status) directErrors.push(`http=${directStatus}, attendu=${scenario.status}`);
    if (scenario.finalPrefix && !finalPath.startsWith(scenario.finalPrefix)) directErrors.push(`location=${finalPath}, préfixe=${scenario.finalPrefix}`);
    for (const vp of viewports) {
      const testName = `${scenario.name}-${vp.name}`;
      const page = await browser.newPage({ viewport: { width: vp.width, height: vp.height } });
      const output = path.join(generate ? BASELINE_DIR : CURRENT_DIR, `${testName}.png`);
      try {
        const navigated = await page.goto(new URL(scenario.url, BASE_URL).toString(), { waitUntil: 'networkidle', timeout: 30000 });
        const rawLang = (await page.locator('html').getAttribute('lang') || '').toLowerCase() || null;
        const htmlLang = rawLang ? rawLang.split(/[-_]/)[0] : null;
        const htmlDir = (await page.locator('html').getAttribute('dir') || '').toLowerCase() || null;
        const navigatedFinalPath = new URL(page.url()).pathname;
        const errors = [...directErrors];
        if (scenario.finalPrefix && !navigatedFinalPath.startsWith(scenario.finalPrefix)) errors.push(`navigation_final=${navigatedFinalPath}, préfixe=${scenario.finalPrefix}`);
        if (scenario.status === 200) {
          if (htmlLang !== scenario.lang) errors.push(`lang=${htmlLang}, attendu=${scenario.lang}`);
          if (htmlDir !== scenario.dir) errors.push(`dir=${htmlDir}, attendu=${scenario.dir}`);
        }
        if (!navigated) errors.push('navigation sans réponse');
        await page.evaluate(() => {
          for (const img of document.images) {
            img.loading = 'eager';
            if (img.dataset && img.dataset.src && (!img.currentSrc || img.currentSrc.endsWith('/'))) img.src = img.dataset.src;
          }
        });
        await page.evaluate(async () => {
          const images = Array.from(document.images);
          await Promise.all(images.map((img) => {
            if (img.complete) return img.decode ? img.decode().catch(() => undefined) : undefined;
            return new Promise((resolve) => { img.addEventListener('load', resolve, { once: true }); img.addEventListener('error', resolve, { once: true }); });
          }));
        });
        await page.waitForTimeout(250);
        await page.screenshot({ path: output, fullPage: true });
        if (generate) {
          results.push({ name: testName, http: directStatus, final_path: navigatedFinalPath, status: errors.length ? 'FAIL' : 'GENERATED', lang: htmlLang, dir: htmlDir, errors });
        } else {
          const baseline = PNG.sync.read(fs.readFileSync(path.join(BASELINE_DIR, `${testName}.png`)));
          const current = PNG.sync.read(fs.readFileSync(output));
          if (baseline.width !== current.width || baseline.height !== current.height) errors.push(`dimensions=${current.width}x${current.height}, attendu=${baseline.width}x${baseline.height}`);
          let diffPercent = 100;
          if (!errors.some((e) => e.startsWith('dimensions='))) {
            const diff = new PNG({ width: baseline.width, height: baseline.height });
            const pixels = pixelmatch(baseline.data, current.data, diff.data, baseline.width, baseline.height, { threshold: 0.1 });
            diffPercent = (pixels / (baseline.width * baseline.height)) * 100;
            if (diffPercent > SEUIL) { fs.writeFileSync(path.join(DIFF_DIR, `${testName}.png`), PNG.sync.write(diff)); errors.push(`diff=${diffPercent.toFixed(2)}%, seuil=${SEUIL}%`); }
          }
          results.push({ name: testName, http: directStatus, final_path: navigatedFinalPath, status: errors.length ? 'FAIL' : 'OK', lang: htmlLang, dir: htmlDir, diff: `${diffPercent.toFixed(2)}%`, errors });
        }
      } catch (error) { results.push({ name: testName, status: 'ERROR', errors: [error.message] }); }
      finally { await page.close(); }
    }
  }
} finally { await api.dispose(); await browser.close(); }
if (generate) writeManifest();
const passed = results.filter((r) => r.status === (generate ? 'GENERATED' : 'OK')).length;
const failed = results.length - passed;
console.log(JSON.stringify({ version: VERSION, commit, base: BASE_URL, mode: generate ? 'GENERATE' : 'CHECK', total: results.length, passed, failed, results }, null, 2));
console.error(`VISUAL_SUMMARY version=${VERSION} commit=${commit} total=${results.length} pass=${passed} fail=${failed}`);
process.exit(failed === 0 && passed === expectedFiles.length ? 0 : 1);
