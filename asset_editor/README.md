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

Go to **Asset-Editor → Einstellungen** and add one or more base paths, one per
line, in the format:

```
My CSS|/var/www/html/assets/css
My JS|/var/www/html/assets/js
```

The label is what the editor sees; the absolute path is validated with
`realpath()` and must exist. Paths that don't resolve are dropped with an
error message.

## Security notes

- Every user-supplied path is resolved and compared against the configured
  base paths. `..` segments are collapsed before the comparison, so
  `foo/../../etc/passwd` cannot escape.
- File writes use `file_put_contents` — make sure the PHP process has write
  access to the configured directories.
- Symlinks *inside* a base that point outside the base are not followed
  defensively. Only configure base paths you control.
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
