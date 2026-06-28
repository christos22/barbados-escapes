# Villa Content Workbook

## Client Workflow

The master template is [villa-content-template.xlsx](../villa-content-template.xlsx).

To create the master Google Sheet:

1. Upload the `.xlsx` file to Google Drive.
2. Open it with Google Sheets.
3. Use **File > Save as Google Sheets**.
4. Keep that converted file as the read-only master template.

For each new villa:

1. Open the master Google Sheet.
2. Use **File > Make a copy** and name it `Villa Content - Villa Name`.
3. Use the **Monkey Hill example** column and example tables as a guide only.
   Type the new villa details in **Client answer** and the left-hand table
   sections.
4. Use **Comments** for questions, caveats, or review notes. These
   comments are not imported into WordPress content.
5. Mark content red only when it should be excluded from the import. Red
   answer/table cells are skipped and reported during dry-run.
6. Use **Additional Requests** for general requests that do not belong in a
   specific row or field. This sheet is untracked and not imported.
7. Complete every yellow required field. Use `Not applicable` when a section
   genuinely does not apply; do not leave required questions blank.
8. Keep the client's wording as the source copy. Copy editing happens during
   review, not during import.
9. Share the completed Sheet with the developer.

Images are configured after the content draft is imported. When the developer
uses a source villa scaffold, gallery images from that source may be copied as
placeholders so the layout can be reviewed before villa-specific media is
assigned. If one or more iCal links are supplied, the importer stores them as
availability feeds; the developer should still sync and review the calendar
before publishing.

## Developer Workflow

Download the Google Sheet with **File > Download > Microsoft Excel (.xlsx)**.

Validate it without changing WordPress:

```bash
ddev import-villa ~/Downloads/villa-content.xlsx --source=monkey-hill --dry-run
```

Create a new villa draft:

```bash
ddev import-villa ~/Downloads/villa-content.xlsx --source=monkey-hill
```

Choose the source villa that is closest to the new villa's needs:

- `--source=monkey-hill` for the standard complete villa layout.
- `--source=with-bedroom-selection` when the new villa needs bedroom-option
  pricing/selector behaviour.
- another complete villa slug or ID when it has a closer layout, gallery shape,
  review section, or special content section.

The command refuses duplicate names and slugs. It creates a draft, assigns the
existing location taxonomy, clones the chosen source villa scaffold when
provided, replaces recognized content sections, derives card/schema metadata,
and reports the remaining media/calendar work.

Content gaps are reported as warnings instead of blocking the import. For
example, blank bedroom descriptions, incomplete staff rows, reversed rate date
ranges, red-marked skipped rows, and row comments are flagged for review after
the draft is created.
If an older completed sheet put clear pricing labels such as `3 bedroom rate`
or `3 bedrooms` in the Rates comments column, the importer promotes those
labels into the pricing table and flags that they should be moved into
**Rate label** next time.
The command still fails for breaking issues such as missing required workbook
tabs, invalid core villa facts, unknown location terms, duplicate villa names,
or generated content that cannot be built.

To update a draft previously created by this importer:

```bash
ddev import-villa ~/Downloads/villa-content.xlsx --update=1234 --dry-run
ddev import-villa ~/Downloads/villa-content.xlsx --update=1234 --yes
```

Updates are limited to importer-managed drafts. Published villas and manually
created drafts cannot be overwritten. Once media has been assigned, further
spreadsheet updates are refused so gallery work cannot be lost. A JSON backup
is written under `.ddev/villa-import-backups/` before an update.

## Workbook Contract

- Current schema version: `1.0`
- The hidden `_Import` sheet and hidden key columns/rows are part of the import
  contract and must not be removed.
- Visible labels and guidance are written for non-technical content authors.
- Monkey Hill example cells are for reference only. The importer ignores the
  example column and the example tables to the right.
- Comments are review notes only. The importer ignores the comments column on
  question tabs and row-based tabs, except for the backwards-compatible Rates
  rescue noted above.
- Red answer/table cells mean "do not import this content." A table row with a
  red import cell is skipped and reported as a warning.
- The **Additional Requests** sheet is untracked and ignored by the importer.
- Blank means "not provided." `Not applicable` means the section should be
  intentionally omitted.
- Dates use `DD MMM YYYY`, for example `10 Jan 2026`.
- Rates are USD values with the `$` symbol, for example `$2,000`.
- Existing location terms are required. Common `Saint`/`St.` variants are
  accepted, but a misspelled or unknown parish stops the import rather than
  creating a new taxonomy term.
- Use **Display address** for the guest-facing location wording.
- Use **Exact map address or plus code** when the Google Map needs a more
  precise address than the public wording.
- Use **Coordinates** for exact `latitude, longitude` values. Coordinates
  populate schema latitude/longitude and give the map button a stable Google
  Maps URL, especially when the client only has a short share link.
- A full Google Maps URL containing coordinates can also populate schema
  latitude and longitude. Short share URLs remain a review task unless
  Coordinates are supplied.
- The Overview **iCal link** field can contain one or more full calendar feed
  URLs. The importer stores each URL as a separate feed on the imported villa.
  Sync and review the availability calendar before publishing.

## Content Mapping

The workbook covers the full single-villa content shape used by Monkey Hill:

- title, hero copy, specs, and villa map card location copy
- iCal link for post-import availability-calendar setup
- bedroom selector visibility and custom comma-separated bedroom options
- main story, extended description, Villa Story highlights, staff, and nearby
  places
- Natalie's villa perspective
- bedrooms grouped by floor or area
- grouped indoor, outdoor, and resort amenities
- house rules and guest reviews
- seasonal rates, tax/deposit notes, and booking terms
- enquiry label, map/address/coordinate metadata, and related villas

The preferred importer workflow clones a complete source villa as the
Gutenberg scaffold, then replaces only sections it can map confidently:

- hero text while preserving the source hero gallery structure
- villa specs
- story columns, staff, highlights, and nearby table
- Natalie's perspective text, preserving source image columns when present
- bedroom, amenities, house-rules, and reviews tabs
- pricing, enquiry form, location/map, and related villas
- villa metadata, taxonomy terms, schema coordinates, and bedroom selector meta

If a source scaffold does not contain a recognizable section, the importer
warns instead of inventing a new design. Use a different source villa or add the
missing section manually/with AI guidance after import.

## Bedroom Selector

The workbook includes **Show bedroom selector?** on the Overview tab.

- Use `Yes` when guests should choose a bedroom configuration before enquiring.
- Use `No` when the villa is only offered as one fixed whole-villa stay.
- Use **Bedroom selector options** when the dropdown needs exact custom labels.
  Separate each option with a comma, for example `2 Bedrooms, 1 Bedroom`.
- Only include real selectable options; the website adds its own placeholder
  automatically.
- Leave **Bedroom selector options** blank to let the importer generate the
  bedroom range automatically from the bedroom total.

When enabled, the importer creates the selector automatically from Overview:
the custom comma-separated labels when provided, otherwise the automatic
bedroom range. If `Sleeps` divides evenly by the bedroom count, automatic
labels include guest capacity, such as `4 Bedrooms (sleeps 8)`.

## Maintaining the Template

The workbook is generated from the version-controlled villa schema:

```bash
cd wp-content/plugins/gutenberg-lab-blocks
npm run villa-template
```

The generated file is written to `villa-content-template.xlsx` at the project
root. When the schema changes, update the generator and parser together,
regenerate the workbook, and run a dry import before replacing the Google
Sheets master. To change the reference villa example, update
`tools/villa-import/fixtures/monkey-hill-example.json` and regenerate the
workbook.
