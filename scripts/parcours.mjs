import { chromium, expect } from '@playwright/test';

const BASE = process.env.PK_BASE || 'http://localhost:8092';
const LANGUAGES = ['/fr', '/en', '/ar'];

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  const results = [];
  let failed = false;

  try {
    for (const lang of LANGUAGES) {
      console.log(`Test du parcours ${lang}...`);
      
      // 1. Accueil
      await page.goto(BASE + lang + '/');
      const title = await page.title();
      if (!title) throw new Error(`Titre manquant sur l'accueil ${lang}`);
      results.push(`[PASS] ${lang} - Accueil`);

      // 2. Recherche
      await page.goto(BASE + lang + '/annonces/');
      const searchInput = await page.locator('input[type="search"]');
      if (await searchInput.count() === 0) throw new Error(`Barre de recherche manquante sur ${lang}`);
      results.push(`[PASS] ${lang} - Recherche`);

      // 3. Invariants RTL pour l'arabe
      if (lang === '/ar') {
        const dir = await page.evaluate(() => document.documentElement.dir);
        if (dir !== 'rtl') throw new Error("RTL manquant sur la version arabe");
        results.push("[PASS] ar - Invariant RTL");
      }
    }

    // 4. Test de soumission (partiel - vérification de la présence du formulaire)
    await page.goto(BASE + '/fr/deposer/');
    const form = await page.locator('#pk-submit-form');
    if (await form.count() === 0) throw new Error("Formulaire de dépôt manquant");
    results.push("[PASS] fr - Formulaire dépôt");

  } catch (e) {
    console.error(`ERREUR : ${e.message}`);
    failed = true;
  }

  await browser.close();
  console.log("\n--- RÉSULTATS E2E ---");
  console.log(results.join('\n'));
  process.exit(failed ? 1 : 0);
})();
