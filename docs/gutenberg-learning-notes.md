# Gutenberg Learning Notes

## Mental Model

- Code sets the sandbox.
- Blocks are the editing units.
- Editors control what the sandbox permits.

## Rule Of Thumb

- `theme.json` defines the design system and global defaults.
- `block.json` defines what a block supports.
- Block code defines behavior, structure, and constraints.
- A block's versioned `style` and `viewScript` files must outlive cached HTML that references them. Serve deployed build files directly; clearing a page cache must not remove the assets needed by older responses.
- Editors control content and styling only where those layers allow it.
- Global block abilities can be editor attributes in JavaScript, but frontend behavior should still be enforced in PHP when it affects rendered markup.

## Practical Reading

- Editors usually control content plus block-level presentation.
- Developers usually control layout setup, defaults, and constraints.
- Spacing, fonts, colors, backgrounds, borders, and shadows are editable only when the theme and block expose those controls.

## Hero Composition

- Villa gallery photos use the uncropped `full` attachment inside a consistent 3:2 display frame. CSS `object-fit: cover` crops the display without distorting the source, including enlarging small originals to the shared frame. The charcoal surround reserves the compact header height above the photo and a 16px minimum gap below; the opening hero and thumbnail styling keep their own treatments.
- `useFramedPhotos` is a per-hero boolean attribute, exposed as a native `ToggleControl` in Hero layout. It defaults to true, so existing galleries with no saved preference inherit framed photos. An explicit false remains a per-villa opt-out, without a content migration. PHP applies the frame only to later photo slides when enabled; the opening hero and videos always retain their full-cover treatment.
- Later photo slides reuse the existing `thumbnailLabel` in PHP, so one editor field controls both labels. A positioned photo wrapper keeps the label attached to the image in either layout; the opening slide and videos have no photo label.
- The theme uses the active framed slide to select the existing compact logo size. Its header script measures the dark band on slide changes and resize, keeping the logo centred without duplicating gallery state. The first slide and full-cover galleries keep their usual header sizing.
- Framed galleries reuse the opening photo or video poster as one decorative backdrop beneath the slider. A separate layer uses Young Estates' `#1e1e1e` dark backdrop at 90% opacity without dimming the foreground photos or adding another video player.

- Keep the hero shell and the search behavior separate when they solve different problems.
- `media-panel` is the reusable visual container for video, overlay, and hero layout.
- `villa-hero-search` is a dynamic block because its options come from real taxonomy terms and current query state.
- Use `theme.json` for reusable font tokens and spacing values, then let a block style variation consume those tokens for a page-specific visual direction.
- When editors need responsive type choices, define fluid `fontSizes` in `theme.json` and avoid hardcoding heading sizes in block CSS.
- When a hero needs to affect shared chrome like the header, prefer a body class or first-block detection over hardcoding header markup into the block itself.
- When a complex hero needs different editor regions but one precise frontend shell, keep the regions as child blocks and let the dynamic parent parse those nested blocks into the final markup.
- Static and slider modes can share the same child block markup when the only change is the parent wrapper and frontend enhancement.
- The editor can preview PHP fallbacks, like an inherited post title, without storing extra block content or recreating the exact frontend layering.
- When hero copy needs to align with shared chrome like the header menu, use the same layout gutter token on both systems instead of centering one and nudging it by eye.
- Block inspector controls are a good fit for product-specific behavior toggles like optional slider arrows; the saved attribute can stay small while PHP decides whether to emit the interactive markup.
- If a slider or other library needs structural CSS on the frontend, import it into `style.scss` so Gutenberg emits it in `style-index.css`; importing it in `index.js` only feeds the editor bundle.
- Shared UI chrome such as slider arrows should live in shared plugin assets and be applied through reusable classes, while each block keeps only its own positioning and state rules.
- Background image opacity is a block-specific behavior, so a small Inspector range control can store the percent while PHP turns it into a sanitized CSS custom property.
- When a hero needs to feel viewport-sized, cap the stage with the visible viewport minus the shared admin-bar offset, then reserve rail space only on viewports tall enough to show the whole hero in one screen.
- Vimeo embeds need iframe/player handling instead of a native `<video>` tag, so the block should store the source choice and URL while PHP/front-end JS decide how to render the real player shell.
- Keep `edit.js` preview-only for remote video sources: let the editor collect the Vimeo URL and poster image, then let dynamic PHP plus view scripts handle autoplay, fallback posters, and player lifecycle on the frontend.
- Gallery responsiveness belongs in `view.js`: prepare and decode the requested photo before moving the slider. Keep PHP's first-image priority and deferred hidden sources; warm only two neighbouring photos after the opening render and gallery intent, with low priority and respect for Save-Data/2G connections.
- Page-role styling belongs in the theme: mark the opening hero during block rendering, use shared `theme.json` text-shadow tokens, and remove image tints only within that hero. Exclude the front page explicitly so reusable blocks do not change the homepage design.
- Villa hero copy belongs to the first slide: keep native blocks editable, then use the active slide index in `view.js` to hide the shared content and its focus targets on later slides. Apply the light CSS tint only to the first slide (or static fallback), leaving gallery photos clear.

