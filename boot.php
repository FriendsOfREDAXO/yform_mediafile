// Boot.php
rex_extension::register('PACKAGES_INCLUDED', function (rex_extension_point $ep) {
    // Statt $this das Addon-Objekt verwenden
    rex_yform::addTemplatePath(rex_addon::get('yform_mediafile')->getPath('ytemplates'));
});

// Statische Methode als Array-Callable registrieren
rex_extension::register('MEDIA_IS_IN_USE', [rex_yform_value_mediafile::class, 'isMediaInUse']);
