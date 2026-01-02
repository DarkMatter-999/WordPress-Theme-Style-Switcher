# Theme Style Switcher

A WordPress plugin that provides a block-based UI to switch between theme style variations (theme.json "styles") on the fly. It scopes generated theme.json CSS for each variation and applies the appropriate scoped styles at runtime by toggling a class on the document root. It also exposes a small settings page and helper JS API for programmatic control.

Stable release: 1.0.0  
Requires: WordPress 6.5+, PHP 7.4+  
License: GPL v2 or later

---

## Overview

`Theme Style Switcher` lets authors add a block (Theme Style Switcher) to post content or templates that allows visitors to switch between a theme's style variations (the JSON files stored in the theme's `styles/` directory). Each variation's generated CSS is scoped to a CSS class (format: `is-style-{slug}`), which the plugin toggles on the `<html>` element. The user's selection is persisted to `localStorage` so it survives page loads.

[ThemeStyleSwitcherDemo.webm](https://github.com/user-attachments/assets/5ef3ea9a-4dfb-43ef-bdfd-4c08bb390d1e)

Key behaviours:
- Reads the base `theme.json` stylesheet and all `styles/*.json` variation files from the active theme.
- Generates variant CSS and scopes it so styles only apply when `is-style-{slug}` is present on the root element.
- Enqueues scoped CSS as inline styles for each variation (no external style file required for variations).
- Adds a block providing a Buttons/Dropdown/Invisible display type for switching styles.
- Persists choice in `localStorage`.
- Exposes a small JS API `window.setThemeStyle(slug)` for programmatic switching.
- Optionally removes generated declarations that refer to `size` (layout) or `color` so the plugin can avoid being overridden by theme.json generated CSS (via settings).

---

## Features

- Block: `Theme Style Switcher` for the block editor (category `Theme Switcher`).
- Supports three display modes:
  - `buttons` (default) - renders a set of buttons on the frontend.
  - `dropdown` - renders a select element.
  - `invisible` - renders nothing on frontend (useful if you provide your own UI).
- The Theme Style Switcher block needs to be present on the page in order for the styles to be applied.
- Styles are scoped so they don't collide with other theme CSS.
- Automatically outputs `@font-face` rules and `preload` links for local font files referenced by variation JSON.
- Admin settings page under Appearance → Theme Style Switcher to control whether layout/color declarations from theme JSON should be prevented from overriding plugin-controlled variables.

---

## Installation

1. Upload the `theme-style-switcher` directory to `wp-content/plugins/`.
2. Activate the plugin at Plugins → Installed Plugins.
3. Open the block editor and insert the `Theme Style Switcher` block where desired.

---

## Usage

### Editor (Block)
- In the block editor, insert the `Theme Style Switcher` block (category: `Theme Switcher`).
- In the block inspector you can choose the display type: `Buttons`, `Dropdown`, or `Invisible`.
- The editor preview reads available variations via the `tss_data` object injected by the plugin so the editor shows previews of available options.

### Frontend
When the block renders on the frontend it outputs a container with `data-tss-block` and `data-display` attributes. The plugin's `screen.js` (registered as the block's `viewScript`) detects containers, builds either buttons or a select, wires the click/change handlers and persists selection to `localStorage`.

### Programmatic API
The plugin exposes a small global function you can use from other scripts or console:

- Set a style:
```javascript
window.setThemeStyle('dark'); // add is-style-dark to <html> and persist in localStorage
```

- Revert to default:
```javascript
window.setThemeStyle('default'); // or window.setThemeStyle(null); removes persisted value
```

The `localStorage` key used is `theme-style-slug`.

---

## How it works (implementation details)

High-level classes and responsibilities (namespaced to `DM_Theme_Style_Switcher`):

- `Plugin` - main singleton that initializes subsystems.
  - Path: `include/classes/class-plugin.php`
- `Assets` - registers & enqueues CSS/JS for frontend, editor and admin.
  - Path: `include/classes/class-assets.php`
  - Enqueued handles:
    - `main-css` → `assets/build/css/main.css`
    - `main-js` → `assets/build/js/main.js`
    - `block-css` → `assets/build/css/screen.css`
    - `block-js` → `assets/build/js/screen.js`
    - `tss-admin-css` → admin-only CSS used by the settings page
    - Per-variation inline styles are registered/enqueued as `tss-style-{slug}` (registered with no external src)
- `Blocks` - registers the block and adds a custom block category `theme-switcher`.
  - Path: `include/classes/class-blocks.php`
  - Hooks:
    - `init` → registers the block (build output path `assets/build/blocks/theme-switcher-block`)
    - `block_categories_all` → adds custom category
- `Theme_Data` - does the heavy lifting for reading theme.json and variations and preparing scoped CSS for each variation.
  - Path: `include/classes/class-theme-data.php`
  - Important methods:
    - `get_rendered_theme_json_css_for_all_variations()` - reads base theme stylesheet and all files in theme `styles/*.json`, merges variation JSON with base theme.json, cleans invalid presets, optionally filters out layout/color declarations (based on plugin settings), returns an array of slug => css.
    - `scope_theme_json_css( $css, $scoped_class )` - prefixes selectors (and `:root` variables) so the CSS only applies when `.$scoped_class` is present.
    - `tss_enqueue_scoped_variation_styles()` - registers/enqueues inline scoped styles for each variation (handles named `tss-style-{slug}`).
    - `tss_output_variation_fonts()` - outputs `<link rel="preload">` and inline `@font-face` for any local file fonts referenced by a variation JSON (looks for `file:` URIs).
    - `tss_localize_script()` - localizes `main-js` with `tss_data.variations` for use in block/editor/frontend.
  - Theme variations are discovered by scanning `get_theme_file_path( 'styles' )` for `*.json` files.
  - Variation slug is the filename (without `.json`). Variation title is either the JSON `title` field or a capitalized slug.


