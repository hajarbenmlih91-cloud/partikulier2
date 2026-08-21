import { chromium } from 'playwright';
import fs from 'node:fs';
import { execFileSync } from 'node:child_process';

const base = process.env.PK_URL || 'http://localhost:8090';
const depositPages = JSON.parse(execFileSync('wp', ['--path=wp', 'eval', '$out=array(); foreach(get_pages(array("post_status"=>"publish","number"=>-1)) as $p){$tpl=get_post_meta($p->ID,"_wp_page_template",true); if(strpos($tpl,"page-deposer-annonce")!==false && function_exists("pll_get_post_language")){ $out[pll_get_post_language($p->ID)]=wp_make_link_relative(get_permalink($p->ID)); }} echo wp_json_encode($out);'], { encoding: 'utf8' }).trim());
const cases = [
  { lang: 'ar', dir: 'rtl', archive: '/ar/annonces/', deposit: depositPages.ar },
  { lang: 'en', dir: 'ltr', archive: '/en/annonces/', deposit: depositPages.en },
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
    const count = await page.locator('a[href*="/property/"], a[href*="/annonce/"]').count();
    return { ok: count > 0, property_links: count, detail: `property_links=${count}` };
  });
  const geographicLinks = page.locator('a[href*="/annonce/"]');
  const legacyLinks = page.locator('a[href*="/property/"]');
  const propertyHref = await (await geographicLinks.count() ? geographicLinks : legacyLinks).first().getAttribute('href');
  if (propertyHref) {
    await check('property', new URL(propertyHref, base).pathname, async () => {
      const html = await page.locator('html').getAttribute('lang');
      const dir = await page.locator('html').getAttribute('dir');
      const hreflang = await page.locator('link[rel="alternate"][hreflang]').count();
      const ownerNote = await page.locator('.pk-owner-note [lang="fr"]').count();
      return { ok: html?.startsWith(c.lang) && (c.lang !== 'ar' || dir === 'rtl') && hreflang >= 4, html, dir, hreflang, owner_note_fr_blocks: ownerNote };
    });
  }
  const depositHref = c.deposit || null;
  if (depositHref) {
    await check('deposit', new URL(depositHref, base).pathname, async () => {
      const html = await page.locator('html').getAttribute('lang');
      const dir = await page.locator('html').getAttribute('dir');
      const freeText = await page.locator('[data-pk-free-text][lang]').count();
      const title = await page.locator('#pk-title').count();
      const extra = await page.locator('#pk-extra').count();
      return { ok: html?.startsWith(c.lang) && title === 1 && extra === 1 && freeText >= 3, html, dir, free_text_fields: freeText, title, extra };
    });
  } else {
    row.failures.push('deposit: localized deposit link not found');
  }
  row.passed = row.failures.length === 0;
  results.push(row);
  await context.close();
}
await browser.close();
const report = { passed: results.every(r => r.passed), results };
fs.writeFileSync(process.env.PK_REPORT || '/tmp/partikulier-6.17-journeys.json', JSON.stringify(report, null, 2));
console.log(JSON.stringify(report, null, 2));
process.exit(report.passed ? 0 : 1);
