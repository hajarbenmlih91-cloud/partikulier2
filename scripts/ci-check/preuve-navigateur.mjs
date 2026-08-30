// Preuve navigateur reel : le cache empoisonne (0 octet) est-il rendu comme page blanche ?
// Usage: node preuve-navigateur.mjs <port> <chemin> <etat>
import { chromium } from 'playwright';

const port = process.argv[2] || '8090';
const path = process.argv[3] || '/annonces/';
const label = process.argv[4] || '?';
const BASE = `http://localhost:${port}`;

const browser = await chromium.launch({
  executablePath: '/home/user/.pwtest/br/chromium-1148/chrome-linux/chrome',
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});
const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
await ctx.addCookies([{ name: 'pll_language', value: 'fr', domain: 'localhost', path: '/' }]);
const page = await ctx.newPage();

let respStatus = 'aucune navigation', bodyLen = -1, title = '(vide)', visible = 'n/a', shot = 'n/a';
try {
  const resp = await page.goto(BASE + path, { waitUntil: 'load', timeout: 25000 });
  respStatus = String(resp ? resp.status() : 'null');
  bodyLen = (await page.content()).length;
  title = await page.title();
  visible = await page.evaluate(() => {
    const b = document.body;
    if (!b) return 'body absent';
    const txt = (b.innerText || '').trim().length;
    const els = b.querySelectorAll('*').length;
    const bg = getComputedStyle(b).backgroundColor;
    return `texte=${txt}car nodes=${els} fond=${bg}`;
  });
  await page.screenshot({ path: `/tmp/pk-logs/nav-${label}-${port}${path.replace(/[^a-z0-9]/gi, '_')}.png`, fullPage: false });
  shot = 'ok';
} catch (e) {
  visible = 'ERREUR: ' + String(e.message).split('\n')[0].slice(0, 90);
}
console.log(`[${label}] ${BASE}${path}`);
console.log(`   statut=${respStatus} content-length(html)=${bodyLen} title="${title}"`);
console.log(`   rendu=${visible} capture=${shot}`);
await browser.close();
