# Move sites off a retired theme

Seven themes are no longer sold. Each was merged into a successor:

| Retired theme | Successor  |
| ------------- | ---------- |
| Showreel      | Awards     |
| Submissions   | Awards     |
| Magazine      | Editorial  |
| Curated       | Minimalist |
| SaaS          | Catalogue  |
| Agency        | Portfolio  |
| One-page      | Portfolio  |

Sites on a retired theme keep rendering while its package is installed. `capell:theme-migrate` moves them onto the successor and refuses any other pair. It moves four kinds of record:

- every site on the retired theme;
- every layout that names the retired theme itself, because a layout's theme wins over its site's theme for every page on that layout. Global layouts with no site move too;
- every widget that uses one of the retired theme's sections (`capell.widget.agency.hero`, for example). It is pointed at the same section on the successor (`capell.widget.portfolio.hero`). If the successor has no such section, the preview names the widgets and nothing moves;
- the Theme Studio active theme, when it is the retired theme. It switches to the successor and the chosen preset.

Soft-deleted sites, layouts and widgets move too, so restoring one later never puts it back on a removed theme.

A theme's section widget draws its saved section type with the theme's own section view. Some retired sections exist on the successor under another name. Agency and Onepage sections that Portfolio has no same-named view for move to the Portfolio section that shows the same items:

| Retired section                                                                                              | Portfolio section    |
| ------------------------------------------------------------------------------------------------------------ | -------------------- |
| Agency `portfolio-grid`, `creator-directory`; Onepage `showcase-hero`, `one-page-grid`, `templates-sections` | `project-feed`       |
| Agency `featured-portfolios`, `awarded-profiles`, `education-upsell`; Onepage `build-resources`              | `related-projects`   |
| Agency `filter-taxonomies`; Onepage `category-tabs`                                                          | `discipline-filters` |
| Onepage `tools-sponsors`                                                                                     | `credits-tools`      |

A section's variant is kept and falls back to the Portfolio section's default layout. A retired section with no items of its own showed sample items; the Portfolio section shows only saved items, so the preview names those widgets and nothing moves until you add their items or remove them. Items without their own label show the Portfolio section's default text, which the preview names. The widget's saved data is not removed. If the old section kept its list under `stories`, a copy is also saved as `items`, where the Portfolio section reads it. The preview names each mapped widget and any saved field the new section does not show, such as an award or a count. A rollback restores the original section and removes only the copied list.

If the successor has neither a view for that type nor a mapped section, the widget would render nothing, so the preview names the widgets and nothing moves. A section variant the successor does not offer falls back to that section's default layout.

If widgets still use the retired theme's sections but no retired theme record exists, the command cannot move them. The preview lists each widget with the successor section to point it at in the widget editor.

If a layout's containers, a widget's item component (including `meta.component_item`) or a blueprint names one of the retired theme's sections, the command cannot move it. The preview lists the location and count, and nothing moves until you update those records by hand.

## Order of operations

Run the migration on every environment that has sites on a retired theme before that theme's package is removed. Remove the package only after the residual-reference check below reports nothing for that theme on every environment. Once the package is gone, the retired theme cannot render and the preview has nothing to compare against. Install the successor package first; the command refuses to move sites to a theme that is not installed.

## Preview, then apply

```bash
php artisan capell:theme-migrate agency portfolio
```

Without `--force` nothing changes. The preview lists every site, layout and widget that would move by id and name, with each widget's old and new section, says whether the Theme Studio active theme switches, and shows the preset it will use, and anything the move does not carry across:

- custom CSS saved on the retired theme;
- hero image and video media on the retired theme;
- theme settings such as colours, header position, the dark mode toggle and secondary containers that differ from the successor.

Recreate any of those on the successor theme if the moved sites need them. Page text, media and navigation are not changed. Layouts change only their theme, and widgets change only the section they render with; their content and settings stay as they are.

```bash
php artisan capell:theme-migrate agency portfolio --force
```

## When the successor is already in use

A theme's preset and saved design tokens are stored on one theme record shared by every site that uses it. If the successor record already has sites or saved tokens, the command will not change its preset or tokens, because that would restyle those sites. Instead:

1. Name the successor's current preset with `--preset=PRESET`; the preview prints it, and any other preset is refused.
2. Review the token differences in the preview: the moved sites will use the successor's values.
3. Confirm with `--allow-shared-successor`.

```bash
php artisan capell:theme-migrate onepage portfolio --preset=PRESET --allow-shared-successor --force
```

