import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';

const THEME_DIR = path.resolve('theme');

console.log('👀 Surveillance active du dossier theme/ ...');
console.log('Chaque modification déclenchera automatiquement bash scripts/sync.sh\n');

let timeout = null;

function sync() {
    try {
        console.log(`[${new Date().toLocaleTimeString()}] Synchronisation vers WordPress...`);
        execSync('bash scripts/sync.sh', { stdio: 'inherit' });
    } catch (e) {
        console.error('Erreur lors de la synchronisation:', e.message);
    }
}

fs.watch(THEME_DIR, { recursive: true }, (eventType, filename) => {
    if (!filename) return;
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => {
        console.log(`Fichier modifié : ${filename}`);
        sync();
    }, 300);
});

// Première synchro au lancement
sync();
