/**
 * Verification rapide : 4 vues seulement, pour trier un grand nombre de
 * candidats sans payer le cout du harnais complet. La validation finale
 * reste tests/visual.mjs.
 *
 * Sortie : code 0 si conforme, 1 sinon.
 */
import { chromium } from 'playwright';
import { PNG } from 'pngjs';
import fs from 'fs';
import path from 'path';

const BASE = process.env.PK_BASE || 'http://localhost:8090';
const MODE = process.argv[2] || 'check';
const DIR = path.join(process.cwd(), 'tests', '__screens__', 'rapide');
const SEUIL = 0.1;

const VUES = [
  ['accueil', '/', 1440, 1000],
  ['deposer', '/deposer-une-annonce/', 1440, 1000],
  ['annonces', '/property/', 1440, 1000],
  ['accueil-m', '/', 390, 844],
];

fs.mkdirSync(DIR, { recursive: true });

function diff(a, b) {
  if (a.width !== b.width || a.height !== b.height) return 100;
  let n = 0;
  for (let i = 0; i < a.data.length; i += 4) {
    if (Math.abs(a.data[i] - b.data[i])
      + Math.abs(a.data[i + 1] - b.data[i + 1])
      + Math.abs(a.data[i + 2] - b.data[i + 2]) > 30) n++;
  }
  return (n / (a.width * a.height)) * 100;
}

const nav = await chromium.launch();
let ko = 0;
const lignes = [];

for (const [nom, url, w, h] of VUES) {
  const page = await nav.newPage({ viewport: { width: w, height: h }, isMobile: h === 844 });
  await page.goto(BASE + url, { waitUntil: 'networkidle' });
  const shot = await page.screenshot({ fullPage: true });
  const ref = path.join(DIR, nom + '.png');

  if (MODE === 'baseline') {
    fs.writeFileSync(ref, shot);
  } else if (!fs.existsSync(ref)) {
    lignes.push(`${nom}: reference absente`); ko++;
  } else {
    const d = diff(PNG.sync.read(fs.readFileSync(ref)), PNG.sync.read(shot));
    if (d > SEUIL) { lignes.push(`${nom}: ${d.toFixed(2)}%`); ko++; }
  }
  await page.close();
}

await nav.close();
if (MODE === 'baseline') { console.log('references rapides enregistrees'); process.exit(0); }
if (ko) { console.log('ECART ' + lignes.join(' | ')); process.exit(1); }
console.log('ok');
