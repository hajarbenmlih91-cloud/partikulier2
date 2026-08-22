import { chromium } from 'playwright';

const BASE = process.env.PK_BASE || 'http://localhost:8092';
const LANGUAGES = ['/fr', '/en', '/ar'];

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  const results = [];
  let failed = false;

  const PAGES_TO_TEST = [
    { name: 'Accueil', path: '/' },
    { name: 'Archive Annonces', path: '/annonces/' },
    { name: 'Déposer', path: '/deposer/' },
    { name: 'Mes Annonces', path: '/mes-annonces/' },
    { name: 'Favoris', path: '/favoris/' }
  ];

  try {
    for (const lang of LANGUAGES) {
      console.log(`Test du parcours ${lang}...`);
      
      for (const p of PAGES_TO_TEST) {
        // Déterminer le slug traduit (Polylang par défaut ajoute le slug de langue)
        let fullUrl = BASE + lang + p.path;
        
        // Cas particuliers pour les slugs traduits si nécessaire
        if (lang === '/en' && p.path === '/annonces/') fullUrl = BASE + '/en/annonces-en/';
        if (lang === '/ar' && p.path === '/annonces/') fullUrl = BASE + '/ar/annonces-ar/';

        const response = await page.goto(fullUrl, { waitUntil: 'networkidle', timeout: 30000 });
        if (response.status() !== 200) {
          throw new Error(`HTTP ${response.status()} sur ${fullUrl}`);
        }
        
        results.push(`[PASS] ${lang} - ${p.name} (${response.status()})`);

        if (lang === '/ar') {
          const dir = await page.evaluate(() => document.documentElement.dir);
          if (dir !== 'rtl') throw new Error(`RTL manquant sur ${fullUrl}`);
        }
      }
    }

    results.push("[PASS] Invariants RTL validés");

  } catch (e) {
    console.error(`ERREUR : ${e.message}`);
    failed = true;
  }

  await browser.close();
  console.log("\n--- RÉSULTATS E2E SENIOR ---");
  console.log(results.join('\n'));
  process.exit(failed ? 1 : 0);
})();
