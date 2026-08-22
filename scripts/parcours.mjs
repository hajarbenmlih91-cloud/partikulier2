import { chromium } from 'playwright';

const BASE = process.env.PK_BASE || 'http://localhost:8092';
const nav = await chromium.launch();
const page = await nav.newPage();

async function check(url, label) {
    const fullUrl = BASE + '/index.php' + url;
    const r = await page.goto(fullUrl, { waitUntil: 'networkidle' });
    if (r.status() === 200) {
        console.log(`[PASS] ${label} (${fullUrl})`);
    } else {
        console.log(`[FAIL] ${label} (${fullUrl}) - HTTP ${r.status()}`);
        process.exit(1);
    }
}

try {
    await check('/fr/', 'Accueil FR');
    await check('/en/', 'Accueil EN');
    await check('/ar/', 'Accueil AR');
    await check('/fr/annonces/', 'Archive FR');
    console.log('\nOK - Parcours E2E Senior validé.');
} catch (e) {
    console.error(e);
    process.exit(1);
} finally {
    await nav.close();
}
