# Foundation Theme reference

<!-- prettier-ignore-start -->

Developer reference for `capell-app/theme-foundation`. Start with the [README](../README.md) for what the extension does and how to install it.

## Package Details

Foundation Theme is an **Available**, **No schema impact** Capell theme in the **Capell Themes** product group. It ships as `capell-app/theme-foundation` and extends these surfaces: admin, frontend.

- Status: Available
- Tier: free
- Bundle: themes
- Composer package: `capell-app/theme-foundation`
- Namespace: `Capell\FoundationTheme`
- Theme key: `default`

## Technical Shape

### Service providers

- `Capell\FoundationTheme\Providers\FoundationThemeServiceProvider`
- `Capell\FoundationTheme\Providers\ConsoleServiceProvider`
- `FoundationSharedFragmentServiceProvider`
- `FoundationThemeSiteSpecServiceProvider`

### Config files

- `packages/theme-foundation/config/capell-theme-foundation.php`

### Settings migrations

- `packages/theme-foundation/database/settings/2026_05_10_190850_01_create_theme_foundation_settings.php`
- `packages/theme-foundation/database/settings/2026_05_23_160819_add_theme_foundation_design_tokens.php`
- `packages/theme-foundation/database/settings/2026_05_23_161002_refresh_theme_foundation_design_token_defaults.php`
- `packages/theme-foundation/database/settings/2026_05_23_170001_add_theme_foundation_composition_tokens.php`
- `packages/theme-foundation/database/settings/2026_05_23_171201_quiet_theme_foundation_composition_palette.php`
- `packages/theme-foundation/database/settings/2026_05_23_180101_add_theme_foundation_image_tokens.php`
- `packages/theme-foundation/database/settings/2026_06_07_000001_add_theme_foundation_dark_design_tokens.php`
- `packages/theme-foundation/database/settings/2026_06_07_000002_add_theme_foundation_typography_tokens.php`
- `packages/theme-foundation/database/settings/2026_07_05_000001_add_theme_foundation_motion_tokens.php`
- `packages/theme-foundation/database/settings/2026_07_14_210000_refresh_theme_foundation_capell_palette.php`
- `packages/theme-foundation/database/settings/2026_07_15_210000_add_theme_foundation_responsive_repeatable_layout.php`

### Settings classes

- `FoundationThemeSettings`
- `FoundationThemeSettingsMigrationProvider`

### Filament classes

- `AuthMenuWidget`
- `FoundationLayoutContainerSchemaExtender`
- `SectionVariantSchemaExtender`
- `FoundationThemeSettingsSchema`

### Livewire components

- `AbstractAssets`
- `PageAssets`
- `AbstractWidget`
- `Pages`

### Extension contracts

- `CompiledThemeReceiptSigningAuthority`
- `DesignSpecMigrationReader`
- `InstallsThemeDemo`
- `OptionalExtensionAvailability`
- `PaginatesResultsListing`
- `ProvidesThemeDemoContent`
- `ResultsListingResolver`

### Listeners

- `RunTailwindAssetsOnPackageChange`

### Actions

- `AssertThemeDemoTargetAction`
- `BuildAssetBannerItemsAction`
- `BuildBannerImageRenderDataAction`
- `BuildFooterLatestPageLinksAction`
- `BuildHeroRailItemsRenderDataAction`
- `BuildLayoutNeighborLinksDataAction`
- `BuildPageContentRenderDataAction`
- `BuildThemeDemoFormSectionAction`
- `BuildThemeDemoFormsPayloadAction`
- `BuildWidgetAssetRenderDataAction`
- `BuildCompiledThemeDistributionAction`
- `CanonicalizeDesignSpecAction`
- `CompileFoundationThemeArtifactAction`
- `ReadDesignSpecAction`
- `RenderFoundationDesignTokensAction`
- `ValidateCompiledThemeArtifactAction`
- `ValidateCompiledThemeDistributionAction`
- `ValidateDesignSpecAction`
- `GenerateThemeScaffoldAction`
- `HasThemeIntegrationEvidenceAction`
- `InstallFoundationThemeDemoAction`
- `InstallFoundationThemeLayoutDefaultsAction`
- `MarkPrimaryHeadingRenderedAction`
- `PrepareFoundationPageWidgetDataAction`
- `ResolveAuthMenuPayloadsAction`
- `ResolveFoundationImageLoadingAction`
- `ResolveFoundationSectionAnchorAction`
- `ResolveFoundationThemeTokensAction`
- `ResolveLoadedLayoutContainerBackgroundImageAction`
- `ResolveLoadedWidgetBackgroundImageAction`
- `ResolveNewsletterFormDataAction`
- `ResolveResultsArchiveUrlAction`
- `ResolveResultsListingAction`
- `ResolveResultsPaginationLinksAction`
- `ResolveSafeCssColorTokenAction`
- `ResolveSectionVariantScopeAction`
- `ResolveThemeFormEmbedDataAction`
- `ResolveThemeFrontendScriptDataAction`
- `ResolveThemeOptionalSectionAvailabilityAction`
- `ResolveWidgetBackgroundImageUrlAction`
- `SetupFoundationThemePackageAction`
- `ValidateThemeCatalogueEntryAction`
- `WidgetIsSlotAction`

