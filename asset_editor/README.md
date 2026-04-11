# asset_editor

REDAXO 5 backend addon to edit `.css` and `.js` files from configurable base
paths with syntax highlighting (Ace editor).

## Features

- Browse and edit `.css` / `.js` files inside folders you configure as
  "allowed base paths".
- Ace editor with syntax highlighting per file type, tab indent, soft tabs,
  `Ctrl+S` / `Cmd+S` shortcut to save.
- Path traversal protection: every request is normalized and verified to stay
  inside one of the configured base paths.
- Only `.css` and `.js` files are editable. Everything else is rejected.
- Permission-gated: users need `asset_editor[]` to see the page.

## Installation

Copy the folder to `redaxo/src/addons/asset_editor/` and activate it in the
REDAXO backend under *System → AddOns*.

## Configuration

All editable content lives under the public REDAXO `assets/` directory
(`rex_path::assets()`). Go to **Asset-Editor → Einstellungen** and add one or
more base paths, one per line, in the format:

```
CSS|css
JS|js
Theme CSS|theme/css
```

The left side is the label shown in the browser, the right side is a path
**relative to the assets root**. An empty path means the assets root itself.
Paths that don't exist or that would escape the assets root are rejected
with an error message.

## Security notes

- Base paths are stored relative to `rex_path::assets()` and resolved with
  `realpath()` at read time. Any entry whose resolved path leaves the assets
  root is discarded, so the addon can never edit files outside `assets/`.
- Within a base, user-supplied relative paths are normalized (`.` / `..`
  collapsed) and required to stay inside the base. `foo/../../etc/passwd`
  cannot escape.
- File writes use `file_put_contents` — make sure the PHP process has write
  access to the configured directories.
- Symlinks *inside* a base that point outside the assets root are not
  followed defensively. Only configure base paths you control.
- Forms use `rex_csrf_token`; mutations require a valid token.

## Ace editor

Ace is loaded from cdnjs by default
(`https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ace.min.js`). If you need
an offline build, drop Ace into `assets/ace/` and adjust the URL in
`boot.php`.

## Release to MyREDAXO

See `.github/workflows/publish-to-redaxo.yml`. Configure `MYREDAXO_USERNAME`
and `MYREDAXO_API_KEY` as repository secrets, then publish a GitHub release
with a semver tag (no `v` prefix) to upload the addon automatically.
