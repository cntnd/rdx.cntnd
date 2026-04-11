<?php

$addon = rex_addon::get('asset_editor');
$csrf = rex_csrf_token::factory('asset_editor_settings');

if (rex_post('save', 'bool') && $csrf->isValid()) {
    $raw = rex_post('base_paths', 'string', '');
    $lines = preg_split('/\r?\n/', $raw) ?: [];
    $result = [];
    $errors = [];

    foreach ($lines as $lineNr => $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (!str_contains($line, '|')) {
            $errors[] = sprintf('Line %d: missing "|" separator.', $lineNr + 1);
            continue;
        }
        [$label, $path] = array_map('trim', explode('|', $line, 2));
        if ($label === '' || $path === '') {
            $errors[] = sprintf('Line %d: label and path required.', $lineNr + 1);
            continue;
        }
        $real = realpath($path);
        if ($real === false || !is_dir($real)) {
            $errors[] = sprintf('Line %d: path does not exist: %s', $lineNr + 1, $path);
            continue;
        }
        $result[] = ['label' => $label, 'path' => $real];
    }

    if ($errors === []) {
        $addon->setConfig('base_paths', $result);
        echo rex_view::success(rex_i18n::msg('asset_editor_settings_saved'));
    } else {
        echo rex_view::error(implode('<br>', array_map('rex_escape', $errors)));
    }
}

$configured = $addon->getConfig('base_paths', []);
$textareaValue = '';
if (is_array($configured)) {
    foreach ($configured as $entry) {
        if (is_array($entry) && isset($entry['label'], $entry['path'])) {
            $textareaValue .= $entry['label'] . '|' . $entry['path'] . "\n";
        }
    }
}

$body = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
    ' . $csrf->getHiddenField() . '
    <div class="form-group">
        <label for="asset-editor-base-paths">' . rex_i18n::msg('asset_editor_base_paths_label') . '</label>
        <textarea id="asset-editor-base-paths" name="base_paths" rows="8" class="form-control">' . rex_escape($textareaValue) . '</textarea>
        <p class="help-block">' . rex_i18n::msg('asset_editor_base_paths_help') . '</p>
    </div>
    <button type="submit" name="save" value="1" class="btn btn-save">' . rex_i18n::msg('asset_editor_save') . '</button>
</form>
';

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('asset_editor_settings_title'), false);
$fragment->setVar('body', $body, false);
echo $fragment->parse('core/page/section.php');