## Value Pillars

- Use a parent/child block pair when a section needs a locked scaffold but each repeated item still benefits from native drag-and-drop editing.
- Store a semantic icon slug instead of a temporary Dashicon class so the icon artwork can change later without migrating saved block content.
- When a carousel needs rich per-slide copy but one controlled front-end shell, store the media as child-block attributes and the copy as native inner blocks, then let the dynamic parent rebuild the final Splide markup.
- When inactive carousel slides still contain links or buttons, disable their focus targets in JavaScript so keyboard users only tab through the active slide's real CTA.

## Read More

- Use a dynamic block when the editor needs normal InnerBlocks content but the frontend needs stricter semantic markup, such as a real expandable `<button>`.
- Keep hidden/revealed copy in the initial HTML for accessibility and SEO; JavaScript should only enhance the interaction, not fetch the content later.

## Villa Reviews

- When content is simple and editor-managed, keep it as native Group/Paragraph blocks and enhance the frontend markup with JavaScript.
- Use a block style variation for presentation options, such as showing three reviews at a time, before adding custom Inspector controls.
- Carousel scripts need to resync when their block starts inside a hidden tab panel, because hidden elements report zero width.

## Shared Icons

- Add reusable SVG artwork to the shared registry in `includes/villas.php`; its labels and markup feed the native block selector, editor preview and PHP renderer together. The Contact icons reuse the footer paths with `currentColor`, so Gutenberg's text colour control also colours the icon.
- Keep custom size limits consistent between the editor control, preview and PHP renderer. Shared Icon permits `0.75rem`; other blocks retain their existing limits.

## Contact Page Composition

- Compose the page with Media Panel, native Groups/Columns, Shared Icon, Google Map and Contact Form 7. A new custom block is unnecessary for this layout.
- Native block attributes hold content, spacing and colours; the theme stylesheet supplies the contact photo crop and scoped form styling. The page and CF7 form are database content, so deploying CSS alone does not transfer them.
- In this WordPress version, a fixed-width child in a native Row uses `style.layout.selfStretch: "fixed"` with `flexSize`. This keeps the icon wrapper compact while its neighbouring text can wrap.
- Keep general enquiries in a separate CF7 form from villa enquiries. Verify required fields and the resulting email through DDEV Mailpit before enabling production delivery.
- Match the shared Media Panel hero's responsive bottom inset when adding page-specific header clearance; a fixed bottom inset can leave text too close to the image edge on desktop.
- For presentation-only reference changes, scope typography and divider rules to the section and verify that the saved wording, link destinations and widget triggers remain unchanged.
- Use the shared `h4` utility for Contact eyebrows; remove saved font-size/weight/tracking overrides so section labels inherit the site treatment. Hero eyebrows keep the shared XL size used on other page heroes.
- Equal-height Columns do not automatically stretch their inner Groups. On desktop, let the Contact details Group and its rows grow with flex layout; keep natural content heights below Gutenberg's 782px column breakpoint.
- Keep the CF7 submit button inside a full-width action Group: Turnstile injects block markup before it, which otherwise breaks a paragraph into extra grid rows. Preserve the widget's own space when a challenge appears.

## Typography

