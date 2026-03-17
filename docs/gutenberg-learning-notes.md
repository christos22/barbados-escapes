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
