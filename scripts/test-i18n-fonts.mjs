import { chromium } from 'playwright';
import fs from 'node:fs';
const base = process.env.PK_BASE || 'http://localhost:8090';
const browser = await chromium.launch({ headless: true });
const VERSION = process.env.PK_VERSION || '6.17.16';
const result = { version: VERSION, base, passed: true, checks: [] };
for (const item of [
  { lang: 'ar', path: '/ar/', expectedFont: true, expectedDir: 'rtl' },
  { lang: 'fr', path: '/fr/', expectedFont: null, expectedDir: 'ltr' },
  { lang: 'en', path: '/en/', expectedFont: null, expectedDir: 'ltr' }
]) {
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: item.lang === 'ar' ? 'ar-MA' : item.lang === 'en' ? 'en-US' : 'fr-FR' });
  const page = await context.newPage();
  const fontRequests = [];
  page.on('request', (r) => { if (/NotoSansArabic|Noto.Sans.Arabic/i.test(r.url())) fontRequests.push(r.url()); });
  try {
    await page.goto(`${base}${item.path}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
    const data = await page.evaluate(async () => {
      await Promise.race([
        document.fonts.ready,
        new Promise((resolve) => setTimeout(resolve, 5000)),
      ]);
      const probe = document.createElement('span');
      probe.lang = 'ar';
      probe.textContent = 'العربية';
      probe.style.cssText = 'font-family:"Noto Sans Arabic"; position:absolute; left:-9999px';
      document.body.appendChild(probe);
      return {
        fontsCheck: document.fonts.check('16px "Noto Sans Arabic"', 'العربية'),
        family: getComputedStyle(probe).fontFamily,
        htmlLang: document.documentElement.lang,
        dir: document.documentElement.dir,
        readyState: document.fonts.status
      };
    });
    const fontOk = item.expectedFont === null ? true : (fontRequests.length > 0 && data.fontsCheck && data.family.includes('Noto Sans Arabic'));
    const ok = fontOk && data.htmlLang.toLowerCase().split(/[-_]/)[0] === item.lang && data.dir === item.expectedDir;
    result.checks.push({ ...item, ok, requests: fontRequests.length, requestUrls: fontRequests, ...data });
    if (!ok) result.passed = false;
  } catch (error) {
    result.passed = false;
    result.checks.push({ ...item, ok: false, error: error.message });
  } finally { await context.close(); }
}
await browser.close();
const report = process.env.PK_REPORT || `documentation/i18n-fonts-v${VERSION}.json`;
fs.writeFileSync(report, `${JSON.stringify(result, null, 2)}\n`);
console.log(JSON.stringify(result, null, 2));
process.exit(result.passed ? 0 : 1);
