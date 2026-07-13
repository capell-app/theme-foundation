# Foundation Theme

<!-- prettier-ignore-start -->

## What This Plugin Adds

Foundation Theme is an **Available**, **No schema impact** Capell theme in the **Capell Foundation** product group. It ships as `capell-app/theme-foundation` and extends these surfaces: admin, frontend.

Capell's foundation theme - base Blade layouts, runtime design tokens, the Tailwind asset pipeline, Blade directives, media/SVG handling, and the override contracts that all vertical Capell themes extend.

After install, admins can select the theme through the core theme management surface. Editors keep using normal Capell content workflows while the package controls public presentation.

Status details:

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/theme-foundation`
- Namespace: `Capell\FoundationTheme`
- Theme key: `default`

## Why It Matters

**For developers:** The package gives developers package-owned service providers, Actions, Data objects, Filament classes, and Blade views instead of pushing this behaviour into core or application code.

**For teams:** The base theme every Capell site and child theme builds on: shared Blade layouts, a runtime design-token system (colours, spacing, radius), the Tailwind asset pipeline, an SVG sanitiser, and the section/area contracts that vertical themes override.

## Screens And Workflow

Screenshot contract: `docs/screenshots.json`.

- Foundation Homepage (frontend, required).
- Foundation Homepage - Tablet (frontend, optional).
- Foundation Homepage - Mobile (frontend, optional).
- Foundation Directory (frontend, required).
- Foundation Directory - Tablet (frontend, optional).
- Foundation Directory - Mobile (frontend, optional).
- Foundation Detail Article (frontend, required).
- Foundation Detail Article - Tablet (frontend, optional).
- Foundation Detail Article - Mobile (frontend, optional).
- Foundation Contact (frontend, required).
- Foundation Contact - Tablet (frontend, optional).
- Foundation Contact - Mobile (frontend, optional).
- Foundation Empty State (frontend, required).
- Foundation Empty State - Tablet (frontend, optional).
- Foundation Empty State - Mobile (frontend, optional).
- Foundation Page Not Found (frontend, required).
- Foundation Page Not Found - Tablet (frontend, optional).
- Foundation Page Not Found - Mobile (frontend, optional).
- Foundation Call To Action (frontend, required).
- Foundation Call To Action - Tablet (frontend, optional).
- Foundation Call To Action - Mobile (frontend, optional).

## Technical Shape

- Service providers: `Capell\FoundationTheme\Providers\FoundationThemeServiceProvider`, `FoundationThemeSiteSpecServiceProvider`.
- Config files: `packages/theme-foundation/config/capell-theme-foundation.php`.
- Settings migrations: `packages/theme-foundation/database/settings/2026_05_10_190850_01_create_theme_foundation_settings.php`, `packages/theme-foundation/database/settings/2026_05_23_160819_add_theme_foundation_design_tokens.php`, `packages/theme-foundation/database/settings/2026_05_23_161002_refresh_theme_foundation_design_token_defaults.php`, `packages/theme-foundation/database/settings/2026_05_23_170001_add_theme_foundation_composition_tokens.php`, `packages/theme-foundation/database/settings/2026_05_23_171201_quiet_theme_foundation_composition_palette.php`, `packages/theme-foundation/database/settings/2026_05_23_180101_add_theme_foundation_image_tokens.php`, `packages/theme-foundation/database/settings/2026_06_07_000001_add_theme_foundation_dark_design_tokens.php`, `packages/theme-foundation/database/settings/2026_06_07_000002_add_theme_foundation_typography_tokens.php`, `packages/theme-foundation/database/settings/2026_07_05_000001_add_theme_foundation_motion_tokens.php`.
- Settings classes: `FoundationThemeSettings`, `FoundationThemeSettingsMigrationProvider`.
- Filament classes: `FoundationLayoutContainerSchemaExtender`, `FoundationThemeSettingsSchema`.
- Livewire components: `AbstractAssets`, `PageAssets`, `AbstractWidget`, `Pages`.
- Listeners: `RunTailwindAssetsOnPackageChange`.
- Actions: `BuildAssetBannerItemsAction`, `BuildBannerImageRenderDataAction`, `BuildHeroRailItemsRenderDataAction`, `BuildLayoutNeighborLinksDataAction`, `BuildPageContentRenderDataAction`, `BuildThemeDemoFormSectionAction`, `BuildThemeDemoFormsPayloadAction`, `BuildWidgetAssetRenderDataAction`, `GenerateThemeScaffoldAction`, `HasThemeIntegrationEvidenceAction`, `InstallFoundationThemeDemoAction`, `InstallFoundationThemeLayoutDefaultsAction`, `and 11 more`.
- Data objects: `AssetBannerItemData`, `BannerImageRenderData`, `FoundationThemeTokensData`, `LayoutNeighborLinksData`, `NewsletterFormData`, `PageContentRenderData`, `ResolvedResultsData`, `ThemeDemoInstallData`, `ThemeFormEmbedData`, `ThemeScaffoldRequestData`, `ThemeValidationResultData`, `WidgetAssetRenderData`.
- Command signatures: `capell:theme-foundation-demo`, `capell:theme-foundation-setup`.
- Console command classes: `DemoCommand`, `GenerateTailwindAssetsCommand`, `MakeThemeCommand`, `SetupCommand`, `ThemeCatalogueReportCommand`, `ValidateThemesCommand`.
- Health checks: `Capell\FoundationTheme\Health\FoundationThemeHealthCheck`.
- Blade views: `packages/theme-foundation/resources/views/app.blade.php`, `packages/theme-foundation/resources/views/block/wrapper.blade.php`, `packages/theme-foundation/resources/views/components/actions/index.blade.php`, `packages/theme-foundation/resources/views/components/app/body.blade.php`, `packages/theme-foundation/resources/views/components/app/head/custom.blade.php`, `packages/theme-foundation/resources/views/components/app/head/tokens.blade.php`, `packages/theme-foundation/resources/views/components/badge.blade.php`, `packages/theme-foundation/resources/views/components/block/wrapper.blade.php`, `packages/theme-foundation/resources/views/components/button/index.blade.php`, `packages/theme-foundation/resources/views/components/content.blade.php`, `packages/theme-foundation/resources/views/components/demo/contact-page.blade.php`, `packages/theme-foundation/resources/views/components/display/art-directed-picture.blade.php`, `and 115 more`.
- Cache tags: `theme-foundation`.

## Child Theme Override Contract

Foundation Theme owns the stable child theme override surface for Capell themes. Child themes should declare `extends: 'default'` and override documented sections, views, tokens, and chrome areas instead of replacing the whole public rendering path.

Stable contract points:

- Theme Studio sections: `navigation`, `hero`, `features`, `proof`, `content-listing`, `cta`, `footer`.
- Shared views: `capell::theme.page`, `capell::layout.area`, `capell::media.svg`.
- Runtime tokens: `--foundation-page-bg`, `--foundation-section-spacing`, `--foundation-widget-gap`.
- Layout Builder chrome areas: `header`.
- Public-output rule: child themes must not expose authoring metadata, editor controls, model IDs, field paths, permissions, or signed editor URLs.

## Data Model

This theme has no schema impact. It relies on core Capell site, page, locale, and theme records instead of declaring package-owned tables.

## Install Impact

- Admin navigation: adds package-owned Filament classes when registered.
- Permissions: none declared in `capell.json`.
- Public routes: none detected in package route files.
- Database changes: no package migrations declared.
- Settings: `Capell\FoundationTheme\Settings\FoundationThemeSettings`.
- Queues or schedules: none detected in standard package paths.
- Cache tags: `theme-foundation`.
- Commands: `capell:theme-foundation-demo`, `capell:theme-foundation-setup`.

## Common Pitfalls

- Configure package settings before testing production-like workflows.
- Keep public Blade and cached HTML free of authoring markers, model IDs, permissions, signed editor URLs, and lazy database queries.
- Keep `composer.json`, `composer.local.json`, `capell.json`, docs, screenshots, and tests aligned when the package surface changes.

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |
| Background work does not run | Queue worker or scheduled command is not active | Check package jobs, commands, and host scheduler configuration | Start the queue or scheduler, then run the focused command or package test |
| Public output leaks unexpected state | Render data, cache variation, or authoring boundary has regressed | Check public Blade, cache tags, and public-output safety tests | Move data loading out of Blade and rerun the package public-output tests |

## Quick Start

1. Install the package: `composer require capell-app/theme-foundation`.
2. Run the required setup: `php artisan capell:theme-foundation-setup`.
3. Open the related Capell admin surface and verify Foundation Theme appears.

## Next Steps

- [Package docs](docs/README.md)
- [Overview](docs/overview.md)
- [Screenshot contract](docs/screenshots.json)
- [Marketplace assets](docs/assets/marketplace/)
- [Capell content language plan](../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../docs/erd/capell-and-package-erds.md)
- Related packages: [Layout Builder](../layout-builder/README.md), [Navigation](../navigation/README.md).
- Focused tests: `vendor/bin/pest packages/theme-foundation/tests --configuration=phpunit.xml`.

<!-- prettier-ignore-end -->
