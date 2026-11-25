# Set up a site with Foundation

Foundation suits a site with structured pages, a documentation library or a publication that needs a straightforward layout. It also supplies the shared sections and media handling used by Capell's more specialised themes.

## Setup

On a disposable local Capell installation, install Foundation and create a separate demo site.

```sh
composer require capell-app/theme-foundation
php artisan capell:theme-foundation-demo --sites="Foundation trial" --url="https://demo.test"
```

Use a new site name and a local domain that you control. The demo command writes sample pages, widgets and layouts on every run. Read [safe demo setup](demo-setup.md) before using a named site that already exists.

For a site with your own pages, open Foundation in theme management, select the site and choose **Preview Foundation Theme**. Open a landing page and a longer content page in the preview. Choose **Apply Foundation Theme** only when you are ready to change the site's public design.

## Preset and sections

The Foundation preset uses Sora for headings and Inter for body text. Start with its regular spacing and display a short home-page heading above a features or proof section. The split hero leaves room for a meaningful image. A stacked hero works well when the heading needs more width. Choose rows for a content listing where titles and summaries carry more weight than images.

## Settings

The extension settings control light and dark colours, image corners, section spacing and heading scale. Start by adjusting one setting and preview two representative pages. Turn on lazy loading for secondary images when the page is long. The hero image is still loaded eagerly. A successful settings save can change other pages using Foundation, so check the sites that share the theme before saving.

## For your editor

Use one page heading and place the most important action near the top. Give each image useful alternative text and check the narrow preview before publishing. If the page links to a form, follow the link from the preview and verify that the form is available. Review any words copied from the demo so the published page describes your own organisation.

## Extend Foundation

For a reusable child theme, declare `extends: 'default'` and use Foundation's documented section, header and media contracts. Keep content loading in render data and use the shared media components for responsive images. See the [editor and child theme overview](overview.admin.md#child-theme-override-contract) for the stable views and tokens.

For upgrade notes, read the [package changelog](../CHANGELOG.md).