### Asset pipeline & build
- Source front-end and block code lives in `assets/src/`.
- Built assets are expected in `assets/build/`.
- `package.json` contains npm scripts:
  - `npm run build` - builds production-ready assets into `assets/build`.
  - `npm run start` - dev/watch mode (uses `@wordpress/scripts`).
- When developing, run the build pipeline to produce `main.css`, `main.js`, `screen.js`, etc. The plugin enqueues the built files from `assets/build`.

### Settings & Options
- Admin settings page: Appearance → Theme Style Switcher
  - File: `include/classes/class-settings.php`
  - Options registered:
    - `dm_tss_override_layout` (boolean) - when true, plugin strips declarations that reference `size` from generated variation CSS (prevents theme.json layout declarations from overriding plugin-controlled layout variables).
    - `dm_tss_override_colors` (boolean) - when true, plugin strips declarations that reference `color` from generated variation CSS (prevents theme.json color declarations from overriding plugin-controlled color variables).

Settings UI is built with simple checkboxes and is saved via WordPress Settings API.

---

## Troubleshooting & Notes

- If no styles directory exists in the current theme (no `styles/*.json`), the plugin will still register and enqueue a scoped `base` stylesheet derived from the theme's `theme.json`.
- Variation CSS is generated from the theme.json machinery (WordPress' `WP_Theme_JSON`), then scoped. Heavy or complex theme.json files can result in large inline styles; be mindful of performance.
- When the plugin outputs `@font-face` rules and preloads, it only handles local `file:` references found inside variation JSON files.
- If you see missing block assets after installing or updating the plugin, ensure `assets/build` exists with built JS/CSS (run `npm run build` if developing or if the repo doesn't include built assets).
- Because variation CSS is added via `wp_add_inline_style()`, it inherits the handle's dependencies order. If you need to alter dependency order, adjust enqueued handles or filters in your theme/plugin.

---

## Development

- Build assets:
  - Install dependencies: `npm install`
  - Build for production: `npm run build`
  - Dev/watch: `npm run start`
- Linting:
  - JS/CSS linting is available via `@wordpress/scripts` and commands in `package.json`.
  - PHP linting / coding standards are defined (see `composer.json` and `phpcs.xml` if present).
- Keep namespaced PHP classes consistent with the autoloader expectations and file names (see `include/helpers/autoloader.php`).

---

## Example: Programmatic usage in theme or plugin

- To set a style from theme JS:
```javascript
window.setThemeStyle('my-variation-slug')
```

- To add a "switch to dark style" link in your theme's template:
```html
<a href="#" onclick="window.setThemeStyle('dark'); return false;">Switch to dark</a>
```

---

## Changelog

- 1.0.0
  - Initial release: block registration, variation discovery, scoped CSS generation, font preloads, settings page, frontend view script.

---

## File map (important plugin files)

- `theme-style-switcher.php` - plugin bootstrap (defines `TSS_PLUGIN_PATH`, `TSS_PLUGIN_URL`, loads autoloader, instantiates plugin)
  - Path: `wp-content/plugins/theme-style-switcher/theme-style-switcher.php`
- `include/helpers/autoloader.php` - PSR-like autoloader for plugin classes
  - Path: `wp-content/plugins/theme-style-switcher/include/helpers/autoloader.php`
- `include/classes/class-plugin.php` - bootstraps `Assets`, `Blocks`, `Theme_Data`, `Settings`
  - Path: `wp-content/plugins/theme-style-switcher/include/classes/class-plugin.php`
- `include/classes/class-assets.php` - enqueues styles/scripts and handles admin enqueue logic
  - Path: `wp-content/plugins/theme-style-switcher/include/classes/class-assets.php`
- `include/classes/class-blocks.php` - registers block & block category
  - Path: `wp-content/plugins/theme-style-switcher/include/classes/class-blocks.php`
- `include/classes/class-theme-data.php` - renders & scopes variation CSS, handles fonts, localization
  - Path: `wp-content/plugins/theme-style-switcher/include/classes/class-theme-data.php`
- `include/classes/class-settings.php` - admin settings page
  - Path: `wp-content/plugins/theme-style-switcher/include/classes/class-settings.php`
- Block source (unbuilt):
  - `assets/src/blocks/theme-switcher-block/*` - `index.js`, `edit.js`, `save.js`, `view.js`, `block.json`
- Built assets (what the plugin enqueues):
  - `assets/build/...` (expected after running build)

---

## Security & Best Practices

- The plugin uses WordPress APIs for enqueuing, i18n, and options. Always sanitize/escape output when extending or modifying code.
- When reading theme files with `file_get_contents()` the plugin trusts theme authors. If you allow user-uploaded or third-party variations, validate JSON and file paths.
- When adding inline styles and printed HTML, ensure intended escaping contexts are respected. The plugin currently prints `@font-face` blocks and `link` tags directly - if modifying that behavior, keep proper escaping.

---

## License & Links

- License: GPL v2 or later - refer to plugin header for details.
- Repository / Author: https://github.com/DarkMatter-999/WordPress-Theme-Style-Switcher
