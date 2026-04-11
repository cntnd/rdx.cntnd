<?php

$addon = rex_addon::get('asset_editor');

if (asset_editor_path::getAssetsRoot() === null) {
    echo rex_view::error(rex_i18n::msg('asset_editor_assets_root_missing', rex_path::assets()));
    return;
}

$bases = asset_editor_path::getBasePaths();

if ($bases === []) {
    echo rex_view::warning(
        rex_i18n::msg('asset_editor_no_base_paths_configured') . ' '
        . '<a href="' . rex_url::backendPage('asset_editor/settings') . '">' . rex_i18n::msg('asset_editor_settings_title') . '</a>'
    );
    return;
}

$baseIndex = rex_request('base', 'int', -1);
$relDir = rex_request('path', 'string', '');
$relFile = rex_request('file', 'string', '');
$csrf = rex_csrf_token::factory('asset_editor_save');

// --- Save handling ----------------------------------------------------------

if (rex_post('save', 'bool') && $csrf->isValid()) {
    $postBase = rex_post('base', 'int', -1);
    $postFile = rex_post('file', 'string', '');
    $postContent = rex_post('content', 'string', '');

    $absolute = asset_editor_path::resolve($postBase, $postFile);
    if ($absolute === null || !asset_editor_path::isEditableFile($absolute)) {
        echo rex_view::error(rex_i18n::msg('asset_editor_path_denied'));
    } else {
        $bytes = @file_put_contents($absolute, $postContent);
        if ($bytes === false) {
            echo rex_view::error(rex_i18n::msg('asset_editor_write_error'));
        } else {
            echo rex_view::success(rex_i18n::msg('asset_editor_saved'));
        }
        $baseIndex = $postBase;
        $relFile = $postFile;
        $parent = dirname($postFile);
        $relDir = ($parent === '.' || $parent === '') ? '' : $parent;
    }
}

// --- No base chosen: list configured base paths -----------------------------

if ($baseIndex < 0 || !isset($bases[$baseIndex])) {
    $items = '';
    foreach ($bases as $i => $entry) {
        $url = rex_url::currentBackendPage(['base' => $i]);
        $relativeLabel = $entry['relative'] === '' ? 'assets/' : 'assets/' . $entry['relative'];
        $items .= '<li><a href="' . $url . '">'
            . '<i class="rex-icon fa-folder-open"></i> '
            . rex_escape($entry['label'])
            . ' <small class="text-muted">' . rex_escape($relativeLabel) . '</small>'
            . '</a></li>';
    }

    $body = '<ul class="list-unstyled asset-editor-base-list">' . $items . '</ul>';

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('asset_editor_select_folder'), false);
    $fragment->setVar('body', $body, false);
    echo $fragment->parse('core/page/section.php');
    return;
}

// --- File mode: show editor -------------------------------------------------

if ($relFile !== '') {
    $absolute = asset_editor_path::resolve($baseIndex, $relFile);
    if ($absolute === null || !asset_editor_path::isEditableFile($absolute)) {
        echo rex_view::error(rex_i18n::msg('asset_editor_file_not_editable'));
        $relFile = '';
    } else {
        $fileContent = (string) @file_get_contents($absolute);
        $mode = asset_editor_path::aceModeForFile($absolute);

        $parent = dirname($relFile);
        $backPath = ($parent === '.' || $parent === '') ? '' : $parent;
        $backUrl = rex_url::currentBackendPage(['base' => $baseIndex, 'path' => $backPath]);

        $body = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
    ' . $csrf->getHiddenField() . '
    <input type="hidden" name="base" value="' . rex_escape((string) $baseIndex, 'html_attr') . '">
    <input type="hidden" name="file" value="' . rex_escape($relFile, 'html_attr') . '">
    <div class="asset-editor-toolbar">
        <a href="' . $backUrl . '" class="btn btn-default">
            <i class="rex-icon fa-arrow-left"></i> ' . rex_i18n::msg('asset_editor_back') . '
        </a>
        <strong class="asset-editor-filename">' . rex_escape($relFile) . '</strong>
    </div>
    <div class="asset-editor-wrap" data-ace-mode="' . rex_escape($mode, 'html_attr') . '">
        <textarea class="aceeditor aceeditor-css form-control" name="content" rows="20">' . rex_escape($fileContent) . '</textarea>
    </div>
    <button type="submit" name="save" value="1" class="btn btn-save">' . rex_i18n::msg('asset_editor_save') . '</button>
</form>
';

        $fragment = new rex_fragment();
        $fragment->setVar('title', rex_escape($bases[$baseIndex]['label']) . ' &mdash; ' . rex_escape($relFile), false);
        $fragment->setVar('body', $body, false);
        echo $fragment->parse('core/page/section.php');
        return;
    }
}

// --- Directory browsing -----------------------------------------------------

$absoluteDir = asset_editor_path::resolve($baseIndex, $relDir);
if ($absoluteDir === null || !is_dir($absoluteDir)) {
    echo rex_view::error(rex_i18n::msg('asset_editor_path_denied'));
    return;
}

$listing = asset_editor_path::listDir($absoluteDir);
$rows = '';

if ($relDir !== '') {
    $parent = dirname($relDir);
    $parentRel = ($parent === '.') ? '' : $parent;
    $upUrl = rex_url::currentBackendPage(['base' => $baseIndex, 'path' => $parentRel]);
    $rows .= '<tr><td><a href="' . $upUrl . '"><i class="rex-icon fa-level-up"></i> ..</a></td><td></td></tr>';
} else {
    $upUrl = rex_url::currentBackendPage();
    $rows .= '<tr><td><a href="' . $upUrl . '"><i class="rex-icon fa-level-up"></i> ..</a></td><td>' . rex_i18n::msg('asset_editor_back_to_bases') . '</td></tr>';
}

foreach ($listing['dirs'] as $dir) {
    $childRel = $relDir === '' ? $dir : $relDir . '/' . $dir;
    $url = rex_url::currentBackendPage(['base' => $baseIndex, 'path' => $childRel]);
    $rows .= '<tr>'
        . '<td><a href="' . $url . '"><i class="rex-icon fa-folder"></i> ' . rex_escape($dir) . '</a></td>'
        . '<td>' . rex_i18n::msg('asset_editor_folder') . '</td>'
        . '</tr>';
}

foreach ($listing['files'] as $file) {
    $childRel = $relDir === '' ? $file : $relDir . '/' . $file;
    $url = rex_url::currentBackendPage(['base' => $baseIndex, 'file' => $childRel]);
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $icon = $ext === 'css' ? 'fa-file-code-o' : 'fa-file-text-o';
    $rows .= '<tr>'
        . '<td><a href="' . $url . '"><i class="rex-icon ' . $icon . '"></i> ' . rex_escape($file) . '</a></td>'
        . '<td>' . strtoupper($ext) . '</td>'
        . '</tr>';
}

$heading = rex_escape($bases[$baseIndex]['label']);
if ($relDir !== '') {
    $heading .= ' / ' . $relDir;
}

$body = '
<table class="table table-hover">
    <thead>
        <tr>
            <th>' . rex_i18n::msg('asset_editor_name') . '</th>
            <th>' . rex_i18n::msg('asset_editor_type') . '</th>
        </tr>
    </thead>
    <tbody>' . $rows . '</tbody>
</table>
';

$fragment = new rex_fragment();
$fragment->setVar('title', $heading, false);
$fragment->setVar('body', $body, false);
echo $fragment->parse('core/page/section.php');
