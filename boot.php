// Erste Registrierung ist korrekt, da sie eine anonyme Funktion verwendet
rex_extension::register('PACKAGES_INCLUDED', function (rex_extension_point $ep) {
    rex_yform::addTemplatePath($this->getPath('ytemplates'));
});

// Zweite Registrierung muss als Callable übergeben werden
rex_extension::register('MEDIA_IS_IN_USE', [rex_yform_value_mediafile::class, 'isMediaInUse']);
