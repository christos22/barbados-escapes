# Licensed Romely deployment

The owner supplied `Romely-VF.zip` and `EULA-q5smwh.pdf` from their DIN Studio
Web Font License download on 15 September 2026. The EULA is general licence
terms, not a personalised receipt or confirmation of the purchased pageview
tier. Retain the order confirmation separately with the client's records.

## Asset used

- ZIP member: `Romely/Web-TT/Romely-Regular.woff2` (unmodified).
- Family/style: Romely Regular, normal, weight 400, as before.
- Public webfont filename: `Romely-Regular-b9d5f6197645.woff2`.
- Font SHA-256: `b9d5f6197645b1df7f175dcc3e513cfff2c65207a04e7ab0e7053d8a2519b764`.
- EULA SHA-256: `1aa3dab4c2c5d4918256bef3ced4b6a355b353eb1fa9e1dd10596cebe733c60a`.
- Original ZIP SHA-256: `6ebd1f77f5e21ad8879f4f15abd739819c65359bdf79556d401844db206138f8`.

No other weights, font families, typography presets, spacing or line heights
are changed. DM Sans remains the existing self-hosted open-licensed asset.

## Private storage, not public Git

This GitHub repository was verified public on 15 September 2026. Do not commit
the purchased font files, ZIP, EULA, receipts or customer billing information.
The theme's `assets/fonts/romely/` directory is ignored by Git.

Private server directory (outside both WordPress document roots):
`/home/grapsa5/barbados-private-assets/romely/2026-09-15/`.

It contains the original ZIP, EULA and extracted `Romely-Regular.woff2`.
Use mode 700 for private directories and 600 for private files. Only the
selected WOFF2 is installed in the website, with mode 644 for font loading.
Do not copy the EULA or full font package into the publicly served theme.

Local private copies are stored outside the WordPress checkout at
`/home/Quester/.local/share/barbados-escapes/licenses/romely/2026-09-15/`.
Use the same installer with this source and the local theme directory when
preparing a local preview. A fresh clone intentionally does not contain Romely;
an authorised developer must obtain the licensed asset privately.

## Deployment and rollback

All three deploy paths (staging Actions, production Actions and cPanel) run
`docs/scripts/install-licensed-romely.sh check PRIVATE_DIRECTORY` before copying
code. Installation rejects missing or changed source files. Font mirroring
excludes Romely, then installs and verifies the exact licensed WOFF2 separately.
The new filename avoids reusing browser caches for the former font.

The installer moves a previous `Romely-Regular.woff2` into private storage as
`retired-SHA256.woff2`. It is retained for controlled rollback, not offered as a
public download. Deploying a historical commit may restore that historical
font, so any rollback needs an explicit font review.

Removing the previous binary from the current branch does not erase it from
historical Git commits. No repository history rewrite is part of this change.

Review on staging before merging to `main`; production deployment remains a
separate approval. The private source must exist before either deployment.
