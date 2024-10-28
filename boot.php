<?php
// Boot.php
$addon = rex_addon::get('yform_mediafile');
rex_extension::register('PACKAGES_INCLUDED', function (rex_extension_point $ep) use ($addon) {
    rex_yform::addTemplatePath($addon->getPath('ytemplates'));
});

