# Generated Tailwind Asset Output Review

Package: `capell-app/theme-foundation`
Command: `capell:frontend-tailwind-assets --report`
Capture ID: `generated-tailwind-asset-output-review`

This report is the intended capture target for the generated Tailwind asset output review. It replaces the previous generic admin/settings capture for this screenshot slot.

## Report Contract

The report output is generated from `TailwindAssetsRegistry::toReport()` and must expose these top-level sections:

- `imports`
- `plugins`
- `sources`
- `theme_colors`

## Default Foundation Inputs

The Foundation Theme default Tailwind configuration contributes:

- plugin: `@tailwindcss/typography`
- source: `resources/views/**/*.blade.php`
- output CSS: `resources/css/capell/frontend.css`

Runtime theme colours are emitted by Foundation Theme head tokens. The generated Tailwind entrypoint registers safe default theme colours and package-provided Tailwind imports, plugins, sources, and theme colours for build-time discovery.

## Screenshot Requirement

The committed PNG for this capture must show the report output summary, not a Foundation settings page. The screenshot contract uses:

- `surface`: `developer`
- `targetType`: `console-command`
- `target`: `capell:frontend-tailwind-assets --report`
