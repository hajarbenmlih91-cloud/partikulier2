import { chromium } from 'playwright';
import fs from 'node:fs';
import { AxeBuilder } from '@axe-core/playwright';

const base = process.env.PK_BASE ?? 'http://localhost:8090';
const version = process.env.PK_VERSION ?? '1.7.1';
const commit = process.env.PK_COMMIT ?? '';
const report = process.env.PK_A11Y_REPORT ?? `documentation/accessibility-v${version}.json`;
if (!/^[0-9a-f]{40}$/.test(commit)) throw new Error('PK_COMMIT doit être un SHA de 40 caractères');
const cases = [
  ['fr', 'fr', 375, 667], ['fr', 'fr', 1280, 800],
  ['en', 'en', 375, 667], ['en', 'en', 1280, 800],
  ['ar', 'ar', 375, 667], ['ar', 'ar', 1280, 800],
];
const browser = await chromium.launch({ headless: true });
const tests = [];
for (const [id, locale, width, height] of cases) {
  const context = await browser.newContext({ viewport: { width, height } });
  const page = await context.newPage();
  const url = `${base}/${locale}/`;
  let response;
  try {
    response = await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
    const axeResults = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'])
      .analyze();
    const checks = await page.evaluate(() => {
      const html = document.documentElement;
      const visible = (el) => !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
      const unlabeledButtons = [...document.querySelectorAll('button,[role="button"]')].filter(visible).filter(el => !(el.getAttribute('aria-label') || el.textContent.trim()));
      const unlabeledInputs = [...document.querySelectorAll('input,select,textarea')].filter(visible).filter(el => {
        if (el.type === 'hidden') return false;
        return !(el.getAttribute('aria-label') || el.getAttribute('aria-labelledby') || (el.id && document.querySelector(`label[for="${CSS.escape(el.id)}"]`)));
      });
      const badImages = [...document.images].filter(img => !img.hasAttribute('alt'));
      return {
        lang: html.lang,
        dir: html.dir || 'ltr',
        title: document.title,
        unlabeled_buttons: unlabeledButtons.length,
        unlabeled_inputs: unlabeledInputs.length,
        images_without_alt: badImages.length,
        visible_focusable: document.querySelectorAll('a[href],button,input,select,textarea,[tabindex]:not([tabindex="-1"])').length,
      };
    });
    const axeViolations = axeResults.violations.map((violation) => ({
      id: violation.id,
      impact: violation.impact,
      help: violation.help,
      nodes: violation.nodes.map((node) => ({ target: node.target, html: node.html })),
    }));
    const expectedDir = locale === 'ar' ? 'rtl' : 'ltr';
    const ok = response?.status() === 200 && checks.lang.toLowerCase().startsWith(locale) && checks.dir === expectedDir && checks.title && checks.unlabeled_buttons === 0 && checks.unlabeled_inputs === 0 && checks.images_without_alt === 0 && axeViolations.length === 0;
    tests.push({ id, locale, viewport: `${width}x${height}`, url, status: ok ? 'PASS' : 'FAIL', http_status: response?.status() ?? 0, checks, axe: { violations: axeViolations.length, details: axeViolations } });
  } catch (error) {
    tests.push({ id, locale, viewport: `${width}x${height}`, url, status: 'FAIL', error: String(error) });
  }
  await context.close();
}
await browser.close();
const failed = tests.filter(t => t.status !== 'PASS');
const payload = { test_id: 'A11Y-DOM-001', candidate_version: version, source_commit: commit, source_ref: process.env.GITHUB_REF ?? 'local', run_id: process.env.PK_RUN_ID ?? 'local', started_at_utc: new Date().toISOString(), finished_at_utc: new Date().toISOString(), command: 'node scripts/test-accessibility.mjs', fixture: 'FR/EN/AR home at mobile and desktop', status: failed.length ? 'FAIL' : 'PASS', exit_code: failed.length ? 1 : 0, total: tests.length, passed: tests.length - failed.length, failed: failed.length, tests, artifacts: [report], limitations: ['Automated DOM gate does not replace independent manual WCAG review'] };
fs.mkdirSync(new URL('.', `file://${process.cwd()}/${report}`).pathname, { recursive: true });
fs.writeFileSync(report, JSON.stringify(payload, null, 2));
console.log(JSON.stringify(payload));
process.exitCode = failed.length ? 1 : 0;
