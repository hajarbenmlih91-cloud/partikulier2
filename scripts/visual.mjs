import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';

const BASE = process.env.PK_BASE || 'http://localhost:8092';
const DIR = process.env.PK_BASELINE_DIR || path.join(process.cwd(), 'tests', 'baselines-6.17.3');
const MODE = process.argv[2] === 'baseline' ? 'baseline' : 'check';
const SEUIL = 0.5;
const TAILLES = [['desktop', 1280, 800], ['mobile', 375, 667]];

// Mapping exact des URLs pour le test
const SCENARIOS = [
  { id: 'fr-accueil', url: '/fr/' },
  { id: 'fr-annonces', url: '/fr/annonces/' },
  { id: 'fr-deposer', url: '/fr/deposer/' },
  { id: 'fr-mes-annonces', url: '/fr/mes-annonces/' },
  { id: 'fr-404', url: '/fr/404-not-found' },
  { id: 'en-accueil', url: '/en/' },
  { id: 'en-annonces', url: '/en/annonces-en/' },
  { id: 'en-deposer', url: '/en/deposer-une-annonce-en/' },
  { id: 'en-mes-annonces', url: '/en/mes-annonces-en/' },
  { id: 'en-404', url: '/en/404-not-found' },
  { id: 'ar-accueil', url: '/ar/' },
  { id: 'ar-annonces', url: '/ar/annonces-ar/' },
  { id: 'ar-deposer', url: '/ar/deposer-une-annonce-ar/' },
  { id: 'ar-mes-annonces', url: '/ar/mes-annonces-ar/' },
  { id: 'ar-404', url: '/ar/404-not-found' }
];

function diff(img1, img2) {
  const { width, height } = img1;
  const out = new PNG({ width, height });
  const count = pixelmatch(img1.data, img2.data, out.data, width, height, { threshold: 0.1 });
  return (count / (width * height)) * 100;
}

(async () => {
  const nav = await chromium.launch();
  const erreurs = [];
  const resultats = [];

  if (!fs.existsSync(DIR)) fs.mkdirSync(DIR, { recursive: true });

  for (const [tname, w, h] of TAILLES) {
    const context = await nav.newContext({ viewport: { width: w, height: h }, isMobile: tname === 'mobile' });
    const page = await context.newPage();

    for (const sc of SCENARIOS) {
      const cle = `${sc.id}-${tname}`;
      const fullUrl = sc.url;

      try {
        let rep = await page.goto(BASE + fullUrl, { waitUntil: 'networkidle', timeout: 30000 });
        await page.addStyleTag({ content: `*,*::before,*::after{animation:none!important;transition:none!important;scroll-behavior:auto!important;caret-color:transparent!important}` });

        if (!fullUrl.includes('404') && rep.status() !== 200) {
          erreurs.push(`${cle} : HTTP ${rep.status()} sur ${fullUrl}`);
          continue;
        }

        if (sc.id.startsWith('ar')) {
          const dir = await page.evaluate(() => document.documentElement.dir);
          if (dir !== 'rtl') erreurs.push(`${cle} : RTL non détecté`);
        }

        const shot = await page.screenshot({ fullPage: true });
        const lang = sc.id.split('-')[0];
        const refDir = path.join(DIR, lang);
        if (!fs.existsSync(refDir)) fs.mkdirSync(refDir, { recursive: true });
        const refPath = path.join(refDir, `${sc.id.split('-').slice(1).join('-')}-${tname}.png`);

        if (MODE === 'baseline') {
          fs.writeFileSync(refPath, shot);
          resultats.push(`  reference  ${cle}`);
        } else {
          if (!fs.existsSync(refPath)) {
            erreurs.push(`${cle} : baseline manquante`);
          } else {
            const img1 = PNG.sync.read(fs.readFileSync(refPath));
            const img2 = PNG.sync.read(shot);
            if (img1.width !== img2.width || img1.height !== img2.height) {
              erreurs.push(`${cle} : dimensions différentes`);
            } else {
              const d = diff(img1, img2);
              const ok = d <= SEUIL;
              resultats.push(`  ${ok ? 'ok  ' : 'ECART'}  ${cle}  ${d.toFixed(2)}%`);
              if (!ok) erreurs.push(`${cle} : écart de ${d.toFixed(2)}%`);
            }
          }
        }
      } catch (e) {
        erreurs.push(`${cle} : Erreur - ${e.message}`);
      }
    }
    await context.close();
  }

  await nav.close();
  console.log(resultats.join('\n'));
  if (erreurs.length) {
    console.log('\nECHEC :');
    erreurs.forEach(e => console.log('  - ' + e));
    process.exit(1);
  }
  console.log('\nOK - Certification visuelle validée.');
})();
