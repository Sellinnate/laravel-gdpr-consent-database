# Laravel GDPR Consent Database — Analisi tecnica e Roadmap Enterprise

> Documento redatto in qualità di Senior Software Developer.
> Obiettivo: portare `selli/laravel-gdpr-consent-database` da package funzionale ma acerbo a un
> **package Composer enterprise-grade** per costruire applicazioni Laravel pienamente conformi al GDPR,
> con **suite di test ≥ 90% di coverage** reale e significativa.
>
> Data analisi: 2026-06-27 · Branch: `main` · Versione PHP locale: 8.4 · Laravel 11/12 · Pest 3

---

## 1. Executive Summary

Il package fornisce **fondamenta solide e una buona ampiezza funzionale** per la sua giovane età:
modello dei consensi polimorfico, versioning dei consent type, scadenza dei consensi, gestione consensi
guest (sessione/cookie tecnico), banner cookie via Blade directive e un controller con endpoint AJAX.

Tuttavia, allo stato attuale **non è enterprise-ready** e presenta criticità che ne compromettono
affidabilità e — più grave per un prodotto che vende *compliance* — la **correttezza giuridica**.
I tre problemi più seri sono:

1. **Logica di test dentro il codice di produzione.** `HasGdprConsents::getConsentsExpiringWithinDays()`
   contiene un ramo `if (app()->environment('testing'))` che **muta le date di scadenza dei consensi**.
   È un difetto bloccante: il comportamento in produzione diverge da quello testato e il dato legale
   viene alterato.
2. **Suite di test che valida i bug invece della correttezza.** Numerosi test contengono asserzioni
   tautologiche (`expect($x->count())->toBe($x->count())`) e commenti espliciti del tipo
   *"Modifichiamo l'aspettativa per adattarla al comportamento attuale"*. I 41 test passano (159 asserzioni)
   ma offrono **falsa sicurezza**: la coverage di *comportamento corretto* è bassa.
3. **Lacune di conformità GDPR** in un package il cui unico scopo è la conformità: niente audit trail
   immutabile (i record vengono mutati in-place, distruggendo la prova storica del consenso — Art. 7(1)),
   nessun export per il diritto di accesso/portabilità (Artt. 15/20), `onDelete('cascade')` che cancella
   la prova del consenso in conflitto con l'obbligo di dimostrabilità, e il pulsante **"Reject All" che
   non revoca** i consensi opzionali già concessi.

A questo si aggiungono debito architetturale (classi scheletro vuote, nessuna astrazione configurabile,
codebase con commenti in italiano misti a inglese), tooling incompleto (PHPStan referenziato negli script
ma non installato né configurato; nessun coverage gate in CI) e versioning basato su `slug LIKE '...%'`
fragile e non corretto.

La roadmap proposta è organizzata in **6 fasi** (≈ 8–10 settimane/uomo) con quality gate misurabili.

---

## 2. Inventario dello stato attuale

| Area | Componenti | Stato |
|---|---|---|
| **Models** | `ConsentType`, `UserConsent`, `GuestConsent` | Funzionali, ma logica di dominio sparsa e accoppiata |
| **Trait** | `HasGdprConsents` | Cuore del package; contiene il bug di ambiente di test |
| **Service** | `GuestConsentManager` | Sottile wrapper, registrato come singleton |
| **Controller** | `GuestConsentController` | 4 endpoint AJAX; bug logico in `rejectAll` |
| **Migrations** | 6 file (`1_`…`6_`) | Incrementali, naming non-timestamp, no indici mirati |
| **Views** | `cookie-banner.blade.php` | Query Eloquent dentro la view (`ConsentType::cookies()`) |
| **Routes** | `routes/web.php` | Nessun middleware (throttle/web/csrf) esplicito |
| **Command** | `LaravelGdprConsentDatabaseCommand` | Scheletro: signature `laravel-gdpr-consent-database`, "All done" |
| **Facade / Main class** | `LaravelGdprConsentDatabase` | **Classe vuota** + facade inutile (residuo skeleton Spatie) |
| **Factories** | 4 factory | Presenti |
| **Seeder** | `CookieConsentSeeder` | Presente |
| **Tests** | 8 file Feature + Arch + Example | 41 test / 159 asserzioni, **molti tautologici** |
| **CI** | run-tests, pint, changelog, dependabot | Manca coverage gate e analisi statica reale |

