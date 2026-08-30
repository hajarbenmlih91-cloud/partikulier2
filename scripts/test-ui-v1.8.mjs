import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { chromium, firefox, webkit } from 'playwright';

const ROOT = process.cwd();
const BASE_URL = process.env.PK_BASE || 'http://localhost:8090';
const RUN_ID = process.env.PK_RUN_ID || 'local';
const VERSION = process.env.PK_VERSION || '1.8.0';
const CONTRACT_PATH = path.join(ROOT, 'documentation', 'visual-scenarios-v1.7.1.json');
const contract = JSON.parse(fs.readFileSync(CONTRACT_PATH, 'utf8'));
const OUT = path.resolve(process.env.PK_UI_OUT || path.join(ROOT, 'tests', `ui-v${VERSION}`));
const BEFORE_DIR = process.env.PK_BEFORE_DIR ? path.resolve(process.env.PK_BEFORE_DIR) : '';
const cacheBust = process.env.PK_CACHE_BUST || '';
const sourceCommit = process.env.PK_COMMIT || execFileSync('git', ['rev-parse', 'HEAD'], { encoding: 'utf8' }).trim();
const browserName = process.env.PK_BROWSER || 'chromium';
const browserType = { chromium, firefox, webkit }[browserName];
if (!browserType) throw new Error(`Navigateur Playwright inconnu : ${browserName}`);

if (!/^[0-9a-f]{40}$/.test(sourceCommit)) throw new Error('source_commit doit être un SHA Git de 40 caractères');
if (!Array.isArray(contract.scenarios) || contract.scenarios.length !== 30) throw new Error('Le contrat visuel doit contenir exactement 30 scénarios');
fs.mkdirSync(OUT, { recursive: true });
fs.mkdirSync(path.join(OUT, 'after'), { recursive: true });

const viewportMap = {
  desktop: { width: 1280, height: 800 },
  mobile: { width: 375, height: 667 },
};
const requiredMobileWidths = [320, 360, 375, 390];
const imageDom = [];
const linkCrawl = [];
const responsive = [];
const scenarios = [];

function requestUrl(raw) {
  const url = new URL(raw, BASE_URL);
  if (cacheBust) url.searchParams.set('pkqa', cacheBust);
  return url.toString();
}
function sha256(file) {
  return crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
}
function isInternalHttp(href) {
  try {
    const url = new URL(href, BASE_URL);
    return /^https?:$/.test(url.protocol) && url.origin === new URL(BASE_URL).origin;
  } catch { return false; }
}
function localePathOk(href, locale) {
  try {
    const pathname = new URL(href, BASE_URL).pathname;
    return pathname === `/${locale}/` || pathname.startsWith(`/${locale}/`);
  } catch { return false; }
}
function extraWidthsFor(pageType) {
  return ['home', 'archive', 'single', 'deposer'].includes(pageType) ? requiredMobileWidths : [];
}
function classifyLink(link, scenarioUrl) {
  const target = new URL(link.href);
  const source = new URL(scenarioUrl);
  if (target.hash && target.pathname === source.pathname && target.search === source.search) return 'same-page-anchor';
  if (/^\/(?:wp-login\.php|wp-register\.php)(?:$|\?)/.test(target.pathname)) return 'wordpress-auth-endpoint';
  if (target.pathname.startsWith('/wp-') || target.pathname.includes('/wp-json/')) return 'wordpress-endpoint';
  if (link.className.includes('pk-lang') || link.href.includes('hreflang=') || link.hreflang) return 'language-selector';
  return null;
}

