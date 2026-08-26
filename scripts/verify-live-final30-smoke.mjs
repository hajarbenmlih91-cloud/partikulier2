import fs from 'node:fs';
import { chromium, firefox, webkit } from 'playwright';

const base = process.env.PK_BASE || 'http://localhost:8090';
const browserName = process.env.PK_BROWSER || 'chromium';
const browserType = { chromium, firefox, webkit }[browserName];
if (!browserType) throw new Error(`Navigateur inconnu: ${browserName}`);
const out = process.env.PK_REPORT || `documentation/live-final30-smoke-${browserName}.json`;
const results = [];
const browser = await browserType.launch({ headless: true });

async function load(page, path, timeout = 30000) {
  const started = Date.now();
  const response = await page.goto(new URL(path, base).toString(), { waitUntil: 'domcontentloaded', timeout });
  let networkidle = true;
  try { await page.waitForLoadState('networkidle', { timeout: 10000 }); } catch { networkidle = false; }
  await page.waitForTimeout(200);
  return { response, networkidle, elapsed_ms: Date.now() - started };
}

async function archiveCase(locale) {
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  const nav = await load(page, `/${locale}/annonces/`);
  const data = await page.evaluate(() => ({
    lang: document.documentElement.lang,
    dir: document.documentElement.dir,
    cards: document.querySelectorAll('.pk-card.pk-card-property').length,
    title: document.title,
    jsonld: [...document.querySelectorAll('script[type="application/ld+json"]')].map((s) => s.textContent || ''),
    body_text: document.body.innerText,
  }));
  const ok = nav.response?.status() === 200 && data.lang.toLowerCase().startsWith(locale) && data.dir === (locale === 'ar' ? 'rtl' : 'ltr') && data.cards > 0;
  results.push({ test: `archive-${locale}`, status: ok ? 'PASS' : 'FAIL', http_status: nav.response?.status() || 0, networkidle: nav.networkidle, elapsed_ms: nav.elapsed_ms, data });
  await page.close();
}

async function filterCase() {
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  const sale = await load(page, '/fr/annonces/?es_action=a-vendre');
  const saleData = await page.evaluate(() => ({
    cards: document.querySelectorAll('.pk-card.pk-card-property').length,
    selected: document.querySelector('#pk-s-action')?.value || '',
    sale_badges: [...document.querySelectorAll('.pk-card.pk-card-property')].filter((el) => /à vendre|a vendre/i.test(el.innerText)).length,
  }));
  const saleOk = sale.response?.status() === 200 && saleData.cards > 0 && saleData.sale_badges > 0;
  results.push({ test: 'filter-sale', status: saleOk ? 'PASS' : 'FAIL', http_status: sale.response?.status() || 0, data: saleData });
  const rent = await load(page, '/fr/annonces/?es_action=a-louer');
  const rentData = await page.evaluate(() => ({ cards: document.querySelectorAll('.pk-card.pk-card-property').length, selected: document.querySelector('#pk-s-action')?.value || '' }));
  results.push({ test: 'filter-rent-dataset-observation', status: rent.response?.status() === 200 ? 'PASS' : 'FAIL', http_status: rent.response?.status() || 0, data: rentData, limitation: 'Un résultat nul est une observation du dataset live, pas une création de fixture.' });
  await page.close();
}

async function arabicPropertyCase() {
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  await load(page, '/ar/annonces/');
  const href = await page.locator('a[href*="/ar/annonce/marrakech/appartement-t2-vue-mer-avec-balcon/"]').first().getAttribute('href').catch(() => null) || await page.locator('a[href*="/ar/annonce/"], a[href*="/ar/property/"]').first().getAttribute('href').catch(() => null);
  if (!href) {
    results.push({ test: 'jsonld-ar-individual', status: 'BLOCKED', reason: 'Aucune fiche AR publique trouvée dans l’archive.' });
    await page.close();
    return;
  }
  const nav = await load(page, href);
  const data = await page.evaluate(() => {
    const jsonld = [...document.querySelectorAll('script[type="application/ld+json"]')].map((s) => s.textContent || '').join('\n');
    return { url: location.href, lang: document.documentElement.lang, dir: document.documentElement.dir, jsonld, body_text: document.body.innerText };
  });
  const forbiddenFrench = /Saïdia|Annonces immobilières gratuites/i.test(data.jsonld);
  const ok = nav.response?.status() === 200 && data.lang.toLowerCase().startsWith('ar') && data.dir === 'rtl' && !forbiddenFrench && /"@type"\s*:\s*"(?:House|Apartment|RealEstateListing)|[ء-ي]{3,}/.test(data.jsonld);
  results.push({ test: 'jsonld-ar-individual', status: ok ? 'PASS' : 'FAIL', http_status: nav.response?.status() || 0, data: { ...data, forbiddenFrench } });
  await page.close();
}

