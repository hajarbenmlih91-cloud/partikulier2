import { chromium } from 'playwright';
import fs from 'fs';

const BASE = process.env.PK_BASE || 'http://localhost:8092';
const res = [];
const note = (nom, ok, msg = '') => res.push({ nom, ok, msg });

const nav = await chromium.launch({ headless: true });

try {
  const ctx = await nav.newContext();
  const page = await ctx.newPage();
  
  // 1. Depot
  await page.goto(BASE + '/deposer-une-annonce/', { waitUntil: 'networkidle' });
  const champs = await page.locator('form input, form select, form textarea').count();
  note('Formulaire de depot servi', champs > 20, `${champs} champs`);
  
  const requis = await page.locator('form [required]').count();
  note('Champs obligatoires declares', requis > 10, `${requis} requis`);
  
  await page.click('button[type="submit"]');
  await page.waitForTimeout(500);
  const erreur = await page.locator('.es-field--error, :invalid').count();
  note('Soumission a vide bloquee', erreur > 0);
  
  await ctx.close();
} catch (e) { note('Erreur globale depot', false, e.message); }

// 2. Recherche
try {
  const ctx = await nav.newContext();
  const page = await ctx.newPage();
  const cas = [
    ['sans filtre', '/annonces/'],
    ['par type', '/annonces/?es_type=appartement'],
    ['par mot-cle', '/annonces/?s=Studio'],
    ['mot-cle inexistant', '/annonces/?s=zzzzintrouvable'],
    ['page 2', '/annonces/page/2/'],
  ];
  for (const [nom, url] of cas) {
    const r = await page.goto(BASE + url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);
    const n = await page.locator('.pk-card-title').count();
    const txt = await page.locator('.pk-archive-toolbar').innerText().catch(() => '');
    const vide = txt.includes('0') || txt.toLowerCase().includes('aucun');
    const ok = r.status() === 200 && (nom === 'mot-cle inexistant' ? vide : n > 0);
    note(`Recherche ${nom}`, ok, `HTTP ${r.status()}, ${n} resultats`);
  }
  await ctx.close();
} catch (e) { note('Erreur globale recherche', false, e.message); }

// 3. Fiche
try {
  const ctx = await nav.newContext();
  const page = await ctx.newPage();
  await page.goto(BASE + '/annonces/', { waitUntil: 'networkidle' });
  const lien = page.locator('.pk-card-title a').first();
  if (await lien.count()) {
    await lien.click();
    await page.waitForLoadState('networkidle');
    const h1 = await page.locator('h1').innerText();
    const prix = await page.locator('[class*=price]').count();
    note('Fiche annonce complete', !!h1 && prix > 0, `${h1.slice(0,20)}...`);
  }
  await ctx.close();
} catch (e) { note('Erreur globale fiche', false, e.message); }

// 4. Clavier
try {
  const ctx = await nav.newContext();
  const page = await ctx.newPage();
  await page.goto(BASE + '/', { waitUntil: 'networkidle' });
  let focusOk = 0;
  for (let i = 0; i < 10; i++) {
    await page.keyboard.press('Tab');
    const hasFocus = await page.evaluate(() => {
      const a = document.activeElement;
      if (!a || a === document.body) return false;
      const s = getComputedStyle(a);
      return s.outlineStyle !== 'none' || s.boxShadow !== 'none';
    });
    if (hasFocus) focusOk++;
  }
  note('Focus clavier visible', focusOk > 5, `${focusOk}/10 elements avec indicateur`);
  await ctx.close();
} catch (e) { note('Erreur globale clavier', false, e.message); }

await nav.close();

console.log(`\n${res.filter(r => r.ok).length}/${res.length} conformes`);
res.forEach(r => console.log(`${r.ok ? 'OK  ' : 'FAIL'}  ${r.nom} ${r.msg ? ' — ' + r.msg : ''}`));
if (res.some(r => !r.ok)) process.exit(1);
