# Barbados Escapes: project notes

Custom WordPress/Gutenberg villa marketing site. The client (Natalie) supplies design and content; this repo holds the custom theme `gutenberg-lab-vvm` and plugin `gutenberg-lab-blocks`.

## Read on demand, not up front

- `AGENTS.md`: block, theme, search, and architecture rules plus the teaching style. Read it before code work on blocks, the theme, or search. Skip it for email, quote, and content tasks.
- `docs/inmotion-backend-deployment.md`: read before any server, deploy, or sync work.
- `docs/villa-content-import.md`: read before any villa workbook review or import.
- `docs/git-release-workflow.md`: read before a release.
- `~/.claude/codex-archive/`: history of earlier work (see the global instructions for how to search it cheaply). Procedures: `memory/procedures/barbados-ddev-sync-remote.md` and `barbados-push-single-villa-prod.md`.

## Environments

- Local: DDEV, `https://barbados-escapes.ddev.site`.
- Production: `https://barbadosescapes.com/`, docroot `/home/grapsa5/barbadosescapes.com`.
- Staging: separate install at `https://barbadosescapes.com/staging`. It must stay password protected and `noindex,nofollow`. A 302 from its homepage is the password gate, not a failure.
- Table prefix is `vvm_` everywhere.
- SSH alias `inmotion-grapsasdev`. Readiness check: `ssh -o BatchMode=yes inmotion-grapsasdev "echo ok"`. Transient `kex_exchange_identification` resets are normal on this host: retry, do not conclude the step failed.

## Code versus content

- Code deploys by Git: push to `main` runs `deploy-backend.yml` (production); `git push origin HEAD:staging` runs `deploy-staging.yml`. Only the custom theme and plugin are deployed.
- Pages, villas, navigation, and template parts are database content. A code deploy does not move them. Say which kind of change a task is.
- After block changes run `npm run build` in `wp-content/plugins/gutenberg-lab-blocks`. The build rewrites unrelated asset metadata, so stage only the intended files. Use a clean worktree if the checkout is dirty.
- Header navigation behavior comes from the saved `wp_navigation` entity. Check it before patching CSS.

## Commands

- `ddev sync-remote --dry-run`, then the real run: mirror production into DDEV.
- `ddev import-villa <workbook.xlsx> --dry-run`: workbook import. Red cells are skipped, comments are review notes, media is assigned afterwards.
- `ddev push-post --post=ID --status=draft|publish --dry-run`, then `--yes` once approved: move one post to production. It backs up the production post and does not upload missing media. Never bypass a failed dry run.
- Parse a workbook: `node wp-content/plugins/gutenberg-lab-blocks/tools/villa-import/parse-workbook.mjs <file.xlsx>`.

## Live content edits

- Use a hash-guarded WP-CLI script: confirm environment, post ID, status, and content hash; write a private backup outside the docroot; change only the approved fragment; re-read the post in a fresh process.
- If the hash is stale, someone else edited the post. Stop, re-read, and retry without overwriting their change.
- `wp eval-file` cannot read stdin. Copy the script to the server, run it, then delete it. Add `global $wpdb;` and omit `declare(strict_types=1)`.

## Cache and verification

- A purge message proves nothing. Verify the ordinary public URL: HTML, asset URLs, and the `x-proxy-cache` header.
- Query-string variants are cached separately, for example `/villas/?villa_page=2&villa_results_fragment=1`. Purge and check them individually.
- Usual clear: `wp autoptimize clear && wp cache flush && wp nginx-helper purge-all`, then exact `/purge/villas/<slug>/` purges if pages stay stale.
- Cloudflare blocks bare curl. Use a browser-like User-Agent and do not follow redirects blindly.
- Check desktop and a 390px mobile viewport for layout claims. State how many villas were checked instead of extrapolating from one.

## Villa content rules

- Workbooks: keep the hidden `_Import` sheet and key rows. Dates are `DD MMM YYYY`, rates are USD, `search_area` must match an existing taxonomy term exactly.
- Reviews: compare the workbook, the client email, the importer contract, and an independent listing. Cite exact tabs and cells. A review is read-only; importing or publishing needs separate approval.
- Guest-age options are exactly `All ages`, `Ages 12+ only`, `Adults 18+ only`. Blank means `All ages`.
- Search results are ordered by `villa_search_starting_price_usd`, highest first.
- Photos: never stretch or upscale. Client guidance is Squoosh, 2560px on the longest side for very large originals, roughly 0.5 to 1.5 MB. ShortPixel then optimizes losslessly in the background.
- Grid cards use the featured image (`_thumbnail_id`), which is separate from the hero gallery.
- A password-protected listing is a preview, not a launch. Verify the gate, noindex, and sitemap exclusion separately.

## Client work

- Young Estates and Hammerton are reference directions, not designs to clone.
- Villa import is £50 per villa: importing a completed template and fixing genuine import errors. Changes to supplied content and new owner requests are charged separately.
- Quotes: find the latest explicit acceptance in the full email thread. Keep accepted scope apart from proposals, and one-time setup apart from ongoing writing or SEO. Ask for an example before pricing unclear scope.
- Replies to Natalie: direct, concise, cordial, in the user's own tone, in the thread the user names. Explain technical terms plainly. Drafts only.
- Do not turn a client's question into an approved decision.
- Tasks live in the local Vikunja dashboard (`/home/Quester/dev/client-dashboard`). An open task is not proof that work remains: check current email and the live site.