Baseline misurato in locale: `vendor/bin/pest` → **41 passed (159 assertions), ~1s**.
Coverage **non misurabile out-of-the-box** (nessun driver configurato nel package né gate in CI); misurata
forzando Xdebug manualmente → **82.4% totale**. Il dato però è ingannevole su due fronti:

| Sorgente | Coverage | Lettura critica |
|---|---|---|
| `Http/Controllers/GuestConsentController` | 100% | ...ma il test di `rejectAll` non verifica la revoca (C2): coverage ≠ correttezza |
| `LaravelGdprConsentDatabase` (classe vuota) | 100% | Coperta solo perché vuota — **codice morto che gonfia il numero** |
| `Models/ConsentType` | 89.7% | createNewVersion edge non coperti |
| `Models/GuestConsent` | 93.8% | — |
| `Services/GuestConsentManager` | 84.6% | — |
| `Traits/HasGdprConsents` | 81.8% | Branch di versioning/renewal scoperti |
| `Models/UserConsent` | **36.0%** | `isCurrentVersion`, `needsRenewal`, `daysUntilExpiration`, scope `expired` **quasi non testati** |
| `Commands/…Command` | **0.0%** | Scheletro mai eseguito |
| `Facades/…` | **0.0%** | Facade inutile mai usata |

Il gap verso il 90% è **concentrato e diagnostico**: il codice di dominio più delicato (`UserConsent` al 36%)
è il meno testato, e i metodi scoperti sono esattamente quelli delle feature che la §3 segnala come non
funzionanti (renewal/versioning). Il codice scheletro (Command/Facade allo 0%) trascina giù il totale: la sua
**rimozione** in Fase 0 alza la baseline senza scrivere un test.

---

## 3. Analisi critica per area

Severità: 🔴 Bloccante · 🟠 Alta · 🟡 Media · 🔵 Bassa/Nice-to-have

### 3.1 Correttezza & Bug

| # | Sev | File / Punto | Problema | Impatto |
|---|-----|--------------|----------|---------|
| C1 | 🔴 | `Traits/HasGdprConsents.php:386-394` | `if (app()->environment('testing'))` muta `expires_at` dei consensi | Codice di test in produzione; comportamento divergente; dato legale alterato |
| C2 | 🔴 | `Http/Controllers/GuestConsentController.php:34-50` (`rejectAll`) | "Reject All" **concede** i required ma **non revoca** gli opzionali già concessi | Violazione UX/legale: l'utente crede di aver rifiutato ma i consensi restano attivi |
| C3 | 🟠 | `Models/ConsentType.php:74-102` (`createNewVersion`) | Parsing versione `explode('.', $version)[1]` assume formato `MAJOR.MINOR` | Crash/Undefined su versioni `"1"`, `"2.0.1"`, semver |
| C4 | 🟠 | `HasGdprConsents.php:63-66,99-101` · `UserConsent.php:119` | Versioning via `slug 'like' $slug.'%'` | Match errati (`terms` ⊂ `terms-and-conditions`), non indicizzabile, SQL fragile |
| C5 | 🟠 | `HasGdprConsents.php:160-181` (`giveConsent`) | `revokeConsent` + `create` senza **transazione** | Stato incoerente su fallimento parziale |
| C6 | 🟠 | `ConsentType.php` modello versioning | Nuova versione = **nuovo slug** (`terms-v1-1`) | Rompe il contratto "slug = identificatore stabile"; tutte le lookup successive diventano LIKE |
| C7 | 🟡 | `migrations/2_…user_consents` | Nessun **unique constraint** su (consentable, consent_type, attivo) | Possibili consensi attivi duplicati |
| C8 | 🟡 | `UserConsent.php:141-148` (`daysUntilExpiration`) | `now()->diffInDays(..., false)` ritorna float in Carbon recenti | Possibile type mismatch sul cast a `?int` |
| C9 | 🟡 | `revokeConsent` (string path) | `firstOrFail()` lancia eccezione se lo slug non esiste, ma il path int no | Comportamento asimmetrico e non documentato |
| C10 | 🔵 | `consentsNeedingRenewal()` | Test stesso asserisce `->toBe(0)` con commento "adattata al comportamento attuale" | La feature **non funziona** come descritto nel README |

