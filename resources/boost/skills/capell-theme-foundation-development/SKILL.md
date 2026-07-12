---
name: capell-theme-foundation-development
description: Use when editing Capell Foundation Theme Blade, Tailwind assets, media URLs, or settings.
---

# Capell Foundation Theme

Default frontend theme infrastructure: Blade components, Tailwind assets, URL helpers, and theme settings.

## Look

- `packages/theme-foundation/src`
- `packages/theme-foundation/resources`
- `packages/theme-foundation/README.md`

## Rules

- Keep components generic; branded renderers belong in theme packages.
- Preserve safe output rules for Blade and SVG media.
- Theme settings must remain optional and migration-safe.
- Run `vendor/bin/pest packages/theme-foundation/tests`.