### Data objects

- `AssetBannerItemData`
- `AuthMenuInputData`
- `AuthMenuRenderData`
- `BannerImageRenderData`
- `CanonicalDesignSpecData`
- `CompiledThemeArtifactData`
- `CompiledThemeDistributionData`
- `CompiledThemeDistributionFileData`
- `CompiledThemeDistributionReceiptData`
- `CompiledThemeFileData`
- `DesignSpecAccessibilityData`
- `DesignSpecAssetData`
- `DesignSpecBrandData`
- `DesignSpecColorModeData`
- `DesignSpecComponentsData`
- `DesignSpecData`
- `DesignSpecDisplayData`
- `DesignSpecLayoutData`
- `DesignSpecLocaleData`
- `DesignSpecLocaleTypographyData`
- `DesignSpecPaletteData`
- `DesignSpecSiteData`
- `DesignSpecTypographyData`
- `DesignSpecTypographyRoleData`
- `FooterLatestPageLinkData`
- `FoundationImageLoadingData`
- `FoundationLayoutContainerPresentationData`
- `FoundationPreparedPageData`
- `FoundationThemeTokensData`
- `LayoutNeighborLinksData`
- `NewsletterFormData`
- `PageContentRenderData`
- `ResultsListingData`
- `ResultsPaginationData`
- `ResultsPaginationLinkData`
- `ResultsPaginationRequestData`
- `SectionVariantScopeData`
- `ThemeDemoInstallData`
- `ThemeFormEmbedData`
- `ThemeFrontendScriptData`
- `ThemeScaffoldRequestData`
- `ThemeValidationResultData`
- `WidgetAssetRenderData`

### Command signatures

- `capell:frontend-tailwind-assets`
- `capell:make-foundation-theme`
- `capell:theme-catalogue-report`
- `capell:theme-foundation-demo`
- `capell:theme-foundation-setup`
- `capell:validate-themes`

### Manifest action API

- `demo: Capell\FoundationTheme\Actions\InstallFoundationThemeDemoAction`
- `setup: Capell\FoundationTheme\Actions\SetupFoundationThemePackageAction`

### Console command classes

- `DemoCommand`
- `GenerateTailwindAssetsCommand`
- `MakeFoundationThemeCommand`
- `SetupCommand`
- `ThemeCatalogueReportCommand`
- `ValidateThemesCommand`

### Manifest contributions

- `console-command: Capell\FoundationTheme\Manifest\FoundationThemeConsoleCommandsContribution`
- `public-render-data: Capell\FoundationTheme\Support\Render\FoundationImageLoadingContributor`

### Health checks

- `Capell\FoundationTheme\Health\FoundationThemeHealthCheck`

### Blade views

