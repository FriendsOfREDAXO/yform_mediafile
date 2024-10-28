<?php

class rex_yform_value_mediafile extends rex_yform_value_abstract
{
    private const ERROR_TYPES = [
        'MIN_ERR' => 'min_err',
        'MAX_ERR' => 'max_err',
        'TYPE_ERR' => 'type_err',
        'EMPTY_ERR' => 'empty_err'
    ];

    private array $warnings = [];
    private array $errorMessages = [];

    public function enterObject(): void
    {
        if (!is_string($this->getValue())) {
            $this->setValue('');
        }

        $this->initializeErrorMessages();
        $this->setupMediaCategory();
        
        if (!$this->isEditable()) {
            $this->handleNonEditableState();
            return;
        }

        if ($this->params['send']) {
            $this->handleFormSubmission();
        }

        $this->renderOutput();
    }

    private function initializeErrorMessages(): void
    {
        $messages = explode(',', $this->getElement('messages'));
        $this->errorMessages = [
            self::ERROR_TYPES['MIN_ERR'] => $messages[0],
            self::ERROR_TYPES['MAX_ERR'] => $messages[1] ?? $messages[0],
            self::ERROR_TYPES['TYPE_ERR'] => $messages[2] ?? $messages[0],
            self::ERROR_TYPES['EMPTY_ERR'] => $messages[3] ?? $messages[0]
        ];
    }

    private function setupMediaCategory(): void
    {
        $categoryId = ('' == $this->getElement(8)) ? 0 : (int) $this->getElement('category');
        $category = rex_media_category::get($categoryId);
        if (null === $category) {
            $categoryId = 0;
        }
        $this->mediaCategoryId = $categoryId;
    }

    private function handleFormSubmission(): void
    {
        $rdelete = md5($this->getFieldName('delete'));
        $rfile = 'file_' . md5($this->getFieldName('file'));

        if (isset($_REQUEST[$rdelete]) && 1 == $_REQUEST[$rdelete]) {
            $this->setValue('');
            return;
        }

        if (!isset($_FILES[$rfile]) || empty($_FILES[$rfile]['name'])) {
            return;
        }

        if (!$this->validateFileSize($rfile)) {
            return;
        }

        $this->processMediaUpload($rfile);
        $this->updateValuePools();
        $this->validateRequired();
    }

    private function validateFileSize(string $fileKey): bool
    {
        $sizes = $this->getFileSizeLimits();
        
        if ($_FILES[$fileKey]['size'] < $sizes['min']) {
            $this->warnings[] = $this->errorMessages[self::ERROR_TYPES['MIN_ERR']];
            return false;
        }
        
        if ($_FILES[$fileKey]['size'] > $sizes['max']) {
            $this->warnings[] = $this->errorMessages[self::ERROR_TYPES['MAX_ERR']];
            return false;
        }

        return true;
    }

    private function getFileSizeLimits(): array
    {
        $sizes = array_map('intval', explode(',', $this->getElement('max_size')));
        return [
            'min' => count($sizes) > 1 ? (int) ($sizes[0] * 1024) : 0,
            'max' => count($sizes) > 1 ? (int) ($sizes[1] * 1024) : (int) ($sizes[0] * 1024)
        ];
    }

    private function processMediaUpload(string $fileKey): void
    {
        $file = $_FILES[$fileKey];
        
        $data = [
            'title' => (string) ($file['name'] ?? ''),
            'category_id' => $this->mediaCategoryId,
            'file' => [
                'name' => (string) ($file['name'] ?? ''),
                'tmp_path' => (string) ($file['tmp_name'] ?? ''),
                'path' => (string) ($file['tmp_name'] ?? '')
            ]
        ];

        $allowedExtensions = $this->getAllowedExtensions();

        try {
            $return = rex_media_service::addMedia($data, true, $allowedExtensions);
            if (1 == $return['ok']) {
                $this->setValue($return['filename']);
            } else {
                $this->warnings = array_merge($this->warnings, $return['messages']);
                $this->setValue('');
            }
        } catch (rex_api_exception $e) {
            $this->warnings[] = $e->getMessage();
            $this->setValue('');
        }
    }

    private function getAllowedExtensions(): array
    {
        $extensions = [];
        if ('' != $this->getElement('types')) {
            $extensions['types'] = $this->getElement('types');
        }
        return $extensions;
    }

    private function updateValuePools(): void
    {
        $this->params['value_pool']['email'][$this->getElement('name')] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getElement('name')] = $this->getValue();
        }
    }

    private function validateRequired(): void
    {
        if (1 == $this->getElement('required') && '' == $this->getValue()) {
            $this->warnings[] = $this->errorMessages[self::ERROR_TYPES['EMPTY_ERR']];
        }
    }

    private function renderOutput(): void
    {
        if (count($this->warnings) > 0) {
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = implode(', ', $this->warnings);
        }

        if ($this->needsOutput() && $this->isViewable()) {
            $template = $this->isEditable() 
                ? 'value.mediafile.tpl.php'
                : ['value.mediafile-view.tpl.php', 'value.view.tpl.php'];
            
            $this->params['form_output'][$this->getId()] = $this->parse($template);
        }
    }

    public function getDescription(): string
    {
        return 'mediafile|name|label|groesseinkb|endungenmitpunktmitkommasepariert|pflicht=1|min_err,max_err,type_err,empty_err|[no_db]|mediacatid|user';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'mediafile',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'max_size' => ['type' => 'text', 'label' => rex_i18n::msg('yform_mediafile_max_size')],
                'types' => ['type' => 'text', 'label' => rex_i18n::msg('yform_mediafile_types')],
                'required' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_mediafile_required')],
                'messages' => ['type' => 'text', 'label' => rex_i18n::msg('yform_mediafile_messages')],
                'no_db' => ['type' => 'no_db', 'label' => rex_i18n::msg('yform_values_defaults_table'), 'default' => 0],
                'category' => ['type' => 'text', 'label' => rex_i18n::msg('yform_mediafile_category')],
                'user' => ['type' => 'text', 'label' => rex_i18n::msg('yform_mediafile_user')],
                'notice' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_notice')]
            ],
            'description' => rex_i18n::msg('yform_mediafile_description'),
            'db_type' => ['text'],
            'multi_edit' => false
        ];
    }

    // Static methods remain unchanged
}
