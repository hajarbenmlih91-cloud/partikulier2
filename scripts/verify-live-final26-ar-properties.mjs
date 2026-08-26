import fs from 'node:fs';
import { chromium, firefox, webkit } from 'playwright';

const base = process.env.PK_BASE || 'http://localhost:8090';
const browserName = process.env.PK_BROWSER || 'chromium';
const type = { chromium, firefox, webkit }[browserName];
if (!type) throw new Error(`Navigateur inconnu: ${browserName}`);
const reportPath = process.env.PK_REPORT || `documentation/live-final26-ar-properties-${browserName}.json`;
const browser = await type.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
const result = { test_id: 'LIVE-FINAL26-AR-PROPERTIES', browser: browserName, base, status: 'PASS', properties: [] };
try {
  const archiveResponse = await page.goto(new URL('/ar/annonces/', base).toString(), { waitUntil: 'domcontentloaded', timeout: 30000 });
  const hrefs = await page.locator('a[href*="/ar/annonce/"], a[href*="/ar/property/"]').evaluateAll((links) => [...new Set(links.map((link) => link.href))].slice(0, 3));
  for (const href of hrefs) {
    try {
      const response = await page.goto(href, { waitUntil: 'domcontentloaded', timeout: 30000 });
      let networkidle = true;
      try { await page.waitForLoadState('networkidle', { timeout: 10000 }); } catch { networkidle = false; }
      const data = await page.evaluate(() => {
        const scripts = [...document.querySelectorAll('script[type="application/ld+json"]')].map((node) => node.textContent || '');
        const parsed = [];
        for (const raw of scripts) { try { parsed.push(JSON.parse(raw)); } catch {} }
        const flat = JSON.stringify(parsed);
        return {
          url: location.href,
          lang: document.documentElement.lang,
          dir: document.documentElement.dir,
          title: document.title,
          jsonld_scripts: scripts.length,
          jsonld_has_arabic: /[ء-ي]{3,}/.test(flat),
          jsonld_has_name: parsed.some((item) => JSON.stringify(item).includes('"name"')),
          forbidden_french: /Saïdia|Annonces immobilières gratuites/i.test(flat),
        };
      });
      const ok = response?.status() === 200 && data.lang.toLowerCase().startsWith('ar') && data.dir === 'rtl' && data.jsonld_scripts > 0 && data.jsonld_has_arabic && data.jsonld_has_name && !data.forbidden_french;
      result.properties.push({ href, status: ok ? 'PASS' : 'FAIL', http_status: response?.status() || 0, networkidle, data });
    } catch (error) {
      result.properties.push({ href, status: 'FAIL', error: String(error) });
    }
  }
  if (archiveResponse?.status() !== 200 || result.properties.length !== 3 || result.properties.some((row) => row.status !== 'PASS')) result.status = 'FAIL';
} finally {
  await browser.close();
}
result.passed = result.properties.filter((row) => row.status === 'PASS').length;
result.failed = result.properties.filter((row) => row.status !== 'PASS').length;
result.limitation = 'La vérification automatique ne remplace pas une validation éditoriale native arabe ni la revue humaine indépendante.';
fs.mkdirSync(new URL('.', `file://${process.cwd()}/${reportPath}`).pathname, { recursive: true });
fs.writeFileSync(reportPath, JSON.stringify(result, null, 2));
console.log(JSON.stringify(result));
process.exitCode = result.status === 'PASS' ? 0 : 1;