const browser = await browserType.launch({ headless: true });
try {
  for (const scenario of contract.scenarios) {
    const viewport = viewportMap[scenario.viewport];
    const page = await browser.newPage({ viewport });
    const responses = new Map();
    page.on('response', (response) => responses.set(response.url(), response.status()));
    const errors = [];
    const url = requestUrl(scenario.url);
    let finalUrl = '';
    try {
      const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
      if (!response) errors.push('navigation sans réponse');
      if (response && response.status() !== scenario.expected_http) errors.push(`http=${response.status()}, attendu=${scenario.expected_http}`);
      await page.evaluate(async () => {
        if (document.fonts && document.fonts.ready) await Promise.race([document.fonts.ready, new Promise((resolve) => setTimeout(resolve, 5000))]);
      });
      await page.waitForTimeout(250);
      // Les fiches utilisent le lazy-loading ; faire défiler chaque image permet
      // de vérifier aussi les médias hors écran, sans désactiver les contrôles
      // `complete`, dimensions naturelles, ressource observée et statut HTTP.
      const imageLocator = page.locator('img');
      for (let imageIndex = 0; imageIndex < await imageLocator.count(); imageIndex += 1) {
        await imageLocator.nth(imageIndex).scrollIntoViewIfNeeded().catch(() => {});
      }
      await page.evaluate(() => window.scrollTo(0, 0));
      await page.waitForTimeout(300);
      finalUrl = page.url();
      const langDir = await page.locator('html').evaluate((html) => ({ lang: html.lang || '', dir: html.dir || '' }));
      const lang = langDir.lang.toLowerCase().split(/[-_]/)[0];
      if (lang !== scenario.locale) errors.push(`lang=${lang}, attendu=${scenario.locale}`);
      if (langDir.dir !== scenario.expected_dir) errors.push(`dir=${langDir.dir}, attendu=${scenario.expected_dir}`);

      const pageImages = await page.locator('img').evaluateAll((images) => images.map((img) => ({
        src: img.getAttribute('src') || '', currentSrc: img.currentSrc || '', alt: img.getAttribute('alt') || '',
        complete: img.complete, naturalWidth: img.naturalWidth, naturalHeight: img.naturalHeight,
      })));
      const resources = await page.evaluate(() => performance.getEntriesByType('resource').map((entry) => entry.name));
      const imageRows = pageImages.map((img) => {
        const imageUrl = img.currentSrc || img.src;
        const status = responses.get(imageUrl) || responses.get(img.src) || null;
        const resourceSeen = resources.includes(imageUrl) || resources.includes(img.src);
        const valid = img.complete && img.naturalWidth > 0 && img.naturalHeight > 0 && resourceSeen && (!status || status === 200);
        return { ...img, http_status: status, resource_seen: resourceSeen, valid };
      });
      imageDom.push({ scenario_id: scenario.id, url: finalUrl, viewport, images: imageRows, valid: imageRows.every((row) => row.valid) });
      if (imageRows.some((row) => !row.valid)) errors.push('image-dom-invalid');

      const links = await page.locator('a[href]').evaluateAll((anchors) => anchors.map((a) => ({ href: a.href, text: (a.textContent || '').trim().slice(0, 120), className: typeof a.className === 'string' ? a.className : '', hreflang: a.getAttribute('hreflang') || '' })));
      const linkRows = [];
      for (const link of links) {
        if (!isInternalHttp(link.href)) continue;
        const target = new URL(link.href);
        const ignoredReason = classifyLink(link, finalUrl);
        const response = await page.request.get(link.href, { maxRedirects: 5, timeout: 15000 }).catch(() => null);
        const status = response ? response.status() : null;
        const finalLinkUrl = response ? response.url() : null;
        const localeOk = ignoredReason ? true : Boolean(finalLinkUrl && localePathOk(finalLinkUrl, scenario.locale));
        linkRows.push({ ...link, status, final_url: finalLinkUrl, locale_expected: scenario.locale, locale_ok: localeOk, ignored: Boolean(ignoredReason), ignored_reason: ignoredReason });
      }
      linkCrawl.push({ scenario_id: scenario.id, url: finalUrl, links: linkRows, valid: linkRows.every((row) => row.status === 200 && row.locale_ok) });
      if (linkRows.some((row) => row.status !== 200 || !row.locale_ok)) errors.push('localized-link-crawl-invalid');

      const screenshot = path.join(OUT, 'after', `${scenario.id}.png`);
      await page.screenshot({ path: screenshot, fullPage: false });
      scenarios.push({ id: scenario.id, url, final_url: finalUrl, viewport, status: errors.length ? 'FAIL' : 'PASS', errors, screenshot, screenshot_sha256: sha256(screenshot), before_dir: BEFORE_DIR || null });

      for (const width of extraWidthsFor(scenario.page)) {
        await page.setViewportSize({ width, height: width === 320 ? 568 : width === 360 ? 740 : width === 375 ? 667 : 844 });
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
        await page.waitForTimeout(150);
        const layout = await page.evaluate(() => {
          const root = document.documentElement;
          const cta = Array.from(document.querySelectorAll('.pk-card-cta, .pk-step-actions .pk-btn, .pk-mobile-action-bar .pk-btn')).map((el) => {
            const rect = el.getBoundingClientRect();
            const style = window.getComputedStyle(el);
            return { el, rect, style };
          }).filter(({ rect, style }) => style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0).map(({ el, rect }) => ({
            text: (el.textContent || '').trim(), left: rect.left, right: rect.right, top: rect.top, bottom: rect.bottom, width: rect.width, height: rect.height,
          }));
          return { scroll_width: root.scrollWidth, client_width: root.clientWidth, horizontal_overflow: root.scrollWidth > root.clientWidth, cta };
        });
        responsive.push({ scenario_id: scenario.id, width, height: width === 320 ? 568 : width === 360 ? 740 : width === 375 ? 667 : 844, layout, valid: !layout.horizontal_overflow && layout.cta.every((item) => item.left >= -1 && item.right <= width + 1 && item.height >= 44) });
      }
    } catch (error) {
      scenarios.push({ id: scenario.id, url, final_url: finalUrl, viewport, status: 'ERROR', errors: [error.message], before_dir: BEFORE_DIR || null });
    } finally {
      await page.close();
    }
  }
} finally {
  await browser.close();
}

