# Install fictional theme demo content safely

For existing content, use private theme Preview first. For sample pages, use a disposable local site and run `php artisan capell:theme-agency-demo --sites="Disposable demo" --url="https://demo.test"`. Review the target name, URL and languages before running it.

An unscoped run is refused when any site already exists, even with `--force`; an empty installation can create its first Demo site. A named existing target receives demo pages while retaining its theme, enabled state, site translations and domains. Scoped setup writes fictional demo page content, widgets and demo layouts, including on repeat runs; keep authored work outside those demo pages. It can also adopt the site recovery page, so prefer a new disposable target.

Only combine an explicit `--sites` target with `--force` after reviewing the overwrite: it selects this theme, enables the site, replaces the requested site translations and language domains using `--url`, and reseeds demo content. Omitted `--languages` uses the default language; other translations and domains remain. Repeating a scoped install reuses demo page identities; it is not an undo operation. Back up any authored data before deliberately overwriting it.

Foundation retains its existing singular options: use `--site="Disposable demo" --language=en --base-url="https://demo.test"` with `capell:theme-foundation-demo`; repeat `--site` and `--language` for additional targets and languages. Other theme commands accept comma-separated `--sites` and `--languages` with `--url`.

The example uses Agency. For another theme, use its demo command from that package README with the same explicitly reviewed options. Foundation supplies the shared installer to all theme demo actions. CLI automation should treat refusal as a failed command and supply a target; do not add `--force` to bypass the scope requirement.

The initial scope check runs before theme, language, site or content writes. It prevents accidental unscoped changes; it is not a transaction or a rollback system. A later content failure can leave a scoped target partly seeded, so review and repeat only on a disposable site.

When installing several named targets together, domain paths include each site's name from the first run, after any language prefix. Repeating the same reviewed target, language and URL options keeps those domain paths stable.

The optional `--profile` flag changes the shared theme's active preset. That can affect other sites already using that theme even when their site configuration is preserved; use private Preview or an isolated installation when evaluating a preset against existing public content.
