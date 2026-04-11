<?php

$addon = rex_addon::get('asset_editor');

if (!$addon->hasConfig('base_paths')) {
    $addon->setConfig('base_paths', []);
}