### 3.2 Conformità GDPR (il dominio del prodotto)

Questa è l'area più importante: il package vende *compliance*, quindi i gap qui sono difetti di prodotto, non solo tecnici.

| # | Sev | Riferimento normativo | Gap | Azione richiesta |
|---|-----|----------------------|-----|------------------|
| G1 | 🔴 | **Art. 7(1)** — dimostrabilità del consenso | I record vengono **mutati in-place** (`update revoked_at, granted=false`): si perde la storia. Nessun audit log immutabile | Modello **append-only**: ogni azione (grant/revoke/renew) è un nuovo record immutabile; stato corrente derivato |
| G2 | 🔴 | **Art. 17 vs Art. 7(1)** | `onDelete('cascade')` sui `user_consents`: cancellare l'utente distrugge la prova del consenso | Strategia di **anonimizzazione** della prova invece di cancellazione; disaccoppiare prova legale da PII |
| G3 | 🟠 | **Artt. 15 / 20** — accesso & portabilità | Nessun export della storia consensi di un soggetto (JSON/CSV) | Comando/servizio `ConsentExporter` per data subject |
| G4 | 🟠 | **Art. 7** — consenso informato e specifico | Non si registra **il testo/la versione esatta** mostrata all'utente al momento del consenso | Salvare snapshot di `policy_text`/`policy_url` + hash nel record |
| G5 | 🟠 | **ePrivacy / "prior consent"** | Il banner non blocca gli script non essenziali **prima** del consenso; nessun meccanismo di blocco | Documentare/fornire pattern di script-gating; categorie con stato pre-consenso |
| G6 | 🟡 | **Art. 30** — registro trattamenti | Nessun campo per `legal_basis`, `purpose`, `data_controller` | Estendere `consent_types` con metadati di trattamento |
| G7 | 🟡 | **Art. 8** — consenso dei minori | Nessun hook per verifica età / consenso genitoriale | Hook/flag opzionale `requires_age_verification` |
| G8 | 🟡 | Tracciabilità | Nessun **evento** dispatchato (`ConsentGranted`, `ConsentRevoked`, `ConsentExpired`) | Event-driven per audit, notifiche, integrazioni downstream |
| G9 | 🔵 | IP è dato personale | `ip_address` salvato in chiaro senza opzione di anonimizzazione | Opzione config di anonimizzazione/hashing IP |

### 3.3 Architettura & Qualità del codice

