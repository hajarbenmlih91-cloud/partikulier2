# Plafond de capacité — v6.17.22 (publié, non simulé)

Source : artefact officiel du run `33136743370`/`33140371484`, lu dans
`documentation/evidence/ci-…/cold-acceptance-v1.7.1.tar.gz` → `capacity-envelope-v6.17.22.json`.

| Sonde | Valeur mesurée | Statut |
|---|---|---|
| `saturation_probe_50rps` | p95 **2,84610565 s**, p99 **2,9251385799 s**, CPU **87,403 %**, 500 requêtes, **0 erreur**, 13 échantillons, 4 vCPU | **FAIL** au sens de la gate (sonde non jugée) |
| `concurrent_sessions_50` | CPU **87,675 %**, **2 échantillons**, jugé sur `session_errors == 0` | PASS, mais sous-échantillonné |
| `load-test` HTTP | p95 ≤ 1,5 s exigé par la gate | PASS |

**Plafond déclaré : ~50 requêtes/seconde soutenu** sur 4 vCPU, avec la réserve des
13 échantillons. La gate `ci-cold-acceptance-v1.7.1.sh:156` ne juge que 4 phases
nommées (`sustained_read_10rps`, `burst_read_25rps`, `write_api_2rps`,
`concurrent_sessions_50`) et **ignore** `saturation_probe` : c'est pour cela qu'un
global `PASS` coexiste avec une sonde `FAIL`.

**Seuil de déclenchement d'une gate stricte** (décision client, pas une suggestion) :
> 50 000 visites/jour ou 150 000 visites/heure.

Comparaison avec le besoin réel : 5 000 visites/jour ≈ 0,06 req/s en moyenne ;
10 000 visites concentrées en 2 h ≈ 1,4 req/s ; pire minute ≈ 14 req/s. Marge
d'environ **35×** avant le plafond. Aucune optimisation de capacité n'est donc
justifiée avant le seuil ci-dessus — et le cache de pages (porte G1) est, lui,
encore hors service, ce qui est le seul point de performance à traiter maintenant.
