# Foundation Theme

<!-- prettier-ignore-start -->

## What this theme gives you

Foundation Theme is the base runtime and visual system that Capell themes build on. It provides shared Blade layouts, public design tokens, media/SVG handling, the Tailwind asset pipeline, and the stable override contract for child themes. On its own it is a neutral starting point; vertical themes extend it with their own design and registered sections.

It also owns DesignSpec v1 and the deterministic Foundation compiler used by Website Generator. The compiler accepts bounded structured design data and produces a validated `capell-theme` Project Build artifact from reviewed package templates; it does not execute generated code or accept model-selected dependencies and paths.

## How to use it

1. Install and set up Foundation Theme, then select the `default` theme definition in the shared Theme Studio workflow for the intended site.
2. Open the Foundation Theme extension settings surface to configure performance and runtime design tokens.
3. Build pages with the shared Layout Builder sections, then preview the public output.
4. Activate a vertical theme when you need its specialised section set; it continues to use Foundation's runtime contracts.

## What it adds

- The base page layouts every Capell theme uses.
- Extension settings for lazy loading, asset minification, light/dark colour tokens, image radius, section spacing, widget gaps, heading scale, motion intensity, and responsive repeatable layouts.
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

---

For developers: see the [README](../README.md).

<!-- prettier-ignore-end -->
