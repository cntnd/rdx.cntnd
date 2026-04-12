<?php

$addon = rex_addon::get('asset_editor');
rex_autoload::addDirectory($addon->getPath('lib'));

if (rex::isBackend() && is_object(rex::getUser())) {
    rex_perm::register('asset_editor[]', rex_i18n::msg('perm_general_asset_editor[]'));

    if (rex_be_controller::getCurrentPagePart(1) === 'asset_editor') {
        // Ace editor from cdnjs. Drop a local build into assets/ace/ and
        // swap the URL if you need to run offline.
        rex_view::addJsFile('https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ace.min.js');
        rex_view::addJsFile($addon->getAssetsUrl('editor.js'));
        rex_view::addCssFile($addon->getAssetsUrl('editor.css'));
    }
}
