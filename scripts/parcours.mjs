import { chromium, request } from 'playwright';
import fs from 'node:fs';
import process from 'node:process';

const contract = JSON.parse(fs.readFileSync(new URL('../tests/routes-contract.json', import.meta.url), 'utf8'));
const base = process.env.PK_BASE || contract.base;
const commit = process.env.PK_COMMIT || 'uncommitted';
const scenarios = [{ name: 'root', ...contract.root }, ...contract.routes];
const browser = await chromium.launch({ headless: true });
const results = [];

function pathOf(value) { try { return new URL(value, base).pathname; } catch { return ''; } }
function check(condition, message, errors) { if (!condition) errors.push(message); }

for (const scenario of scenarios) {
  const errors = [];
  const api = await request.newContext({ baseURL: base, maxRedirects: 0, extraHTTPHeaders: { 'Cache-Control': 'no-cache' } });
  const context = await browser.newContext();
  const page = await context.newPage();
  try {
    const response = await api.get(scenario.path);
    const status = response.status();
    const location = response.headers().location || '';
    const redirectCount = status >= 300 && status < 400 && location ? 1 : 0;
    const finalPath = redirectCount ? pathOf(location) : pathOf(scenario.path);
    check(status === scenario.expected_status, `status=${status}, attendu=${scenario.expected_status}`, errors);
    check(redirectCount === scenario.expected_redirects, `redirections=${redirectCount}, attendu=${scenario.expected_redirects}`, errors);
    if (scenario.expected_location) check(finalPath === scenario.expected_location, `location=${finalPath}, attendu=${scenario.expected_location}`, errors);
    if (scenario.expected_final_path) check(finalPath === scenario.expected_final_path, `final=${finalPath}, attendu=${scenario.expected_final_path}`, errors);
    if (scenario.expected_location_prefix) check(finalPath.startsWith(scenario.expected_location_prefix), `location=${finalPath}, préfixe=${scenario.expected_location_prefix}`, errors);
    let lang = null;
    let dir = null;
    if (scenario.expected_status === 200) {
      const pageResponse = await page.goto(new URL(scenario.path, base).toString(), { waitUntil: 'domcontentloaded', timeout: 30000 });
      check(pageResponse?.status() === 200, `navigation_status=${pageResponse?.status()}`, errors);
      const rawLang = await page.locator('html').getAttribute('lang');
      lang = rawLang ? rawLang.toLowerCase().split(/[-_]/)[0] : null;
      dir = await page.locator('html').getAttribute('dir');
      check(lang === scenario.expected_lang, `lang=${lang}, attendu=${scenario.expected_lang}`, errors);
      check((dir || '').toLowerCase() === scenario.expected_dir, `dir=${dir}, attendu=${scenario.expected_dir}`, errors);
    }
    results.push({ name: scenario.name, path: scenario.path, status, redirects: redirectCount, location: location || null, final_path: finalPath, lang, dir, result: errors.length ? 'FAIL' : 'PASS', errors });
  } catch (error) {
    results.push({ name: scenario.name, path: scenario.path, result: 'FAIL', errors: [error.message] });
  } finally {
    await page.close();
    await context.close();
    await api.dispose();
  }
}
await browser.close();
const passed = results.filter((r) => r.result === 'PASS').length;
const failed = results.length - passed;
console.log(JSON.stringify({ version: contract.version, commit, base, total: results.length, passed, failed, results }, null, 2));
console.log(`E2E_SUMMARY version=${contract.version} commit=${commit} total=${results.length} pass=${passed} fail=${failed}`);
process.exit(failed === 0 ? 0 : 1);
