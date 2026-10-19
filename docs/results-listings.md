# Results listings

A `content-listing` widget on a canonical or inherited Results layout receives
hydrated items through `ResultsListingResolver`. Existing resolvers retain their
three-argument `handle(Site, Language, Page)` contract and existing serialised
results retain the `items` and `archiveUrl` keys.

Set the widget metadata `per_page` to a positive integer to opt into pagination.
The resolver must implement the additive `PaginatesResultsListing` capability;
legacy implementations continue to render their normal results without a pager.
`handlePaginated()` receives `ResultsPaginationRequestData` and returns
`ResultsListingData` with optional `ResultsPaginationData` metadata.

The default implementation bounds the page size to 1–48, orders articles before
pages, uses eligible children when present and otherwise falls back to site
content, and excludes demonstration recovery pages from the page dataset. Totals
cover the complete eligible dataset rather than the historical 24-item limit.
An out-of-range request returns no items and navigation back to a valid page.

The shared listing variants render a bounded navigation window using plain GET
links with `?page=N`. Links carry only the page parameter; they do not propagate
preview, session, search or other query values. The existing HTML cache pagination
policy already recognises `page` and partitions query variants. No Livewire write
request is needed.

The implementation uses the existing Core page loader and its publication,
site, language and blueprint rules. That loader enumerates the eligible IDs,
then hydrates the requested window plus its canonical relations. It does not
perform SQL `LIMIT` pagination. The merged article/page boundary can require two
adjacent loader windows, but it does not hydrate the complete content catalogue.
Blade receives the resolved items and navigation data and performs no queries.
