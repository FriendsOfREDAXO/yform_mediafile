<?php
/**
 * @var rex_yform_value_mediafile $this
 * @psalm-scope-this rex_yform_value_mediafile
 */

// Notice/Warning-Handling
$notices = array_filter([
    $this->getElement('notice') ? rex_i18n::translate($this->getElement('notice'), false) : '',
    isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']
        ? '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'
        : ''
]);

$notice = $notices ? '<p class="help-block">' . implode('<br />', $notices) . '</p>' : '';

// File type handling
$acceptedTypes = $this->getElement('types');
$fileTypeDisplay = $acceptedTypes ? '(' . str_replace(',', ', ', $acceptedTypes) . ')' : '';

// CSS Classes
$class_group = trim('form-group media-upload-widget ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

// Get file information if exists
$fileInfo = null;
if ($this->getValue()) {
    $media = rex_media::get($this->getValue());
    if ($media) {
        $fileInfo = [
            'name' => $media->getFileName(),
            'size' => $media->getSize(),
            'updateDate' => $media->getUpdateDate('d.m.Y H:i:s'),
            'extension' => $media->getExtension()
        ];
    }
}

?>
<div class="<?= $class_group ?>" data-media-widget>
    <label class="control-label" for="<?= $this->getFieldId() ?>">
        <?= $this->getLabel() ?>
        <small class="text-muted"><?= $fileTypeDisplay ?></small>
    </label>

    <!-- Drag & Drop Zone -->
    <div class="upload-zone" 
         data-drop-zone 
         ondrop="handleDrop(event)" 
         ondragover="handleDragOver(event)" 
         ondragleave="handleDragLeave(event)">
        
        <div class="upload-interface">
            <!-- File Input with custom styling -->
            <div class="file-input-wrapper">
                <input class="file-input" 
                       type="file" 
                       id="<?= $this->getFieldId() ?>" 
                       name="file_<?= md5($this->getFieldName('file')) ?>" 
                       accept="<?= $acceptedTypes ?>"
                       onchange="handleFileSelect(this)" />
                
                <div class="upload-prompt">
                    <i class="fa fa-cloud-upload"></i>
                    <span><?= rex_i18n::msg('yform_mediafile_drag_or_choose') ?></span>
                </div>
            </div>

            <?php if ($fileInfo): ?>
                <!-- Current File Preview -->
                <div class="current-file">
                    <div class="file-preview">
                        <?php if (in_array($fileInfo['extension'], ['jpg', 'jpeg', 'png', 'gif'])): ?>
                            <img src="<?= rex_media_manager::getUrl('rex_media_small', $this->getValue()) ?>" 
                                 alt="<?= htmlspecialchars($fileInfo['name']) ?>"
                                 class="preview-image" />
                        <?php else: ?>
                            <i class="fa fa-file-o file-icon"></i>
                        <?php endif ?>
                    </div>
                    
                    <div class="file-info">
                        <h5 class="file-name">
                            <a href="<?= rex_url::media($this->getValue()) ?>" 
                               target="_blank"
                               title="<?= rex_i18n::msg('yform_mediafile_view_file') ?>">
                                <?= htmlspecialchars($fileInfo['name']) ?>
                            </a>
                        </h5>
                        <p class="file-meta">
                            <span class="file-size"><?= rex_formatter::bytes($fileInfo['size']) ?></span>
                            <span class="file-date"><?= $fileInfo['updateDate'] ?></span>
                        </p>
                        
                        <div class="file-actions">
                            <button type="button" 
                                    class="btn btn-sm btn-danger" 
                                    onclick="toggleDelete(this)"
                                    data-delete-toggle>
                                <i class="fa fa-trash-o"></i>
                                <?= rex_i18n::msg('yform_mediafile_delete') ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Hidden delete checkbox -->
    <input type="checkbox" 
           class="hidden" 
           name="<?= md5($this->getFieldName('delete')) ?>" 
           value="1" 
           id="delete_<?= $this->getFieldId() ?>" />
    
    <!-- Hidden current value field -->
    <input type="hidden" 
           name="<?= $this->getFieldName() ?>" 
           value="<?= htmlspecialchars($this->getValue()) ?>" />
    
    <?= $notice ?>
</div>

<style>
.media-upload-widget .upload-zone {
    border: 2px dashed #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    background: #f8f9fa;
    margin-bottom: 10px;
}

.media-upload-widget .upload-zone.dragover {
    background: #e9ecef;
    border-color: #007bff;
}

.media-upload-widget .file-input-wrapper {
    position: relative;
    cursor: pointer;
}

.media-upload-widget .file-input {
    position: absolute;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.media-upload-widget .upload-prompt {
    padding: 30px;
    color: #666;
}

.media-upload-widget .upload-prompt i {
    font-size: 2em;
    margin-bottom: 10px;
    color: #007bff;
}

.media-upload-widget .current-file {
    display: flex;
    align-items: center;
    padding: 15px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 15px;
}

.media-upload-widget .file-preview {
    width: 100px;
    height: 100px;
    margin-right: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 4px;
}

.media-upload-widget .preview-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.media-upload-widget .file-icon {
    font-size: 2.5em;
    color: #666;
}

.media-upload-widget .file-info {
    flex: 1;
}

.media-upload-widget .file-name {
    margin: 0 0 5px 0;
    font-weight: 500;
}

.media-upload-widget .file-meta {
    color: #666;
    font-size: 0.9em;
    margin: 0 0 10px 0;
}

.media-upload-widget .file-size:after {
    content: "•";
    margin: 0 5px;
}

.media-upload-widget .file-actions {
    margin-top: 10px;
}

.media-upload-widget .btn-danger.marked-delete {
    background-color: #dc3545;
    color: white;
}

.hidden {
    display: none;
}
</style>

<script>
function handleDragOver(event) {
    event.preventDefault();
    event.currentTarget.classList.add('dragover');
}

function handleDragLeave(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('dragover');
}

function handleDrop(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('dragover');
    
    const fileInput = event.currentTarget.querySelector('input[type="file"]');
    const files = event.dataTransfer.files;
    
    if (files.length > 0) {
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change'));
    }
}

function handleFileSelect(input) {
    const widget = input.closest('[data-media-widget]');
    const deleteCheckbox = widget.querySelector('input[type="checkbox"]');
    const deleteButton = widget.querySelector('[data-delete-toggle]');
    
    if (deleteCheckbox) {
        deleteCheckbox.checked = false;
    }
    if (deleteButton) {
        deleteButton.classList.remove('marked-delete');
    }
}

function toggleDelete(button) {
    const widget = button.closest('[data-media-widget]');
    const checkbox = widget.querySelector('input[type="checkbox"]');
    
    checkbox.checked = !checkbox.checked;
    button.classList.toggle('marked-delete');
}
</script>
