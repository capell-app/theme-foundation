# Foundation Theme

<!-- prettier-ignore-start -->

## What this theme gives you

Foundation Theme is the base look and feel that every Capell theme builds on. It gives you clean, fast public pages and the settings to make them yours: your logo, colours, and fonts. On its own it is a solid, neutral starting point; visual themes extend it with a tailored design language.

## How to use it

1. Go to **Appearance > Themes** (or **Settings > Theme**).
2. Choose **Foundation Theme** and **Activate**.
3. Open **Theme settings** to set your logo, colours, and fonts.
4. **Preview** before making it live.

## What it adds

- The base page layouts every Capell theme uses.
- Theme settings for your logo, colours, and fonts.
- A fast, accessible, cache-safe public site out of the box.

## Good to know

- This is the base other themes build on; install it before any visual theme.
- Activating a visual theme keeps Foundation's behaviour and changes the design on top of it.
- **Preview** to see changes before visitors do.

## Child Theme Override Contract

Foundation Theme owns the stable child theme override surface for Capell themes. Child themes should declare `extends: 'default'` and override documented sections, views, tokens, and chrome areas instead of replacing the whole public rendering path.

Stable contract points:

- Theme Studio sections: `navigation`, `hero`, `features`, `proof`, `content-listing`, `cta`, `footer`.
- Shared views: `capell::theme.page`, `capell::layout.area`, `capell::media.svg`.
- Runtime tokens: `--foundation-page-bg`, `--foundation-section-spacing`, `--foundation-widget-gap`.
- Layout Builder chrome areas: `header`.
- Public-output rule: child themes must not expose authoring metadata, editor controls, model IDs, field paths, permissions, or signed editor URLs.

---

For developers: see the [README](../README.md).

<!-- prettier-ignore-end -->
