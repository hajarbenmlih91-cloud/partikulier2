// Parcours E2E anglais obligatoire du CdC 6.17.
process.env.PK_ONLY_LANG = 'en';
process.env.PK_REPORT = process.env.PK_REPORT || '/tmp/partikulier-6.17-journey-en.json';
await import('./test-i18n-journey.mjs');