async function popupAndImagesCase() {
  const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
  const nav = await load(page, '/ar/annonces/');
  const popup = await page.evaluate(() => ({ toggle: !!document.querySelector('.pk-filter-toggle'), panel: !!document.querySelector('.pk-filters-panel'), hiddenBefore: document.querySelector('.pk-filters-panel')?.getAttribute('aria-hidden') || null }));
  let popupData = { ...popup, opened: false, hiddenAfter: null, focusReturned: false };
  const toggle = page.locator('.pk-filter-toggle').first();
  if (await toggle.count()) {
    await toggle.click();
    popupData.opened = await page.evaluate(() => document.querySelector('.pk-filters-panel')?.getAttribute('aria-hidden') === 'false');
    await page.keyboard.press('Escape');
    popupData.hiddenAfter = await page.locator('.pk-filters-panel').getAttribute('aria-hidden').catch(() => null);
    popupData.focusReturned = await page.evaluate(() => document.activeElement?.matches('.pk-filter-toggle') || false);
  }
  const imageRows = [];
  for (const width of [320, 360, 375, 390]) {
    await page.setViewportSize({ width, height: width === 320 ? 568 : width === 360 ? 740 : width === 375 ? 667 : 844 });
    await load(page, '/ar/annonces/');
    const images = page.locator('img');
    for (let i = 0; i < await images.count(); i += 1) {
      await images.nth(i).scrollIntoViewIfNeeded().catch(() => {});
      await page.waitForTimeout(150);
    }
    await page.waitForTimeout(1000);
    const row = await page.evaluate(() => ({
      count: document.images.length,
      invalid: [...document.images].filter((img) => !img.complete || img.naturalWidth <= 0 || img.naturalHeight <= 0).length,
      overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
      resources: performance.getEntriesByType('resource').filter((e) => /\.(?:webp|jpg|jpeg|png|avif)(?:\?|$)/i.test(e.name)).length,
    }));
    imageRows.push({ width, ...row });
  }
  const popupOk = popupData.opened && popupData.hiddenAfter === 'true' && popupData.focusReturned;
  const imagesOk = imageRows.every((row) => row.count > 0 && row.invalid === 0 && !row.overflow);
  results.push({ test: 'popup-and-images-ar-mobile', status: nav.response?.status() === 200 && popupOk && imagesOk ? 'PASS' : 'FAIL', http_status: nav.response?.status() || 0, popup: popupData, images: imageRows });
  await page.close();
}

try {
  await archiveCase('fr');
  await archiveCase('en');
  await archiveCase('ar');
  await filterCase();
  await arabicPropertyCase();
  await popupAndImagesCase();
} finally {
  await browser.close();
}
const failed = results.filter((row) => row.status === 'FAIL');
const report = { test_id: 'LIVE-FINAL30-SMOKE', browser: browserName, base, status: failed.length ? 'FAIL' : 'PASS', passed: results.length - failed.length, failed: failed.length, results, limitations: ['Le smoke test ne remplace pas les signoffs humains, la capacité 10 RPS, ni la mesure contractuelle TTFB/LCP.'] };
fs.mkdirSync(new URL('.', `file://${process.cwd()}/${out}`).pathname, { recursive: true });
fs.writeFileSync(out, JSON.stringify(report, null, 2));
console.log(JSON.stringify(report));
process.exitCode = failed.length ? 1 : 0;
