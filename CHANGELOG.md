# Changelog

All notable changes to `capell-app/theme-foundation` will be documented in this file.

## Unreleased

- `capell:theme-migrate --from curated --to minimalist` no longer blocks on Curated's `form` and `lightbox-carousel-viewer` sections: they map onto Minimalist's new `form` and `gallery` sections, the preview lists the fields they do not show, and rollback restores them.

- `capell:theme-migrate` now maps the retired Showreel, Submissions, Magazine, Curated and SaaS sections that have no same-named section onto the closest section of their successor (Awards, Editorial, Minimalist and Catalogue). Saved data stays, the preview lists the fields the new section does not show, and rollback restores the original section. A coverage test checks every retired theme's sections, Agency and Onepage included.

- A SiteSpec that chooses the Foundation theme now sets the primary action colour from its primary brand colour, when that colour keeps white button text and surface text readable.

- Moved the hard-coded English in the Foundation demo contact page, modern CTA section and kitchen-sink reference views into `capell-theme-foundation::demo` translation keys, with a guard test.

- Added the Foundation consent-embed component (`<x-capell-theme-foundation::consent-embed>`). It renders a third-party embed only when the active theme declares the source origin for that widget in `frontend['embedOrigins']`, and inserts the iframe only after the visitor presses a keyboard-operable load button. Visitors who never consent keep a fallback link.

- Hide stored container keys behind deterministic public presentation identifiers.

- Pass the Widget media owner through nested builder content rendering.

- **Important upgrade behaviour — footer containers become public:** the Foundation fallback footer, the affected legacy theme footer sections, and the Grounds and Switchboard custom footers now render the shared Layout Builder `footer` area. Editors who previously assigned a container to that area on any of the 23 affected themes will see its content become live immediately after upgrading. This is deliberate: existing containers are not hidden, unpublished, or gated behind a new setting.
- Added the closed, typed DesignSpec v1 contract with bounded validation, canonical JSON, and explicit compatibility readers.
- Added deterministic Foundation theme artifact compilation and validation without model-selected paths, dependencies, commands, or network access.
- Registered the `capell-theme` Project Build artifact handler through Core's public handler registry.
- Advertised the DesignSpec, compiler, and Project Build artifact capabilities in package catalogue metadata.

## 2026-06-03

- Replaced the stub `FoundationThemeHealthCheck` with real Diagnostics probes: it now verifies the Foundation Theme Studio definition is registered, the required `frontend` and `layout-builder` packages are installed, and the published frontend asset manifest exists. The `critical` health check no longer reports green when the theme is broken.
- Rewrote the marketplace summary and the `capell.json` / `composer.json` descriptions to lead with the theme's role as the base every Capell site and child theme extends, and to describe the runtime design-token system and override contracts.
- Added a manifest/provider consistency test asserting the registered Theme Studio definition matches the `capell.json` theme key, `extends`, and package name.
