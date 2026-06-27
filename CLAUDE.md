# CLAUDE.md

Guidance for working in this repository (`selli/laravel-gdpr-consent-database`).

## What this is

An enterprise-grade Laravel package for GDPR consent management: typed & versioned consents, an immutable
audit trail, expiration/renewal, guest (cookie-based) consents, erasure (anonymisation), domain events and a
configurable cookie banner.

- **Requirements:** PHP `^8.2`, Laravel 11/12 (`illuminate/contracts ^11||^12`).
- **Docs site:** https://laravel-gdpr-consent.selli.io (docmd, hosted on Cloudflare, rebuilt from `main`).

## Quality gates (all must pass)

```bash
composer test          # Pest test suite
composer test-coverage # coverage (target >= 90%)
composer analyse       # PHPStan level max + Larastan (no baseline)
composer format        # Laravel Pint
composer mutate        # mutation testing (Pest --mutate), informational
```

## Public docs site — `docs/` (docmd, consumer-facing)

Built with **docmd** (`@mgks/docmd`). Lives at the **repo root**: root `package.json`
(`docs:dev` / `docs:build` = `docmd dev` / `docmd build`) + root `docmd.config.json` (`src: docs`,
`out: site`). The build output `site/` is gitignored.

```bash
npm install                  # installs @mgks/docmd (+ @docmd/* engine)
npm run docs:dev             # preview docs locally
npm run docs:build           # build the docs site into ./site
npx @mgks/docmd validate     # check docs internal links/anchors (must pass)
```

Conventions: add every new page to the nav in `docmd.config.json`; keep docs junior-proof; assets live in
`docs/assets/` and are served at `/assets/...`. After doc changes run `npm run docs:build` then
`npx @mgks/docmd validate` — both must pass.

### Cloudflare provisioning (same setup as `laravel-llm-warden`)

The docs site is hosted on **Cloudflare Pages**, connected to this GitHub repo (Git integration), and
**rebuilt automatically from `main`**. There is intentionally **no** `wrangler.toml` or deploy workflow in
the repo — the provisioning is the Cloudflare Pages project configuration:

| Cloudflare Pages setting | Value |
|---|---|
| Production branch | `main` |
| Framework preset | None |
| Build command | `npm run docs:build` |
| Build output directory | `site` |
| Root directory | `/` (repo root) |
| Custom domain | `laravel-gdpr-consent.selli.io` |

To set it up: Cloudflare Dashboard → Workers & Pages → Create → Pages → Connect to Git → select this repo →
apply the settings above → add the custom domain. Every push to `main` then triggers a rebuild and deploy.

## Internal working docs (gitignored, not shipped)

- `PLAN.md` — implementation tracker.
- `DEVELOPMENT_NOTES.md` — technical decision log.
- `ANALISI_E_ROADMAP_ENTERPRISE.md` — the original analysis/roadmap (tracked, but `export-ignore`d from the
  Composer dist).
