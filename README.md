# mdimport plugin for DokuWiki

**Import Markdown files directly into the DokuWiki editor.**

This plugin adds a toolbar button to the edit page, allowing you to select a Markdown (`.md`) file from your computer. The file's content is automatically converted to DokuWiki syntax and inserted at the current cursor position.

All documentation for this plugin is available at:  
**[https://www.dokuwiki.org/plugin:mdimport](https://www.dokuwiki.org/plugin:mdimport)**

---

## Important – Folder Name

If you install this plugin manually, make sure it is placed in:

    lib/plugins/mdimport/

If the folder is named differently, the plugin will **not** work!

For general information on installing extensions in DokuWiki, see:  
[https://www.dokuwiki.org/extensions](https://www.dokuwiki.org/extensions)

---

## Features

- **One‑click import** – Select a Markdown file from your computer, and its content is converted and inserted at the cursor position.
- **Full conversion** – Supports common Markdown elements:
  - Headers (`#` → `======`)
  - Bold (`**text**`), italic (`*text*`), inline code (`` `code` ``)
  - Links (`[text](url)` → `[[url|text]]`) and images (`![alt](url)` → `{{url|alt}}`)
  - Unordered and ordered lists (with nesting)
  - Tables (with column alignment)
  - Code blocks (with language hint)
  - Blockquotes and horizontal rules
  - YAML front matter (automatically stripped)
- **Live preview** – Conversion happens in the background via AJAX; the resulting DokuWiki syntax is inserted immediately.
- **Lightweight** – No external dependencies; pure PHP and JavaScript.

---

## Requirements

- **DokuWiki** – any recent version (tested with **2025‑05‑14 “Librarian”** and later)
- **PHP** – 7.4 or higher (uses typed properties and `str_starts_with`)

---

## Installation

1. **Download** the plugin from [GitHub](https://github.com/yourusername/dokuwiki-plugin-mdimport) or the [DokuWiki plugin repository](https://www.dokuwiki.org/plugin:mdimport).
2. **Extract** the contents into `lib/plugins/mdimport/` of your DokuWiki installation.
3. **Enable** the plugin via the DokuWiki extension manager (Admin → Extension Manager) or by setting `plugin»mdimport»enabled = 1` in the configuration.

The plugin should appear immediately on the edit page toolbar.

---

## Usage

1. Open any page in edit mode.
2. Click the **“Import Markdown file”** button (📄 icon) in the toolbar.
3. A file picker dialog opens – select a `.md` or `.txt` file.
4. The content is converted and inserted at the current cursor position.

The conversion uses an internal PHP class (`MarkdownToDokuWikiConverter`) and returns pure DokuWiki syntax, so you can further edit the imported text as usual.

---

## Supported Markdown Syntax

| Markdown | DokuWiki | Notes |
|----------|----------|-------|
| `# Heading 1` | `====== Heading 1 ======` | Level 1–6 supported |
| `**bold**` or `__bold__` | `**bold**` | Same in both |
| `*italic*` or `_italic_` | `//italic//` | |
| `` `code` `` | `''code''` | |
| `[link](url)` | `[[url|link]]` | |
| `![alt](image.jpg)` | `{{image.jpg|alt}}` | |
| `- item` or `* item` | `* item` (unordered) | Nested lists use two‑space indentation |
| `1. item` | `- item` (ordered) | Ordered lists become DokuWiki unordered lists with a dash; numbering is not preserved |
| `> quote` | `>> quote` | |
| `---` | `----` | Horizontal rule |
| `` ``` `` | `<code>` and `</code>` | Language hint preserved (e.g., ` ```php `) |
| `\| Header \|` | `^ Header ^` | Table rows; alignment is detected from the separator line |

> **Note:** DokuWiki’s syntax differs from Markdown in several places; the plugin aims for a sensible conversion, but always check the result before saving.

---

## Conversion Details

- **YAML front matter** – If the file starts with a block delimited by `---` … `---` (or `...`), it is removed entirely.
- **Code blocks** – Opening and closing triple backticks are replaced with `<code>` and `</code>` tags. An optional language hint becomes a class (`<code php>`).
- **Tables** – The plugin detects the separator line (e.g., `|:---:|---:|`) and uses it to align columns (`^ left ^`, `^ center :^`, `^ right :^`).
- **Inline formatting** – Bold, italic, inline code, links, and images are converted as shown above.
- **Paragraphs** – Multiple lines of text are joined with a single space and separated by blank lines.

---

## License

**Copyright (C) 2026 sioc-de-narf <sioc.de.narf@gmail.com>**

This program is free software: you can redistribute it and/or modify it under the terms of the **GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.**

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the [GNU General Public License](https://www.gnu.org/licenses/gpl-3.0.html) for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.

The full license text is available in the `LICENSE` file included with this plugin.

---

## Author

- **Name:** sioc-de-narf  
- **Email:** sioc.de.narf@proton.me  
- **Website:** [DokuWiki plugin page](https://www.dokuwiki.org/plugin:mdimport)

---

## Contributing

Found a bug or want to improve the conversion? Feel free to open an issue or submit a pull request on [GitHub](https://github.com/yourusername/dokuwiki-plugin-mdimport). Contributions are welcome!