# Villa Content Workbook

## Client Workflow

The master template is [villa-content-template.xlsx](villa-content-template.xlsx).

To create the master Google Sheet:

1. Upload the `.xlsx` file to Google Drive.
2. Open it with Google Sheets.
3. Use **File > Save as Google Sheets**.
4. Keep that converted file as the read-only master template.

For each new villa:

1. Open the master Google Sheet.
2. Use **File > Make a copy** and name it `Villa Content - Villa Name`.
3. Complete every yellow required field. Use `Not applicable` when a section
   genuinely does not apply; do not leave required questions blank.
4. Keep the client's wording as the source copy. Copy editing happens during
   review, not during import.
5. Share the completed Sheet with the developer.

Images and availability-calendar feeds are configured after the content draft
is imported. The workbook may describe media requirements, but it does not
upload or reuse images.

## Developer Workflow

Download the Google Sheet with **File > Download > Microsoft Excel (.xlsx)**.

Validate it without changing WordPress:

```bash
ddev import-villa ~/Downloads/villa-content.xlsx --dry-run
```

Create a new villa draft:

```bash
ddev import-villa ~/Downloads/villa-content.xlsx
```

The command refuses duplicate names and slugs. It creates a draft, assigns the
existing location taxonomy, builds the Gutenberg page, derives card/schema
metadata, and reports the remaining media/calendar work.

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
- Blank means "not provided." `Not applicable` means the section should be
  intentionally omitted.
- Dates use `YYYY-MM-DD`.
- Rates are numeric USD values without currency symbols.
- Existing location terms are required. A misspelled or unknown parish stops
  the import rather than creating a new taxonomy term.
- A full Google Maps URL containing coordinates populates schema latitude and
  longitude. Short share URLs remain a review task.

## Content Mapping

The workbook covers the full single-villa content shape used by Monkey Hill:

- title, summary, hero copy, specs, and archive card copy
- bedroom selector visibility and lowest bedroom option
- main story, extended description, staff, highlights, and nearby places
- Natalie's villa perspective
- bedrooms grouped by floor or area
- grouped indoor, outdoor, and resort amenities
- house rules and guest reviews
- seasonal rates, tax/deposit notes, and booking terms
- enquiry copy, map/address content, and related villas

The importer builds this structure from a neutral PHP content builder. It never
duplicates another villa post, attachment IDs, calendar feeds, reviews, map
settings, or related-villa IDs.

## Bedroom Selector

The workbook includes **Show bedroom selector?** on the Overview tab.

- Use `Yes` when guests should choose a bedroom configuration before enquiring.
- Use `No` when the villa is only offered as one fixed whole-villa stay.
- Leave **Lowest bedroom option to show** blank for `1`.
- Enter a higher number only when smaller configurations are not offered. For
  example, a 7-bedroom villa that rents only as 5, 6, or 7 bedrooms should use
  `5`.

When enabled, the importer creates the selector automatically from Overview:
maximum bedrooms down to the lowest bedroom option. If `Sleeps` divides evenly
by the bedroom count, the labels include guest capacity, such as
`4 Bedrooms (sleeps 8)`.

## Maintaining the Template

The workbook is generated from the version-controlled villa schema:

```bash
cd wp-content/plugins/gutenberg-lab-blocks
npm run villa-template
```

The generated file is written to `docs/villa-content-template.xlsx`. When the
schema changes, update the generator and parser together, regenerate the
workbook, and run a dry import before replacing the Google Sheets master.
