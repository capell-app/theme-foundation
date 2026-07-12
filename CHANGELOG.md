# Changelog

All notable changes to `capell-app/theme-foundation` will be documented in this file.

## Unreleased

- Prepared package metadata and documentation for ongoing Capell 0.0.x package work.

## 2026-06-03

- Replaced the stub `FoundationThemeHealthCheck` with real Diagnostics probes: it now verifies the Foundation Theme Studio definition is registered, the required `frontend` and `layout-builder` packages are installed, and the published frontend asset manifest exists. The `critical` health check no longer reports green when the theme is broken.
- Rewrote the marketplace summary and the `capell.json` / `composer.json` descriptions to lead with the theme's role as the base every Capell site and child theme extends, and to describe the runtime design-token system and override contracts.
- Added a manifest/provider consistency test asserting the registered Theme Studio definition matches the `capell.json` theme key, `extends`, and package name.
