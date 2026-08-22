import { chromium } from 'playwright';
import fs from 'fs';

const BASE_URL = process.env.PK_BASE || 'http://localhost:8097';
const CONTRACT_PATH = '/home/ubuntu/partikulier2/tests/routes-contract.json';

async function run() {
    if (!fs.existsSync(CONTRACT_PATH)) {
        console.error(`Contrat introuvable : ${CONTRACT_PATH}`);
        process.exit(1);
    }
    const contract = JSON.parse(fs.readFileSync(CONTRACT_PATH, 'utf8'));
    const browser = await chromium.launch();
    let passed = 0;
    let total = 0;

    console.log(`════════ Recette E2E Senior v${contract.version} ════════`);
    console.log(`Cible : ${BASE_URL}\n`);

    for (const scenario of contract.scenarios) {
        total++;
        const context = await browser.newContext({
            viewport: { width: 1280, height: 720 }
        });
        const page = await context.newPage();
        const url = `${BASE_URL}${scenario.url}`;

        try {
            const response = await page.goto(url, { waitUntil: 'networkidle' });
            const finalUrl = page.url();
            const status = response.status();

            let scenarioOk = true;
            let errors = [];

            if (scenario.expected_code === 302 || scenario.expected_code === 301) {
                if (scenario.expected_location) {
                    if (!finalUrl.endsWith(scenario.expected_location)) {
                        scenarioOk = false;
                        errors.push(`URL finale ${finalUrl} ne finit pas par ${scenario.expected_location}`);
                    }
                } else if (scenario.expected_location_contains) {
                    if (!finalUrl.includes(scenario.expected_location_contains)) {
                        scenarioOk = false;
                        errors.push(`URL finale ${finalUrl} ne contient pas ${scenario.expected_location_contains}`);
                    }
                }
            } else {
                if (status !== scenario.expected_code) {
                    scenarioOk = false;
                    errors.push(`Status ${status} au lieu de ${scenario.expected_code}`);
                }
                if (finalUrl !== url && !finalUrl.startsWith(url + '?')) {
                    scenarioOk = false;
                    errors.push(`Redirection inattendue vers ${finalUrl}`);
                }
            }

            if (scenarioOk && scenario.expected_lang) {
                const lang = await page.getAttribute('html', 'lang');
                if (lang !== scenario.expected_lang) {
                    scenarioOk = false;
                    errors.push(`Langue ${lang} au lieu de ${scenario.expected_lang}`);
                }
            }

            if (scenarioOk && scenario.expected_dir) {
                const dir = await page.getAttribute('html', 'dir');
                if ((dir || 'ltr') !== scenario.expected_dir) {
                    scenarioOk = false;
                    errors.push(`Direction ${dir} au lieu de ${scenario.expected_dir}`);
                }
            }

            if (scenarioOk) {
                passed++;
                console.log(`   [PASS] ${scenario.description}`);
            } else {
                console.log(`   [FAIL] ${scenario.description}`);
                errors.forEach(e => console.log(`          - ${e}`));
            }

        } catch (e) {
            console.log(`   [ERROR] ${scenario.description} : ${e.message}`);
        } finally {
            await context.close();
        }
    }

    await browser.close();
    console.log(`\nRésultat : ${passed}/${total} PASS`);
    process.exit(passed === total ? 0 : 1);
}

run();
