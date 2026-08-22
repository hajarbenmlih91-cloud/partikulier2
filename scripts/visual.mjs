import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';

const BASE_URL = process.env.PK_BASE || 'http://localhost:8095';
const VERSION = '6.17.6';
const BASELINE_DIR = path.join(process.cwd(), 'tests', `baselines-${VERSION}`);
const CURRENT_DIR = path.join(process.cwd(), 'tests', 'current');
const DIFF_DIR = path.join(process.cwd(), 'tests', 'diff');
const SEUIL = 0.5;

const scenarios = [
    { name: 'fr-accueil', url: '/fr/' },
    { name: 'en-accueil', url: '/en/' },
    { name: 'ar-accueil', url: '/ar/' },
    { name: 'fr-annonces', url: '/fr/annonces/' },
    { name: 'en-annonces', url: '/en/annonces/' },
    { name: 'ar-annonces', url: '/ar/annonces/' },
    { name: 'fr-deposer', url: '/fr/deposer/' },
    { name: 'en-deposer', url: '/en/deposer-en/' },
    { name: 'ar-deposer', url: '/ar/deposer-ar/' },
    { name: 'fr-mes-annonces', url: '/fr/mes-annonces/' },
    { name: 'en-mes-annonces', url: '/en/mes-annonces-en/' },
    { name: 'ar-mes-annonces', url: '/ar/mes-annonces-ar/' },
    { name: 'fr-favoris', url: '/fr/favoris/' },
    { name: 'en-favoris', url: '/en/favoris-en/' },
    { name: 'ar-favoris', url: '/ar/favoris-ar/' }
];

const viewports = [
    { name: 'desktop', width: 1280, height: 800 },
    { name: 'mobile', width: 375, height: 667 }
];

async function run() {
    if (!fs.existsSync(BASELINE_DIR)) fs.mkdirSync(BASELINE_DIR, { recursive: true });
    if (!fs.existsSync(CURRENT_DIR)) fs.mkdirSync(CURRENT_DIR, { recursive: true });
    if (!fs.existsSync(DIFF_DIR)) fs.mkdirSync(DIFF_DIR, { recursive: true });

    const browser = await chromium.launch();
    const results = [];

    for (const scenario of scenarios) {
        for (const vp of viewports) {
            const page = await browser.newPage({ viewport: vp });
            const testName = `${scenario.name}-${vp.name}`;
            const url = BASE_URL + scenario.url;
            
            console.log(`Test: ${testName} -> ${url}`);
            
            try {
                const response = await page.goto(url, { waitUntil: 'networkidle' });
                const status = response.status();
                
                // Vérification RTL
                const isAr = scenario.name.startsWith('ar');
                const dir = await page.getAttribute('html', 'dir');
                const rtlOk = isAr ? dir === 'rtl' : dir !== 'rtl';

                const screenshotPath = path.join(process.env.PK_GENERATE ? BASELINE_DIR : CURRENT_DIR, `${testName}.png`);
                await page.screenshot({ path: screenshotPath, fullPage: true });

                if (process.env.PK_GENERATE) {
                    results.push({ name: testName, status: 'GENERATED', rtl: rtlOk });
                } else {
                    const baselinePath = path.join(BASELINE_DIR, `${testName}.png`);
                    if (!fs.existsSync(baselinePath)) {
                        results.push({ name: testName, status: 'NO_BASELINE', rtl: rtlOk });
                        continue;
                    }

                    const img1 = PNG.sync.read(fs.readFileSync(baselinePath));
                    const img2 = PNG.sync.read(fs.readFileSync(screenshotPath));
                    const { width, height } = img1;
                    const diff = new PNG({ width, height });

                    const numDiffPixels = pixelmatch(img1.data, img2.data, diff.data, width, height, { threshold: 0.1 });
                    const diffPercent = (numDiffPixels / (width * height)) * 100;
                    
                    if (diffPercent > 0) {
                        fs.writeFileSync(path.join(DIFF_DIR, `${testName}.png`), PNG.sync.write(diff));
                    }

                    results.push({ 
                        name: testName, 
                        status: status === 200 && rtlOk && diffPercent <= SEUIL ? 'OK' : 'FAIL',
                        http: status,
                        rtl: rtlOk,
                        diff: diffPercent.toFixed(2) + '%'
                    });
                }
            } catch (e) {
                results.push({ name: testName, status: 'ERROR', error: e.message });
            }
            await page.close();
        }
    }

    await browser.close();
    console.table(results);
    
    const failures = results.filter(r => r.status !== 'OK' && r.status !== 'GENERATED');
    if (failures.length > 0) {
        console.log(`${failures.length} tests ont échoué.`);
        process.exit(1);
    }
}

run();