| # | Sev | Punto | Problema |
|---|-----|-------|----------|
| A1 | 🟠 | `LaravelGdprConsentDatabase.php`, `Facades/…`, `Commands/…` | Codice **scheletro** residuo (classe vuota, facade inutile, command "All done") |
| A2 | 🟠 | Config | Nessuna possibilità di configurare **model classes**, **nomi tabelle**, prefisso route, middleware → impossibile estendere senza fork |
| A3 | 🟠 | `routes/web.php` | Endpoint senza middleware espliciti (`web`, `throttle`, CSRF): in produzione la sessione/cookie non sono garantiti fuori dal gruppo `web` |
| A4 | 🟡 | Tutto il codebase | **Commenti in italiano** misti a codice inglese; inconsistente per un OSS internazionale |
| A5 | 🟡 | Tutto il codebase | Mancano `declare(strict_types=1)`, return types nativi su molti metodi del trait (solo via docblock) |
| A6 | 🟡 | `cookie-banner.blade.php:34` | `ConsentType::cookies()` esegue **query Eloquent dentro la view** (anti-pattern, non testabile, N+1) |
| A7 | 🟡 | Migrations | Naming `1_`,`2_`… non-timestamp; nessun indice su `expires_at`, `granted`, `slug` per le ricerche LIKE |
| A8 | 🟡 | Dominio | Logica di business sparsa tra trait, modelli e manager: assenza di un **service layer**/contracts |
| A9 | 🔵 | `GuestConsent` usa `HasGdprConsents` | Riuso del trait crea accoppiamento tra modello guest e logica utente; valutare estrazione di un'interfaccia `Consentable` |

### 3.4 Suite di test

| # | Sev | Punto | Problema |
|---|-----|-------|----------|
| T1 | 🔴 | `VersioningAndExpirationTest.php:231,242` | Asserzioni **tautologiche**: `expect($c->count())->toBe($c->count())` — non testano nulla |
| T2 | 🔴 | Vari (`:126,178,193`) | Commenti *"Modifichiamo l'aspettativa per adattarla al comportamento attuale"*: i test sono stati **piegati ai bug** |
| T3 | 🟠 | Globale | Nessun **coverage gate**; coverage reale di comportamento corretto stimata bassa nonostante 41 test verdi |
| T4 | 🟠 | `ExampleTest.php` | Test scheletro placeholder |
| T5 | 🟡 | Globale | Mancano test su: `rejectAll` revoca, `command`, branch del banner, edge `createNewVersion` semver, transazioni, concorrenza |
| T6 | 🟡 | Globale | Nessun **mutation testing** (Infection) per validare la qualità delle asserzioni |

### 3.5 Tooling, CI/CD & DX