- Keep line-height ratios in `settings.custom.typography.lineHeight`. Native block defaults and component/editor CSS share the body and heading tokens; paragraphs belong under `styles.blocks.core/paragraph`, not the unsupported `styles.elements.paragraph` key. Clear old saved block overrides when adopting a shared default.
- Font replacements belong in the existing `theme.json` presets: keep stable slugs so saved blocks inherit the new family. The theme now uses self-hosted DM Sans for `refined-sans` and Romely for the serif presets, including the legacy `heading` alias.

## Villa Availability

- Use PHP for availability storage, iCal sync, and final calendar markup so date rules are enforced outside the browser.
- Use a dynamic block when the editor needs a pluggable calendar but the frontend data comes from villa meta, cron sync, and a custom lookup table.
- Keep the frontend script small: it should only select dates, validate the visible range against the rendered unavailable dates, and fill the enquiry form.
- When a calendar block controls related form fields, scan saved block attributes in PHP so hidden calendars also remove server-rendered CF7 fields and submit data.

## Villa Search Ordering

- Sort the complete candidate query by numeric starting nightly price, highest first, before availability filtering and pagination. A named `WP_Meta_Query` clause supplies the numeric sort; an `EXISTS`/`NOT EXISTS` group retains unpriced villas at the end. Post ID keeps equal-price results stable between pages.

## Villa Publication QA

- Calendar month navigation (`minimumStart`) and the first bookable date (`minimumDate`) are separate concerns. Enforce today in site time on both the frontend and CF7 validation; recompute it in JS so cached HTML does not admit yesterday.
- Block themes can run `wptexturize` again after `the_content`. Opt-in `villa_plain_hyphens` meta protects rendered text with hyphen entities using the HTML API; URLs, scripts, styles and stored editorial content stay untouched.
- A native Query Loop with `query.namespace: gutenberg-lab/public-villa-sitemap` lists all published, unprotected villas. Keep the surrounding Site Map headings and links as editable core blocks.
- Villa archive pages get descriptions, social metadata and self-canonicals, including `villa_page=2`. Filter combinations use `noindex` and fragment responses use `X-Robots-Tag`; villa detail pages stay indexable. Guidance: https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading
- Regression commands: `ddev wp eval-file wp-content/plugins/gutenberg-lab-blocks/tests/villa-publication-qa.php` and `node wp-content/plugins/gutenberg-lab-blocks/tests/villa-date-policy.mjs`. PHP fixture tests refuse production and remove only their temporary posts and terms.

### Availability uses occupied nights

The dynamic calendar receives occupied nights from PHP, not inclusive booking dates. A booking from the 11th to checkout on the 14th blocks the 11th, 12th and 13th. JavaScript selection and PHP search/CF7 validation must check every night in `[arrival, departure)`, including any configured buffer. The legacy `allowUnavailableEndpoints` attribute only permits checkout on the first blocked date; it never permits an occupied arrival. Existing blocks need no content migration.

Run `php tests/villa-availability-boundaries.php` and `node tests/villa-availability-boundaries.mjs` from the block plugin to check both implementations against the shared boundary fixtures.

Checkout shading is separate from date validation. Render the opposite diagonal on the first free date after an occupied night, in both PHP calendar markup and the enquiry pickers. Do not shade the last occupied night, a buffer day, or the free day after a buffer as checkout. The shared marker fixtures cover month/year boundaries, single nights and back-to-back stays.


### Closing framed gallery photos

PHP renders one accessible X outside the slide markup; JavaScript shows it only for later framed photos. X and gallery-focused Escape reuse the existing fade navigation to return to the opening view without scrolling. The opening video retains its position and prior play/pause state; confirmed playback restarts the existing five-second title/CTA fade timer.

Gallery Escape listens at document level while a photo is visible, because clicking an image leaves keyboard focus on the page. The capture listener checks open menus and dialogs before their own Escape handlers run, and ignores editable fields and an off-screen hero.

### Bedroom gallery labels below photos

The existing `isCardInteractive: false` variant identifies bedroom preview rails. Keep its image and copy in normal flow, using a 4:3 media frame and theme typography/colour tokens for the labels below it. The image no longer needs a contrast overlay. This presentation change reuses saved child-block attributes and needs no content migration. PHP selects the uncropped `large` image family; editor-only CSS keeps the media toolbar above each card.
