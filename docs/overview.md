# Foundation Theme

<!-- prettier-ignore-start -->

## What this theme gives you

Foundation Theme is the base runtime and visual system that Capell themes build on. It provides shared Blade layouts, public design tokens, media/SVG handling, the Tailwind asset pipeline, and the stable override contract for child themes. On its own it is a neutral starting point; vertical themes extend it with their own design and registered sections.

It also owns DesignSpec v1 and the deterministic Foundation compiler used by Website Generator. The compiler accepts bounded structured design data and produces a validated `capell-theme` Project Build artifact from reviewed package templates; it does not execute generated code or accept model-selected dependencies and paths.

## How to use it

1. Open the theme-management entry for **Foundation Theme**, note the site's current theme, and select only the site you intend to review.
2. Choose **Preview Foundation Theme** to view this theme privately on that site's existing content without changing its selected theme. Check available page areas, sections and the mobile layout; create a page first if there is nothing to preview.
3. When ready, choose **Apply Foundation Theme** for that site. This changes its public design immediately, including already-published pages.
4. Open the Foundation Theme extension settings surface to configure performance and runtime design tokens.
5. Build pages with the shared Layout Builder sections, then preview the public output.
6. Use the same Preview then Apply workflow for a specialised child theme; it continues to use Foundation runtime contracts.

To recover the previous design, reopen the previous theme's management entry, select the same site and choose its **Apply** button. This restores the theme selection; it does not undo content or shared Theme Studio settings. Saving those settings can affect public pages using that theme, so do not treat settings edits as private preview changes.

## What it adds

- The base page layouts every Capell theme uses.
- Extension settings for secondary-image lazy loading, build-policy guidance, light/dark colour tokens, image radius, section spacing, widget gaps, heading scale, motion intensity, and responsive repeatable layouts.
- Shared Blade components, layout areas, media/SVG handling, and cache-safe public rendering for child themes.
- A closed DesignSpec v1 schema, canonical reader, deterministic compiler, and `capell-theme` Project Build artifact handler.
- Setup, demo, theme-validation, theme-catalogue, Tailwind-generation, and theme-scaffolding commands for maintainers.

## Good to know

- This is the base other themes build on; install it before any vertical theme.
- The Foundation settings schema does not include a logo or font picker; configure available runtime tokens through its actual extension settings surface.
- `capell:theme-foundation-demo` installs Foundation demo pages; `capell:theme-foundation-setup` provides the package setup command.
- Activating a vertical theme keeps Foundation's behaviour and changes the design on top of it.

## Child Theme Override Contract

Foundation Theme owns the stable child theme override surface for Capell themes. Child themes should declare `extends: 'default'` and override documented sections, views, tokens, and chrome areas instead of replacing the whole public rendering path.

Stable contract points:

- Theme Studio sections: `navigation`, `hero`, `features`, `proof`, `content-listing`, `search`, `pagination`, `form`, `contact-split`, `cta`, `footer`.
- Shared views: `capell::theme.page`, `capell::layout.area`, `capell::media.svg`.
- Runtime tokens: `--foundation-page-bg`, `--foundation-section-spacing`, `--foundation-widget-gap`.
- Layout Builder chrome areas: `header`.
- Public-output rule: child themes must not expose authoring metadata, editor controls, model IDs, field paths, permissions, or signed editor URLs.

## Try fictional demo content safely

Use private theme Preview for existing content. Ask a developer to follow [safe demo setup](../../theme-foundation/docs/demo-setup.md) in a disposable local site when you want fictional sample pages instead.

A scoped demo run writes sample pages, widgets and demo layouts, including on repeats, and can adopt the site recovery page. An unscoped run refuses existing sites; a scoped run preserves site configuration unless the developer deliberately chooses the documented overwrite option. Review the target, languages and domains together before setup.

## Performance controls

Lazy loading controls secondary feature, proof and content-listing images. Disable it to load those images eagerly; hero images always remain eager with high priority. CSS and JavaScript use the resource variants supplied by the installed build. There is no save-time asset rebuild switch. Existing saved minification values are retained for compatibility and do not select a different asset build.

---

For developers: see the [README](../README.md).

<!-- prettier-ignore-end -->

A successful Foundation settings save invalidates generated full-page HTML and queues the configured edge-cache purge through the frontend output-cache contract. Rolled-back saves leave that output intact.