| # | Sev | Punto | Problema |
|---|-----|-------|----------|
| D1 | 🟠 | `composer.json` | Script `analyse` → PHPStan, ma **PHPStan/Larastan non sono in `require-dev`** e manca `phpstan.neon` → `composer analyse` fallisce |
| D2 | 🟠 | CI `run-tests.yml` | Nessun **coverage gate** (es. `--min=90`), nessuna matrice con pcov |
| D3 | 🟡 | `composer.json` | `"minimum-stability": "dev"` su un package pubblicato → rischioso |
| D4 | 🟡 | Coerenza vendor | `name: selli/...` + `homepage: github.com/selli/...` ma badge/repo usano `sellinnate/...` |
| D5 | 🟡 | README | Linka `CONTRIBUTING.md` **inesistente**; sezione sicurezza generica |
| D6 | 🔵 | DX | Nessun `infection.json`, nessun workbench reale (`Workbench\App\` dichiarato in autoload-dev ma cartella assente) |

---

## 4. Sintesi prioritaria

```
🔴 BLOCCANTI (da risolvere subito)
   C1  Logica di test in produzione (getConsentsExpiringWithinDays)
   C2  "Reject All" non revoca gli opzionali
   G1  Audit trail immutabile (Art. 7.1)
   G2  Cascade delete distrugge la prova legale (Art. 17 vs 7.1)
   T1  Asserzioni tautologiche
   T2  Test piegati ai bug

🟠 ALTE
   C3 C4 C5 C6  · G3 G4 G5 · A1 A2 A3 · T3 T4 · D1 D2

🟡 MEDIE
   C7 C8 C9 C10 · G6 G7 G8 · A4 A5 A6 A7 A8 · T5 T6 · D3 D4 D5

🔵 BASSE
   G9 · A9 · D6
```

---

## 5. Roadmap di sviluppo (a fasi)

Ogni fase ha **deliverable**, **quality gate** e **criteri di accettazione** misurabili.
Stima totale: **8–10 settimane/uomo**. Le fasi 0–2 sono prerequisito a qualunque rilascio enterprise.

### Fase 0 — Stabilizzazione & infrastruttura qualità (≈ 3–4 gg)
**Obiettivo:** rete di sicurezza prima di toccare il dominio.

- Aggiungere `larastan/larastan` (PHPStan livello max), creare `phpstan.neon`, far passare `composer analyse`.
- Configurare **coverage driver** (pcov in CI) e **coverage gate** in `run-tests.yml`: `pest --coverage --min=90`.
- Aggiungere **Infection** (`infection.json`) per il mutation testing con MSI target iniziale ≥ 70%.
- `declare(strict_types=1)` in tutti i file; abilitare regole Pint più severe.
- Rimuovere codice scheletro morto (A1): `LaravelGdprConsentDatabase` vuota, facade, command placeholder → o rimossi o sostituiti da comandi reali (vedi Fase 3).
- Creare `workbench/` reale, `CONTRIBUTING.md`, correggere coerenza vendor (D4) e `minimum-stability: stable`.

**Quality gate:** CI verde con `pint`, `phpstan` (max), `pest --min=90` configurato (anche se la soglia si raggiunge nelle fasi successive).

### Fase 1 — Bonifica bug critici & test onesti (≈ 1 settimana)
**Obiettivo:** eliminare i bloccanti di correttezza e rendere la suite veritiera.

- **C1**: rimuovere il ramo `app()->environment('testing')`; reimplementare `getConsentsExpiringWithinDays` con logica deterministica basata solo su `expires_at`. Riscrivere i test con `Carbon::setTestNow()` e **asserzioni reali**.
- **C2**: `rejectAll` deve **revocare** tutti i consensi opzionali non-required di categoria cookie, oltre a concedere i required.
- **T1/T2**: eliminare tutte le asserzioni tautologiche; ripristinare le aspettative corrette; rimuovere i commenti "adattata al comportamento attuale". Dove emergono bug reali (es. C10 `consentsNeedingRenewal`), correggere il codice, non il test.
- **C5**: avvolgere `giveConsent`/`renewConsent`/`revokeConsent` in `DB::transaction()`.
- **C8/C9**: normalizzare i tipi di ritorno e l'asimmetria string/int.

**Quality gate:** tutti i bug 🔴/🟠 di §3.1 chiusi con test di regressione che **fallirebbero** sul codice pre-fix.

### Fase 2 — Riprogettazione del modello dati per la compliance (≈ 1.5–2 settimane)
**Obiettivo:** rendere il dato legalmente solido. **Breaking change → bump major.**

- **G1 — Audit trail immutabile:** modello append-only. Ogni `grant/revoke/renew` crea un record immutabile (`action`, `occurred_at`, `actor`, `ip`, `user_agent`, snapshot versione/testo). Lo stato "attivo" diventa una proiezione (ultimo evento per `consentable`+`consent_type`). Mantenere scope `active()` performante con indice dedicato.
- **G2 — Erasure vs prova:** sostituire `onDelete('cascade')` con strategia di **anonimizzazione** (`consentable_id` → token irreversibile) preservando la prova aggregata; fornire comando `gdpr:anonymize-subject`.
- **C4/C6 — Versioning corretto:** introdurre `consent_type_group_id` (o `parent_id`) come **identificatore stabile**; lo slug **non cambia** tra versioni; eliminare ogni `LIKE '...%'`. La versione corrente è una query indicizzata su gruppo + `active`.
- **C3 — Semver:** parser di versione robusto (supporto `MAJOR.MINOR[.PATCH]`), con fallback e validazione.
- **C7 — Unique constraint** a livello DB per impedire doppioni attivi.
- **G4 — Snapshot policy:** colonne `policy_url`, `policy_text_hash` sul record di consenso.
- **G6 — Art. 30:** campi `legal_basis`, `purpose`, `data_controller` su `consent_types`.

**Quality gate:** migrazione di upgrade documentata; test su append-only, anonimizzazione, versioning senza LIKE, unique constraint.

### Fase 3 — Astrazione, configurabilità & developer experience (≈ 1.5 settimane)
**Obiettivo:** estendibilità senza fork — requisito enterprise.

- **A2 — Config completa:** model classes sovrascrivibili, nomi tabelle, prefisso route, middleware, guard, toggle feature.
- **A8 — Service layer + contracts:** `ConsentManager` con interfaccia `ConsentManagerContract`; binding nel container; il trait delega al servizio.
- **A9 — `Consentable` interface** per disaccoppiare User/Guest.
- **G8 — Eventi:** `ConsentGranted`, `ConsentRevoked`, `ConsentRenewed`, `ConsentExpired` (+ listener opzionali).
- **G3 — Diritti dell'interessato:** `ConsentExporter` (JSON/CSV) e comandi Artisan reali (sostituiscono il command scheletro A1):
  - `gdpr:consents:export {subject}`
  - `gdpr:consents:expire` (job/scheduler per scadenze)
  - `gdpr:consents:report` (registro Art. 30)
- **A3 — Routes:** gruppo `web` esplicito + `throttle`; CSRF garantito; rendere registrazione route opzionale via config.

**Quality gate:** un'app demo nel `workbench/` usa solo l'API pubblica/config senza estendere classi interne.

### Fase 4 — Front-end banner & ePrivacy (≈ 1 settimana)
**Obiettivo:** banner conforme e disaccoppiato.

- **A6:** spostare il caricamento dei consent type dal Blade a un **ViewComposer**/controller; view senza query.
- **G5:** documentare e fornire pattern di **script-gating** (blocco cookie non essenziali prima del consenso); categorie con stato pre-consenso; nessun checkbox pre-spuntato per gli opzionali.
- **G9:** opzione di **anonimizzazione IP** configurabile.
- Accessibilità (focus trap, ARIA, keyboard) e test di rendering del banner per ogni branch.

**Quality gate:** test Blade/HTTP su tutti i branch del banner; checklist ePrivacy verificata.

### Fase 5 — Hardening test fino a ≥ 90% & rilascio (≈ 1 settimana)
**Obiettivo:** copertura reale e release.

- Colmare i gap di test (T5): `rejectAll`, comandi, eventi, exporter, anonimizzazione, concorrenza/transazioni, edge semver, guest polymorphic.
- **Coverage ≥ 90%** misurata (pcov) e **gate in CI** attivo (`--min=90`).
- **Infection MSI ≥ 85%** per garantire che i test uccidano i mutanti (qualità, non solo quantità).
- Matrice CI: PHP 8.2/8.3/8.4 × Laravel 11/12, `prefer-lowest`/`prefer-stable`.
- README riscritto (EN), `UPGRADE.md`, `CHANGELOG` aggiornato, security policy reale.

**Quality gate finale:** `pint` ✅ · `phpstan` max ✅ · `pest --min=90` ✅ · `infection --min-msi=85` ✅ su tutta la matrice.

---

## 6. Strategia per ≥ 90% di coverage (significativo)

La coverage va **misurata e cancellata di valore se non accompagnata da mutation testing**.
Piano concreto:

1. **Abilitare il driver** (pcov in CI, xdebug/pcov in locale) — oggi assente: prerequisito per qualunque numero.
2. **Coverage gate**: `pest --coverage --min=90` in `run-tests.yml`, build rossa sotto soglia.
3. **Mutation gate**: Infection con `--min-msi=85` per impedire test tautologici (questi sopravvivono ai mutanti e vengono individuati).
4. **Matrice per fonte**:

   | Sorgente | Target | Test chiave |
   |---|---|---|
   | `HasGdprConsents` (trait) | 100% | give/revoke/renew, required, expiring, versioning, transazioni |
   | `ConsentType` | 100% | createNewVersion (semver/edge), isEffective, calculateExpirationDate, scope gruppo |
   | `UserConsent` | 100% | scope active/revoked/expired, isCurrentVersion, needsRenewal, daysUntilExpiration |
   | `GuestConsent` / `GuestConsentManager` | ≥ 95% | findOrCreateForSession, cookie persistente, deleghe |
   | `GuestConsentController` | 100% | acceptAll, **rejectAll (revoca)**, savePreferences, status |
   | Comandi Artisan | ≥ 90% | export, expire, report |
   | Eventi / Exporter | ≥ 90% | dispatch, payload, formati export |
   | Blade banner | ≥ 90% | branch showDetails, required disabled, config override |

5. **Test di regressione first**: ogni bug di §3 chiuso con un test che **fallisce prima** del fix (commit dimostrativo).

---

## 7. Architettura target (enterprise)

```
src/
├── Contracts/            ConsentManagerContract, Consentable, ConsentExporterContract
├── Services/             ConsentManager, GuestConsentManager, ConsentExporter, IpAnonymizer
├── Models/               ConsentType, ConsentRecord (append-only), GuestConsent
├── Events/               ConsentGranted, ConsentRevoked, ConsentRenewed, ConsentExpired
├── Commands/             ExportConsents, ExpireConsents, ConsentReport, AnonymizeSubject
├── Http/Controllers/     ConsentController (config-driven middleware)
├── Http/ViewComposers/   CookieBannerComposer
├── Traits/               HasGdprConsents (delega al ConsentManager)
└── GdprConsentServiceProvider
config/gdpr-consent-database.php   ← models, tabelle, route, middleware, ip_anonymization, features
```

Principi: **single source of truth** nel service layer; modelli sovrascrivibili via config; nessuna query
nelle view; dato legale **immutabile e dimostrabile**; tutto guidato da eventi per integrazioni downstream.

---

## 8. Definizione di "Enterprise-Ready" (Definition of Done)

- [ ] Nessun ramo di ambiente di test nel codice di produzione (C1).
- [ ] Audit trail immutabile e dimostrabile (Art. 7.1) — G1/G2.
- [ ] "Reject All" revoca gli opzionali; banner ePrivacy-compliant — C2/G5.
- [ ] Versioning su identificatore stabile, zero `LIKE` — C4/C6.
- [ ] Operazioni transazionali, unique constraint, indici mirati — C5/C7.
- [ ] Diritti dell'interessato: export & anonimizzazione — G2/G3.
- [ ] Config completa: modelli, tabelle, route, middleware sovrascrivibili — A2.
- [ ] Eventi di dominio dispatchati — G8.
- [ ] PHPStan livello max ✅ · Pint ✅ · `declare(strict_types=1)` ✅.
- [ ] **Coverage ≥ 90%** con gate in CI **+ Infection MSI ≥ 85%**.
- [ ] CI su matrice PHP 8.2–8.4 × Laravel 11–12.
- [ ] Documentazione EN completa: README, UPGRADE, CONTRIBUTING, SECURITY, CHANGELOG.

---

## Appendice A — Riferimenti normativi citati

| Articolo GDPR | Tema | Finding correlato |
|---|---|---|
| Art. 4(11) | Definizione di consenso (libero, specifico, informato, inequivocabile) | G4, G5 |
| Art. 7(1) | Onere della prova / dimostrabilità del consenso | G1, G2, G4 |
| Art. 7(3) | Revoca facile quanto il rilascio | C2 |
| Art. 8 | Consenso dei minori | G7 |
| Art. 15 | Diritto di accesso | G3 |
| Art. 17 | Diritto alla cancellazione | G2 |
| Art. 20 | Portabilità dei dati | G3 |
| Art. 30 | Registro delle attività di trattamento | G6 |
| Direttiva ePrivacy | Prior consent / blocco cookie non essenziali | G5, G9 |

> Nota: questo documento è un'analisi tecnica e non costituisce consulenza legale. Le scelte di compliance
> vanno validate con il DPO/consulente legale del progetto che adotta il package.
