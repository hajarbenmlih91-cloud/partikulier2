import { chromium } from 'playwright';
import fs from 'node:fs';

const base = process.env.PK_URL || 'http://localhost:8090';
const cases = [
  { lang: 'ar', dir: 'rtl', archive: '/ar/annonces/', deposit: '/ar/%d8%a5%d8%b6%d8%a7%d9%81%d8%a9-%d8%a5%d8%b9%d9%84%d8%a7%d9%86/' },
  { lang: 'en', dir: 'ltr', archive: '/en/annonces/', deposit: '/en/submit-a-listing/' },
];
const browser = await chromium.launch({ headless: true });
const results = [];
for (const c of cases) {
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: c.lang === 'ar' ? 'ar-MA' : 'en-US', extraHTTPHeaders: { 'Accept-Language': c.lang } });
  const page = await context.newPage();
  const row = { lang: c.lang, passed: true, failures: [], screens: [] };
  async function check(name, path, fn) {
    const response = await page.goto(base + path, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await page.waitForTimeout(300);
    const finalStatus = response?.status() ?? 0;
    const data = await fn();
    data.http_status = finalStatus;
    if (finalStatus >= 400) data.ok = false;
    const ok = data.ok !== false;
    row.screens.push({ name, url: page.url(), ...data });
    if (!ok) row.failures.push(name + ': ' + (data.detail || 'failed'));
  }
  await check('home', `/${c.lang}/`, async () => {
    const html = await page.locator('html').getAttribute('lang');
    const dir = await page.locator('html').getAttribute('dir');
    return { ok: html?.startsWith(c.lang), html, dir, detail: `lang=${html} dir=${dir}` };
  });
  await check('archive', c.archive, async () => {
    const count = await page.locator('a[href*="/property/"]').count();
    return { ok: count > 0, property_links: count, detail: `property_links=${count}` };
  });
  const propertyHref = await page.locator('a[href*="/property/"]').first().getAttribute('href');
  if (propertyHref) {
    await check('property', new URL(propertyHref, base).pathname, async () => {
      const html = await page.locator('html').getAttribute('lang');
      const dir = await page.locator('html').getAttribute('dir');
      const hreflang = await page.locator('link[rel="alternate"][hreflang]').count();
      const ownerNote = await page.locator('.pk-owner-note [lang="fr"]').count();
      return { ok: html?.startsWith(c.lang) && (c.lang !== 'ar' || dir === 'rtl') && hreflang >= 4, html, dir, hreflang, owner_note_fr_blocks: ownerNote };
    });
  }
  await check('deposit', c.deposit, async () => {
    const html = await page.locator('html').getAttribute('lang');
    const dir = await page.locator('html').getAttribute('dir');
    const freeText = await page.locator('[data-pk-free-text][lang]').count();
    const title = await page.locator('#pk-title').count();
    const extra = await page.locator('#pk-extra').count();
    return { ok: html?.startsWith(c.lang) && title === 1 && extra === 1 && freeText >= 3, html, dir, free_text_fields: freeText, title, extra };
  });
  row.passed = row.failures.length === 0;
  results.push(row);
  await context.close();
}
await browser.close();
const report = { passed: results.every(r => r.passed), results };
fs.writeFileSync(process.env.PK_REPORT || '/tmp/partikulier-6.17-journeys.json', JSON.stringify(report, null, 2));
console.log(JSON.stringify(report, null, 2));
process.exit(report.passed ? 0 : 1);