When the successor record is unused, it takes the first successor preset (or `--preset`) and the retired theme's saved design tokens. A disabled successor record is enabled; the preview says so, including when other sites already use it.

## Roll back

Every applied move writes a rollback report under `storage/app/capell/theme-migrations/` and prints its path. The report records:

- the site and layout ids and names that actually moved;
- the ids of the widgets that were pointed at the successor, with their old and new sections;
- both theme ids and keys;
- the successor record's previous settings;
- the previous Theme Studio active theme;
- a fingerprint of the installation it ran on.

Without `--force`, `--rollback` runs the same checks as a real rollback and lists the sites, layouts and widgets that would move back.

```bash
php artisan capell:theme-migrate --rollback=storage/app/capell/theme-migrations/20261112-101500-000000-agency-to-portfolio.json
php artisan capell:theme-migrate --rollback=storage/app/capell/theme-migrations/20261112-101500-000000-agency-to-portfolio.json --force
```

Rollback refuses, changing nothing, when the report has already been used, when it was written on another installation (for example the environment this one was cloned from, or after APP_KEY was rotated or the database connection or database was renamed), when its theme ids no longer belong to the same retired theme and successor (for example a report copied from another environment, or recreated theme records), or when the retired theme's package is no longer installed. A report is stamped with `rolled_back_at` as soon as the rollback commits, before caches are refreshed, so an old report cannot move records back after a later migration.

It moves back only the sites and layouts that migration actually moved and that are still on the successor, and restores only the widgets still on the section the migration set; records that were already on the successor, or reached it later, are not moved. The successor record's previous preset, tokens and status are restored only when no site or layout uses it any more and nobody has edited it since; otherwise its current settings are kept and the command says so. The record itself is never deleted. The default theme flag and the Theme Studio active theme move back only while they still hold what the migration set. If the retired theme record is disabled, the command says so.

## Safety

- Only one migration or rollback runs at a time. The lock uses the application cache, so on a multi-server installation the cache store must be shared (for example Redis or the database), not `file` or `array`.
- Site, layout, widget, theme and Theme Studio writes happen in one transaction. After it commits, caches are flushed, the CDN is purged for every site that renders a moved site, layout or widget (including sites using a moved global layout), public widget snapshots for the old theme and for layouts placing a moved widget are revoked, and the theme preview is regenerated.
- If a move fails after a new successor record was created, that unused record stays; it is harmless and the next run reuses it.

## Before removing a retired theme package

After every move, and on a rerun with nothing left to move, the command runs a residual-reference scan and says whether the retired theme's package can be removed on that installation. The verdict comes only from a raw search: the Theme Studio active theme and `capell-frontend.foundation_theme` settings, every text and JSON column of every table for the theme's section prefix, and every `theme_id` column, or column with a foreign key to the themes table, for any record of the retired theme. The search reads rows through the query builder, so soft-deleted rows and Publishing Studio workspace drafts count. Only tables with this connection's table prefix are searched, so another application sharing the database is left alone. Activity, audit, event, metric, job, session, cache, migration, revision and snapshot tables are not searched, with or without the Capell table prefix. The search runs on MySQL, MariaDB, SQLite and PostgreSQL; on PostgreSQL whole JSON columns are cast to text for the search. While anything still refers to the theme, or anything else blocks the move, the command exits with a failure code, including after a move that succeeded, so scripts can stop before removing the package. The Foundation doctor check `legacy.foundation.theme.retired.theme.sites` runs the same scan for every retired theme and reports counts per location: `sites.theme_id`, `layouts.theme_id` (soft-deleted and global layouts included), `layouts.containers`, `widgets.component`, `widgets.meta.component`, `widgets.component_item`, `widgets.meta.component_item`, the blueprint component columns and the Theme Studio active theme. It warns while references remain on an installed retired theme and fails once that theme's package is removed. Those counts include soft-deleted rows and workspace drafts, and they explain the verdict, but the doctor check does not run the schema-wide search, so rerun the command before removing a package.

The move refuses to start while a Publishing Studio workspace holds a draft of the retired theme record, or a draft site, layout or widget that still uses it, and names those workspaces. Publish or discard the drafts, then run the command again. An abandoned workspace keeps its rows until it is pruned with `php artisan capell:publishing-studio:prune --id=<id>`, which the message shows for each abandoned workspace.

Remove a retired theme's package only when the command reports it safe to remove on every environment. Then run `php artisan view:clear`, so no compiled view still includes the removed package's templates. History rows (layout content snapshots and public render contract events) and layout preset labels keep the old theme id or key on purpose, and theme settings stored for a retired theme inside layout containers are ignored once its package is gone.
