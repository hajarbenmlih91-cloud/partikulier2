import { chromium } from 'playwright';

const BASE = process.env.PK_BASE || 'http://localhost:8098';
const VERSION = '6.17.8';

const scenarios = [
    { name: 'Accueil FR', path: '/fr/' },
    { name: 'Archive FR', path: '/fr/annonces/' },
    { name: 'Déposer FR', path: '/fr/deposer/' },
    { name: 'Mes Annonces FR', path: '/fr/mes-annonces/' },
    { name: 'Favoris FR', path: '/fr/favoris/' },
    { name: 'Accueil EN', path: '/en/' },
    { name: 'Archive EN', path: '/en/annonces/' },
    { name: 'Déposer EN', path: '/en/deposer-en/' },
    { name: 'Mes Annonces EN', path: '/en/mes-annonces-en/' },
    { name: 'Favoris EN', path: '/en/favoris-en/' },
    { name: 'Accueil AR', path: '/ar/' },
    { name: 'Archive AR', path: '/ar/annonces/' },
    { name: 'Déposer AR', path: '/ar/deposer-ar/' },
    { name: 'Mes Annonces AR', path: '/ar/mes-annonces-ar/' },
    { name: 'Favoris AR', path: '/ar/favoris-ar/' }
];

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();
    let passed = 0;

    console.log(`--- RÉSULTATS E2E SENIOR v${VERSION} ---`);

    for (const s of scenarios) {
        const url = BASE + s.path;
        try {
            const response = await page.goto(url, { waitUntil: 'networkidle' });
            const status = response.status();
            const finalUrl = page.url();
            
            if (status === 200) {
                const isLogin = finalUrl.includes('wp-login.php');
                console.log(`[PASS] ${s.name} (${status})${isLogin ? ' [AUTH_REDIRECT]' : ''}`);
                passed++;
            } else {
                console.log(`[FAIL] ${s.name} (Status: ${status}, URL: ${url})`);
            }
        } catch (e) {
            console.log(`[ERROR] ${s.name}: ${e.message}`);
        }
    }

    await browser.close();
    console.log(`${passed}/${scenarios.length} Scénarios validés`);
    process.exit(passed === scenarios.length ? 0 : 1);
})();
