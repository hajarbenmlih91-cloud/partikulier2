import { chromium } from 'playwright';

const BASE = process.env.PK_BASE || 'http://localhost:8092';
const LANGUAGES = ['/fr', '/en', '/ar'];

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  const results = [];
  let failed = false;

  const SCENARIOS = [
    { name: 'Accueil', path: '/' },
    { name: 'Archive', path: '/annonces/' },
    { name: 'Déposer', path: '/deposer-PAGE/' },
    { name: 'Mes Annonces', path: '/mes-annonces-PAGE/' },
    { name: 'Favoris', path: '/favoris-PAGE/' }
  ];

  try {
    for (const langPrefix of LANGUAGES) {
      console.log(`Test du parcours ${langPrefix}...`);
      const lang = langPrefix.replace('/', '');
      
      for (const s of SCENARIOS) {
        let pathPart = s.path;
        if (pathPart.includes('-PAGE/')) {
            const baseSlug = pathPart.replace('-PAGE/', '');
            pathPart = baseSlug + (lang === 'fr' ? '/' : `-${lang}/`);
        }
        
        const url = BASE + langPrefix + pathPart;
        console.log(`Visite : ${url}`);
        
        const response = await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
        
        if (response.status() !== 200) {
          throw new Error(`HTTP ${response.status()} sur ${url}`);
        }
        
        results.push(`[PASS] ${langPrefix} - ${s.name}`);

        if (lang === 'ar') {
          const dir = await page.evaluate(() => document.documentElement.dir || document.body.dir || getComputedStyle(document.documentElement).direction);
          if (dir !== 'rtl') throw new Error(`RTL manquant sur ${url} (${dir})`);
        }
      }
    }

    results.push("[PASS] 15/15 Scénarios validés");
    results.push("[PASS] Invariants RTL confirmés");

  } catch (e) {
    console.error(`ERREUR : ${e.message}`);
    failed = true;
  }

  await browser.close();
  console.log("\n--- RÉSULTATS E2E SENIOR v6.17.7 ---");
  console.log(results.join('\n'));
  process.exit(failed ? 1 : 0);
})();
