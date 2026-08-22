import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';

const BASE = process.env.PK_BASE || 'http://localhost:8092';
const BASELINE_DIR = 'tests/baselines-6.17.5';
const GENERATE = process.env.PK_GENERATE === '1';
const SEUIL = 0.5;

const SCENARIOS = [
  { id: 'accueil', path: '/' },
  { id: 'annonces', path: '/annonces/' },
  { id: 'deposer', path: '/deposer-PAGE/' },
  { id: 'mes-annonces', path: '/mes-annonces-PAGE/' },
  { id: 'favoris', path: '/favoris-PAGE/' }
];

const VIEWPORTS = [
  { name: 'desktop', width: 1280, height: 800 },
  { name: 'mobile', width: 375, height: 667 }
];

const LANGUAGES = ['fr', 'en', 'ar'];

(async () => {
  const browser = await chromium.launch();
  const results = [];
  let failed = false;

  if (!fs.existsSync(BASELINE_DIR)) fs.mkdirSync(BASELINE_DIR, { recursive: true });

  for (const lang of LANGUAGES) {
    for (const v of VIEWPORTS) {
      for (const s of SCENARIOS) {
        const scenarioId = `${lang}-${s.id}-${v.name}`;
        const langPrefix = lang === 'fr' ? '/fr' : (lang === 'en' ? '/en' : '/ar');
        
        let pathPart = s.path;
        if (pathPart.includes('-PAGE/')) {
            const baseSlug = pathPart.replace('-PAGE/', '');
            pathPart = baseSlug + (lang === 'fr' ? '/' : `-${lang}/`);
        }
        
        const url = BASE + langPrefix + pathPart;
        const context = await browser.newContext({ viewport: v });
        const page = await context.newPage();
        
        try {
          console.log(`Traitement ${scenarioId} : ${url}`);
          const response = await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
          
          if (response.status() !== 200) {
             throw new Error(`HTTP ${response.status()}`);
          }

          if (lang === 'ar') {
            const dir = await page.evaluate(() => document.documentElement.dir || document.body.dir || getComputedStyle(document.documentElement).direction);
            if (dir !== 'rtl') throw new Error(`RTL non détecté (${dir})`);
          }

          const screenshotPath = path.join(BASELINE_DIR, `${scenarioId}.png`);
          
          if (GENERATE) {
            await page.screenshot({ path: screenshotPath });
            results.push(`[GEN] ${scenarioId} générée`);
          } else {
            if (!fs.existsSync(screenshotPath)) {
              throw new Error(`Baseline manquante`);
            }
            
            const img1 = PNG.sync.read(fs.readFileSync(screenshotPath));
            const img2 = PNG.sync.read(await page.screenshot());
            const { width, height } = img1;
            const diff = new PNG({ width, height });
            
            const numDiffPixels = pixelmatch(img1.data, img2.data, diff.data, width, height, { threshold: 0.1 });
            const diffPercent = (numDiffPixels / (width * height)) * 100;
            
            if (diffPercent > SEUIL) {
              throw new Error(`Écart de ${diffPercent.toFixed(2)}%`);
            }
            results.push(`[OK] ${scenarioId} (${diffPercent.toFixed(2)}%)`);
          }
        } catch (e) {
          console.error(`ERREUR ${scenarioId} : ${e.message}`);
          results.push(`[FAIL] ${scenarioId} : ${e.message}`);
          failed = true;
        }
        await page.close();
      }
    }
  }

  await browser.close();
  console.log("\n--- RÉSULTATS VISUELS v6.17.5 ---");
  console.log(results.join('\n'));
  process.exit(failed && !GENERATE ? 1 : 0);
})();
