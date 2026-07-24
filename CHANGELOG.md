# Changelog

All notable changes to `capell-app/theme-foundation` will be documented in this file.

## Unreleased

- Added the closed, typed DesignSpec v1 contract with bounded validation, canonical JSON, and explicit compatibility readers.
- Added deterministic Foundation theme artifact compilation and validation without model-selected paths, dependencies, commands, or network access.
- Registered the `capell-theme` Project Build artifact handler through Core's public handler registry.
- Advertised the DesignSpec, compiler, and Project Build artifact capabilities in package catalogue metadata.

## 2026-06-03

- Replaced the stub `FoundationThemeHealthCheck` with real Diagnostics probes: it now verifies the Foundation Theme Studio definition is registered, the required `frontend` and `layout-builder` packages are installed, and the published frontend asset manifest exists. The `critical` health check no longer reports green when the theme is broken.
- Rewrote the marketplace summary and the `capell.json` / `composer.json` descriptions to lead with the theme's role as the base every Capell site and child theme extends, and to describe the runtime design-token system and override contracts.
- Added a manifest/provider consistency test asserting the registered Theme Studio definition matches the `capell.json` theme key, `extends`, and package name.
