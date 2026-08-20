import { chromium } from 'playwright';
import fs from 'fs';
const base = process.env.PK_BASE || 'http://localhost:8090';
const routes = {
  archive: '/annonces/',
  detail: '/annonce/casablanca/appartement-lumineux-3-pieces/',
  depot: '/deposer-une-annonce/',
  favoris: '/favoris/'
};
const browser = await chromium.launch();
const rows = [];
for (const [name, route] of Object.entries(routes)) {
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));
  page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text()); });
  const response = await page.goto(base + route, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForTimeout(1500);
  const data = await page.evaluate(() => ({
    url: location.href,
    estatikScripts: [...document.scripts].map(s => s.src).filter(src => /estatik/i.test(src)),
    queryMonitor: [...document.scripts].some(s => /query-monitor/i.test(s.src)),
    favoriteButtons: document.querySelectorAll('[data-favorite], .pk-favorite, button[aria-label*="favor" i]').length,
    forms: document.querySelectorAll('form').length,
    fields: document.querySelectorAll('form input, form select, form textarea').length,
    gallery: document.querySelectorAll('[class*="gallery" i], [class*="slider" i], .slick-slider, .es-gallery').length,
    images: document.images.length,
    filters: document.querySelectorAll('select, input[type="range"], [class*="filter" i]').length,
    scripts: [...document.scripts].map(s => s.src).filter(Boolean)
  }));
  rows.push({ name, route, status: response?.status() ?? null, consoleErrors: errors, ...data });
  await page.close();
}
await browser.close();
fs.writeFileSync('rapport-estatik-real-ui-6.14.1.json', JSON.stringify(rows, null, 2) + '\n');
console.log(JSON.stringify(rows, null, 2));
