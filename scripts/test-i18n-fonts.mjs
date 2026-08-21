import { chromium } from 'playwright';
import fs from 'node:fs';
const base = process.env.PK_URL || 'http://localhost:8090';
const browser = await chromium.launch({ headless: true });
const result = { passed: true, checks: [] };
for (const item of [{ lang: 'ar', path: '/ar/', expected: true }, { lang: 'fr', path: '/', expected: false }, { lang: 'en', path: '/en/', expected: false }]) {
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: item.lang === 'ar' ? 'ar-MA' : item.lang === 'en' ? 'en-US' : 'fr-FR' });
  const page = await context.newPage();
  const fontRequests = [];
  page.on('request', r => { if (r.url().includes('NotoSansArabic')) fontRequests.push(r.url()); });
  await page.goto(base + item.path, { waitUntil: 'networkidle', timeout: 30000 });
  const data = await page.evaluate(async () => {
    await document.fonts.ready;
    const probe = document.createElement('span');
    probe.lang = 'ar'; probe.setAttribute('data-pk-free-text', '1'); probe.textContent = 'العربية';
    document.body.appendChild(probe);
    return { check: document.fonts.check('16px "Noto Sans Arabic"', 'العربية'), family: getComputedStyle(probe).fontFamily, html: document.documentElement.lang, dir: document.documentElement.dir };
  });
  const loaded = fontRequests.length > 0;
  const ok = item.expected ? loaded && data.check && data.family.includes('Noto Sans Arabic') : !loaded;
  result.checks.push({ lang: item.lang, ok, requests: fontRequests.length, ...data });
  if (!ok) result.passed = false;
  await context.close();
}
await browser.close();
fs.writeFileSync(process.env.PK_REPORT || '/tmp/partikulier-6.17-fonts.json', JSON.stringify(result, null, 2));
console.log(JSON.stringify(result, null, 2));
process.exit(result.passed ? 0 : 1);
