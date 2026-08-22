// Parcours E2E arabe obligatoire du CdC 6.17.
process.env.PK_ONLY_LANG = 'ar';
process.env.PK_REPORT = process.env.PK_REPORT || '/tmp/partikulier-6.17-journey-ar.json';
await import('./test-i18n-journey.mjs');
