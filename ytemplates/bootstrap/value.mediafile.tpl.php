<?php

/**
 * @var rex_yform_value_mediafile $this
 * @psalm-scope-this rex_yform_value_mediafile
 */

$notice = [];
if ('' != $this->getElement('notice')) {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$class_group = trim('form-group ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

?>

<div class="<?= $class_group ?>">
    <label class="control-label" for="<?= $this->getFieldId() ?>"><?= $this->getLabel() ?></label>
    <div class="input-group">
        <input class="form-control" type="file" id="<?= $this->getFieldId() ?>" name="file_<?= md5($this->getFieldName('file')) ?>" accept="<?= $this->getElement('types') ?>" />
        <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="const file = document.getElementById('<?= $this->getFieldId() ?>'); file.value = '';">×</button></span>
    </div>
    <?php if ($this->getValue()): ?>
        <div class="help-block">
            <dl class="<?= $this->getHTMLClass() ?>-info">
                <dt>Dateiname</dt>
                <dd><?php
                    echo '<a target="_blank" href="'.rex_url::media($this->getValue()).'">'.htmlspecialchars($this->getValue()).'</a>';
                ?></dd>
                <dt>Vorschau</dt>
                <dd><?php
                    echo '<a target="_blank" href="'.rex_url::media($this->getValue()).'"><img src="'.rex_media_manager::getUrl('rex_media_small',$this->getValue()).'" /></a>';
                ?></dd>
            </dl>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="<?php echo md5($this->getFieldName('delete')) ?>" value="1" />
                    Datei löschen
                </label>
            </div>
        </div>
    <?php endif ?>
    <input type="hidden" name="<?php echo $this->getFieldName() ?>" value="<?php echo htmlspecialchars($this->getValue()) ?>" />
    <?php echo $notice ?>
</div>
