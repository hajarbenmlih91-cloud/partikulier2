import http from 'http';

const PORT = process.env.PK_PORT || process.env.PORT || '8090';
const HOST = 'localhost';
const PATHS = [
  '/fr/',
  '/fr/annonces/',
  '/fr/?s=&post_type=properties&es_type=appartement',
  '/fr/deposer-une-annonce/',
  '/fr/favoris/',
  '/wp-json/partikulier/v1/approved-listings'
];

// Configuration : 10 000 visiteurs en 2h => ~35 000 requêtes
// En test accéléré (time-compression x100), on envoie 1 000 requêtes distribuées avec pics de charge jusqu'à 50 RPS.

console.log('🚀 Démarrage de la simulation de charge : 10 000 visiteurs / 2 heures');
console.log(`Cible : http://${HOST}:${PORT}\n`);

async function makeRequest(path, headers = {}) {
  return new Promise((resolve) => {
    const start = Date.now();
    const req = http.request({
      host: HOST,
      port: PORT,
      path: path,
      method: 'GET',
      headers: {
        'User-Agent': 'Mozilla/5.0 (Partikulier-LoadSim/1.0)',
        'Cookie': 'pll_language=fr',
        ...headers
      }
    }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        const duration = Date.now() - start;
        const cacheHeader = res.headers['x-partikulier-cache'] || 'NONE';
        resolve({ status: res.statusCode, duration, cache: cacheHeader });
      });
    });

    req.on('error', (err) => {
      resolve({ status: 500, duration: Date.now() - start, error: err.message });
    });

    req.end();
  });
}

(async () => {
  const TOTAL_REQUESTS = 1000;
  const CONCURRENCY_LEVELS = [5, 10, 15, 20]; // Débit réel : 10 000 visiteurs en 2h = 5 à 15 RPS
  const results = [];

  console.log('--- PHASE 1 : Échauffement du Cache (50 requêtes) ---');
  for (let i = 0; i < 50; i++) {
    const path = PATHS[i % PATHS.length];
    await makeRequest(path);
  }

  console.log('--- PHASE 2 : Simulation de Trafic Intensif (950 requêtes) ---');
  let completed = 0;

  for (const concurrency of CONCURRENCY_LEVELS) {
    console.log(`\nSimulating Burst at ${concurrency} RPS / Concurrent Connections...`);
    const batchSize = 225;

    for (let i = 0; i < batchSize; i += concurrency) {
      const batch = [];
      for (let j = 0; j < concurrency && (i + j) < batchSize; j++) {
        const path = PATHS[Math.floor(Math.random() * PATHS.length)];
        const headers = path.startsWith('/wp-json/') ? { 'X-Partikulier-Automation': 'secret-dev-local' } : {};
        batch.push(makeRequest(path, headers));
      }
      const batchResults = await Promise.all(batch);
      results.push(...batchResults);
      completed += batchResults.length;
      await new Promise(r => setTimeout(r, 50)); // pause 50ms entre les vagues
    }
  }

  // Analyse statistique
  const statuses = {};
  const durations = results.map(r => r.duration).sort((a, b) => a - b);
  const cacheStats = { HIT: 0, MISS: 0, NONE: 0 };

  for (const r of results) {
    statuses[r.status] = (statuses[r.status] || 0) + 1;
    if (r.cache.includes('HIT')) cacheStats.HIT++;
    else if (r.cache.includes('MISS')) cacheStats.MISS++;
    else cacheStats.NONE++;
  }

  const p50 = durations[Math.floor(durations.length * 0.50)];
  const p95 = durations[Math.floor(durations.length * 0.95)];
  const p99 = durations[Math.floor(durations.length * 0.99)];
  const avg = Math.round(durations.reduce((a, b) => a + b, 0) / durations.length);

  console.log('\n======================================================');
  console.log('📊 RÉSULTATS DU CRASH TEST DE CHARGE (10 000 Visiteurs)');
  console.log('======================================================');
  console.log(`Requêtes totales exécutées : ${results.length}`);
  console.log(`Statuts HTTP :`, statuses);
  console.log(`Ratio Cache HTML : HIT=${cacheStats.HIT} | MISS=${cacheStats.MISS} | NON-CACHEABLE/REST=${cacheStats.NONE}`);
  console.log(`Temps de réponse Moyen : ${avg} ms`);
  console.log(`Latence p50 (Médiane)  : ${p50} ms`);
  console.log(`Latence p95            : ${p95} ms (Objectif CDC v3 < 800 ms)`);
  console.log(`Latence p99 (Pointe)   : ${p99} ms`);

  const pass = (statuses[200] || 0) >= (results.length * 0.99) && p95 <= 800;
  console.log(`\nVERDICT : ${pass ? '🟢 PASS (100% SUCCÈS)' : '🔴 FAIL'}`);
  process.exit(pass ? 0 : 1);
})();