const summary = {
  test_id: 'UI-UX-V1.8-001', browser: browserName, candidate_version: VERSION, source_commit: sourceCommit, run_id: RUN_ID,
  base_url: BASE_URL, cache_bust: cacheBust || null, scenario_count: scenarios.length,
  passed: scenarios.filter((row) => row.status === 'PASS').length,
  failed: scenarios.filter((row) => row.status !== 'PASS').length,
  mobile_widths: requiredMobileWidths,
  image_dom_passed: imageDom.filter((row) => row.valid).length,
  image_dom_total: imageDom.length,
  link_crawl_passed: linkCrawl.filter((row) => row.valid).length,
  link_crawl_total: linkCrawl.length,
  responsive_passed: responsive.filter((row) => row.valid).length,
  responsive_total: responsive.length,
  baseline_policy: contract.baseline_policy,
  scenarios,
};
const files = {
  'ui-summary.json': summary,
  'image-dom-report.json': { test_id: 'IMAGE-DOM-V1.8-001', source_commit: sourceCommit, rows: imageDom },
  'link-crawl-report.json': { test_id: 'LINK-CRAWL-V1.8-001', source_commit: sourceCommit, rows: linkCrawl },
  'responsive-layout-report.json': { test_id: 'RESPONSIVE-V1.8-001', source_commit: sourceCommit, rows: responsive },
};
for (const [name, value] of Object.entries(files)) fs.writeFileSync(path.join(OUT, name), `${JSON.stringify(value, null, 2)}\n`);
const manifestRows = Object.keys(files).sort().map((name) => `${sha256(path.join(OUT, name))}  ${name}`);
fs.writeFileSync(path.join(OUT, 'SHA256SUMS'), `${manifestRows.join('\n')}\n`);
console.log(JSON.stringify({ ...summary, output_dir: OUT, sha256_manifest: path.join(OUT, 'SHA256SUMS') }, null, 2));
const ok = summary.failed === 0 && summary.image_dom_passed === summary.image_dom_total && summary.link_crawl_passed === summary.link_crawl_total && summary.responsive_passed === summary.responsive_total;
process.exit(ok ? 0 : 1);
