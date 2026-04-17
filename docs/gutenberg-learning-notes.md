# Gutenberg Learning Notes

## Mental Model

- Code sets the sandbox.
- Blocks are the editing units.
- Editors control what the sandbox permits.

## Rule Of Thumb

- `theme.json` defines the design system and global defaults.
- `block.json` defines what a block supports.
- Block code defines behavior, structure, and constraints.
- Editors control content and styling only where those layers allow it.

## Practical Reading

- Editors usually control content plus block-level presentation.
- Developers usually control layout setup, defaults, and constraints.
- Spacing, fonts, colors, backgrounds, borders, and shadows are editable only when the theme and block expose those controls.

## Hero Composition

- Keep the hero shell and the search behavior separate when they solve different problems.
- `media-panel` is the reusable visual container for video, overlay, and hero layout.
- `villa-hero-search` is a dynamic block because its options come from real taxonomy terms and current query state.
- Use `theme.json` for reusable font tokens and spacing values, then let a block style variation consume those tokens for a page-specific visual direction.
- When editors need responsive type choices, define fluid `fontSizes` in `theme.json` and avoid hardcoding heading sizes in block CSS.
- When a hero needs to affect shared chrome like the header, prefer a body class or first-block detection over hardcoding header markup into the block itself.
- When a complex hero needs different editor regions but one precise frontend shell, keep the regions as child blocks and let the dynamic parent parse those nested blocks into the final markup.
- The editor can preview PHP fallbacks, like an inherited post title, without storing extra block content or recreating the exact frontend layering.
- When hero copy needs to align with shared chrome like the header menu, use the same layout gutter token on both systems instead of centering one and nudging it by eye.
- Block inspector controls are a good fit for product-specific behavior toggles like optional slider arrows; the saved attribute can stay small while PHP decides whether to emit the interactive markup.
- If a slider or other library needs structural CSS on the frontend, import it into `style.scss` so Gutenberg emits it in `style-index.css`; importing it in `index.js` only feeds the editor bundle.
- Shared UI chrome such as slider arrows should live in shared plugin assets and be applied through reusable classes, while each block keeps only its own positioning and state rules.
- When a hero needs to feel viewport-sized, cap the stage with the visible viewport minus the shared admin-bar offset, then reserve rail space only on viewports tall enough to show the whole hero in one screen.

## Value Pillars

- Use a parent/child block pair when a section needs a locked scaffold but each repeated item still benefits from native drag-and-drop editing.
- Store a semantic icon slug instead of a temporary Dashicon class so the icon artwork can change later without migrating saved block content.