- `packages/theme-foundation/resources/views/app.blade.php`
- `packages/theme-foundation/resources/views/block/wrapper.blade.php`
- `packages/theme-foundation/resources/views/components/actions/index.blade.php`
- `packages/theme-foundation/resources/views/components/app/body.blade.php`
- `packages/theme-foundation/resources/views/components/app/head/custom.blade.php`
- `packages/theme-foundation/resources/views/components/app/head/tokens.blade.php`
- `packages/theme-foundation/resources/views/components/badge.blade.php`
- `packages/theme-foundation/resources/views/components/block/wrapper.blade.php`
- `packages/theme-foundation/resources/views/components/button/index.blade.php`
- `packages/theme-foundation/resources/views/components/content.blade.php`
- `packages/theme-foundation/resources/views/components/demo/contact-page.blade.php`
- `packages/theme-foundation/resources/views/components/display/art-directed-picture.blade.php`
- `packages/theme-foundation/resources/views/components/display/byline-with-metadata.blade.php`
- `packages/theme-foundation/resources/views/components/display/card-frame-wrapper.blade.php`
- `packages/theme-foundation/resources/views/components/display/count-up-stat.blade.php`
- `packages/theme-foundation/resources/views/components/display/hover-video-poster.blade.php`
- `packages/theme-foundation/resources/views/components/display/map-link.blade.php`
- `packages/theme-foundation/resources/views/components/display/photo-treatment-filter.blade.php`
- `packages/theme-foundation/resources/views/components/display/responsive-table-to-cards.blade.php`
- `packages/theme-foundation/resources/views/components/display/timestamp-metadata-block.blade.php`
- `packages/theme-foundation/resources/views/components/dropdown/index.blade.php`
- `packages/theme-foundation/resources/views/components/dropdown/link.blade.php`
- `packages/theme-foundation/resources/views/components/footer/index.blade.php`
- `packages/theme-foundation/resources/views/components/footer/latest-pages.blade.php`
- `packages/theme-foundation/resources/views/components/footer/menu.blade.php`
- `packages/theme-foundation/resources/views/components/footer/related-sites.blade.php`
- `packages/theme-foundation/resources/views/components/footer/site-info.blade.php`
- `packages/theme-foundation/resources/views/components/footer/social-links.blade.php`
- `packages/theme-foundation/resources/views/components/footer/sub-footer.blade.php`
- `packages/theme-foundation/resources/views/components/header/index.blade.php`
- `packages/theme-foundation/resources/views/components/header/menu/dropdown.blade.php`
- `packages/theme-foundation/resources/views/components/header/menu/item.blade.php`
- `packages/theme-foundation/resources/views/components/header/menu/languages-dropdown.blade.php`
- `packages/theme-foundation/resources/views/components/heading/index.blade.php`
- `packages/theme-foundation/resources/views/components/heading/subheading.blade.php`
- `packages/theme-foundation/resources/views/components/icon/index.blade.php`
- `packages/theme-foundation/resources/views/components/icon/spinner.blade.php`
- `packages/theme-foundation/resources/views/components/languages.blade.php`
- `packages/theme-foundation/resources/views/components/layout/area.blade.php`
- `packages/theme-foundation/resources/views/components/layout/container.blade.php`
- `packages/theme-foundation/resources/views/components/layout/index.blade.php`
- `packages/theme-foundation/resources/views/components/layout/main.blade.php`
- `packages/theme-foundation/resources/views/components/layout/widget.blade.php`
- `packages/theme-foundation/resources/views/components/lightbox.blade.php`
- `packages/theme-foundation/resources/views/components/list/index.blade.php`
- `packages/theme-foundation/resources/views/components/list/item.blade.php`
- `packages/theme-foundation/resources/views/components/list/list-item.blade.php`
- `packages/theme-foundation/resources/views/components/logo/index.blade.php`
- `packages/theme-foundation/resources/views/components/logo/title.blade.php`
- `packages/theme-foundation/resources/views/components/media/background.blade.php`
- `packages/theme-foundation/resources/views/components/media/svg.blade.php`
- `packages/theme-foundation/resources/views/components/no-results.blade.php`
- `packages/theme-foundation/resources/views/components/pagination/hero-summary.blade.php`
- `packages/theme-foundation/resources/views/components/section/repeatable-carousel.blade.php`
- `packages/theme-foundation/resources/views/components/section/team-member.blade.php`
- `packages/theme-foundation/resources/views/components/section/widget.blade.php`
- `packages/theme-foundation/resources/views/components/structured-data.blade.php`
- `packages/theme-foundation/resources/views/components/theme/page-shell.blade.php`
- `packages/theme-foundation/resources/views/components/widget/announcement-bar.blade.php`
- `packages/theme-foundation/resources/views/components/widget/asset/accordion.blade.php`
- `packages/theme-foundation/resources/views/components/widget/asset/banners.blade.php`
- `packages/theme-foundation/resources/views/components/widget/asset/carousel.blade.php`
- `packages/theme-foundation/resources/views/components/widget/asset/extended-background.blade.php`
- `packages/theme-foundation/resources/views/components/widget/asset/feature-item.blade.php`
- `packages/theme-foundation/resources/views/components/widget/asset/features.blade.php`
- `packages/theme-foundation/resources/views/components/widget/asset/index.blade.php`
- `packages/theme-foundation/resources/views/components/widget/asset/media.blade.php`
- `packages/theme-foundation/resources/views/components/widget/asset/pages.blade.php`
- `packages/theme-foundation/resources/views/components/widget/asset/testimonials.blade.php`
- `packages/theme-foundation/resources/views/components/widget/asset/widgets.blade.php`
- `packages/theme-foundation/resources/views/components/widget/banner-image.blade.php`
- `packages/theme-foundation/resources/views/components/widget/default.blade.php`
- `packages/theme-foundation/resources/views/components/widget/foundation-section.blade.php`
- `packages/theme-foundation/resources/views/components/widget/hero.blade.php`
- `packages/theme-foundation/resources/views/components/widget/kitchen-sink/reference.blade.php`
- `packages/theme-foundation/resources/views/components/widget/modern/alternating-content.blade.php`
- `packages/theme-foundation/resources/views/components/widget/modern/card-grid.blade.php`
- `packages/theme-foundation/resources/views/components/widget/modern/cta-section.blade.php`
- `packages/theme-foundation/resources/views/components/widget/modern/faq-section.blade.php`
- `packages/theme-foundation/resources/views/components/widget/modern/feature-list.blade.php`
- `packages/theme-foundation/resources/views/components/widget/modern/hero-banner.blade.php`
- `packages/theme-foundation/resources/views/components/widget/modern/image-gallery.blade.php`
- `packages/theme-foundation/resources/views/components/widget/modern/pricing-table.blade.php`
- `packages/theme-foundation/resources/views/components/widget/modern/process-steps.blade.php`
- `packages/theme-foundation/resources/views/components/widget/modern/stats-section.blade.php`
- `packages/theme-foundation/resources/views/components/widget/modern/team-members.blade.php`
- `packages/theme-foundation/resources/views/components/widget/modern/testimonials.blade.php`
- `packages/theme-foundation/resources/views/components/widget/navigation/index.blade.php`
- `packages/theme-foundation/resources/views/components/widget/navigation/tabs.blade.php`
- `packages/theme-foundation/resources/views/components/widget/page/breadcrumbs.blade.php`
- `packages/theme-foundation/resources/views/components/widget/page/content.blade.php`
- `packages/theme-foundation/resources/views/components/widget/slot.blade.php`
- `packages/theme-foundation/resources/views/components/widget/snippet.blade.php`
- `packages/theme-foundation/resources/views/components/widget/wrapper.blade.php`
- `packages/theme-foundation/resources/views/forms/embed.blade.php`
- `packages/theme-foundation/resources/views/forms/newsletter.blade.php`
- `packages/theme-foundation/resources/views/theme/chrome/footer.blade.php`
- `packages/theme-foundation/resources/views/theme/chrome/navigation.blade.php`
- `packages/theme-foundation/resources/views/theme/page.blade.php`
- `packages/theme-foundation/resources/views/theme/partials/mobile-navigation.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/changelog-stream--grid.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/changelog-stream.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/contact-split.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/content-listing--grid.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/content-listing--masonry-safe.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/content-listing--rows.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/content-listing.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/cta--band.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/cta--card.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/cta--inline.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/cta.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/faq-search-discovery--categorised.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/faq-search-discovery.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/features.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/footer.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/form--encouraging.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/form.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/hero--full-bleed.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/hero--split.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/hero--stacked.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/hero.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/navigation.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/pagination.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/partials/contact-split-form-body.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/partials/form-body.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/partials/form-encouraging-body.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/pricing-value-spectrum--compact.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/pricing-value-spectrum.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/proof.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/results-pagination.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/search.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/stats-display-band--light.blade.php`
- `packages/theme-foundation/resources/views/theme/sections/stats-display-band.blade.php`
- `packages/theme-foundation/resources/views/widget/kitchen-sink/reference.blade.php`
- `packages/theme-foundation/resources/views/widgets/auth-menu.blade.php`

### Cache tags

- `theme-foundation`


## Data Model

This theme has no schema impact. It relies on core Capell site, page, locale, and theme records instead of declaring package-owned tables.

<!-- prettier-ignore-end -->
