<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_mediafile extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if (!is_string($this->getValue())) {
            $this->setValue('');
        }

        $media_category_id = ('' == $this->getElement(8)) ? 0 : (int) $this->getElement('category');
        $media_category = rex_media_category::get($media_category_id);
        if (null === $media_category) {
            $media_category_id = 0;
        }

        $mediapool_user = ('' == $this->getElement('user')) ? 'yform::mediafile' : $this->getElement('user');
        $pool = $this->params['value_pool']['email'];
        $mediapool_user = preg_replace_callback(
            '/###(\w+)###/',
            static function ($m) use ($pool) {
                return $pool[$m[1]]
                    ?? 'key not found';
            },
            $mediapool_user
        );

        $sizes = array_map('intval', explode(',', $this->getElement('max_size')));
        $minsize = count($sizes) > 1 ? (int) ($sizes[0] * 1024) : 0;
        $maxsize = count($sizes) > 1 ? (int) ($sizes[1] * 1024) : (int) ($sizes[0] * 1024);

        $warnings = [];
        $err_msgs = explode(',', $this->getElement('messages')); // min_err,max_err,type_err,empty_err
        $err_msgs['min_err'] = $err_msgs[0];
        $err_msgs['max_err'] = $err_msgs[1] ?? $err_msgs[0];
        $err_msgs['type_err'] = $err_msgs[2] ?? $err_msgs[0];
        $err_msgs['empty_err'] = $err_msgs[3] ?? $err_msgs[0];

        $rdelete = md5($this->getFieldName('delete'));
        $rfile = 'file_' . md5($this->getFieldName('file'));

        if (!$this->isEditable()) {
            unset($_FILES[$rfile]);
        }

        // SIZE CHECK
        if ($this->params['send'] && isset($_FILES[$rfile]) && '' != $_FILES[$rfile]['name'] && ($_FILES[$rfile]['size'] > $maxsize || $_FILES[$rfile]['size'] < $minsize)) {
            if ($_FILES[$rfile]['size'] < $minsize) {
                $warnings[] = $err_msgs['min_err'];
            }
            if ($_FILES[$rfile]['size'] > $maxsize) {
                $warnings[] = $err_msgs['max_err'];
            }
            unset($_FILES[$rfile]);
            $this->setValue('');
        }

        if ($this->params['send']) {
            if (isset($_REQUEST[$rdelete]) && 1 == $_REQUEST[$rdelete]) {
                $this->setValue('');
            }

            if (isset($_FILES[$rfile]) && '' != $_FILES[$rfile]['name']) {
                $file = $_FILES[$rfile];

                $data = [];
                $data['title'] = (string) ($file['name'] ?? '');
                $data['category_id'] = (int) $media_category_id;
                $data['file']['name'] = (string) ($file['name'] ?? '');
                $data['file']['tmp_path'] = (string) ($file['tmp_name'] ?? '');
                $data['file']['path'] = (string) ($file['tmp_name'] ?? '');

                // TODO: im Frontend noch prüfen. Mediafile nicht ersetzen wenn md5 identisch
                $doSubindexing = true;

                // TODO: wenn validierungs abbruch, noch nicht speichern

                $allowedExtensions = [];
                if ('' != $this->getElement('types')) {
                    $allowedExtensions['types'] = $this->getElement('types');
                }
                $warnings = [];

                try {
                    $return = rex_media_service::addMedia($data, $doSubindexing, $allowedExtensions);
                    if (1 == $return['ok']) {
                        $this->setValue($return['filename']);
                    } else {
                        $warnings = $return['messages'];
                        $this->setValue('');
                    }
                } catch (rex_api_exception $e) {
                    $warnings[] = $e->getMessage();
                    $this->setValue('');
                }
            }
        }

        if ($this->params['send']) {
            $this->params['value_pool']['email'][$this->getElement('name')] = $this->getValue();
            if ($this->saveInDb()) {
                $this->params['value_pool']['sql'][$this->getElement('name')] = $this->getValue();
            }
        }

        //# check for required file
        if ($this->params['send'] && 1 == $this->getElement('required') && '' == $this->getValue()) {
            $warnings[] = $err_msgs['empty_err'];
        }

        if ($this->params['send'] && count($warnings) > 0) {
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = implode(', ', $warnings);
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.mediafile-view.tpl.php', 'value.view.tpl.php']);
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('value.mediafile.tpl.php');
            }
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
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'max_size' => ['type' => 'text',    'label' => rex_i18n::msg('yform_mediafile_max_size')],
                'types' => ['type' => 'text',    'label' => rex_i18n::msg('yform_mediafile_types')],
                'required' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_mediafile_required')],
                'messages' => ['type' => 'text',    'label' => rex_i18n::msg('yform_mediafile_messages')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'category' => ['type' => 'text',    'label' => rex_i18n::msg('yform_mediafile_category')],
                'user' => ['type' => 'text',    'label' => rex_i18n::msg('yform_mediafile_user')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_mediafile_description'),
            'db_type' => ['text'],
            'multi_edit' => false,
        ];
    }

    public static function getSearchField($params)
    {
        rex_yform_value_text::getSearchField($params);
    }

    public static function getSearchFilter($params)
    {
        return rex_yform_value_text::getSearchFilter($params);
    }

    public static function getListValue($params)
    {
        return rex_yform_value_text::getListValue($params);
    }

    public static function isMediaInUse(\rex_extension_point $ep)
    {
        $params = $ep->getParams();
        $warning = $ep->getSubject();

        $sql = \rex_sql::factory();
        $sql->setQuery('SELECT * FROM `' . \rex_yform_manager_field::table() . '` LIMIT 0');

        $columns = $sql->getFieldnames();
        $select = in_array('multiple', $columns) ? ', `multiple`' : '';

        $fields = $sql->getArray('SELECT `table_name`, `name`' . $select . ' FROM `' . \rex_yform_manager_field::table() . '` WHERE `type_id`="value" AND `type_name` IN("mediafile","textarea")');
        $fields = \rex_extension::registerPoint(new \rex_extension_point('YFORM_MEDIA_IS_IN_USE', $fields));

        if (!count($fields)) {
            return $warning;
        }

        $tables = [];
        $escapedFilename = $sql->escape('%' . $params['filename'] . '%');
        foreach ($fields as $field) {
            $tableName = $field['table_name'];
            $condition = $sql->escapeIdentifier($field['name']) . ' LIKE ' . $escapedFilename;

            if (isset($field['multiple']) && $field['multiple'] == 1) {
                $condition = 'FIND_IN_SET(' . $escapedFilename . ', ' . $sql->escapeIdentifier($field['name']) . ')';
            }
            $tables[$tableName][] = $condition;
        }

        $messages = '';
        foreach ($tables as $tableName => $conditions) {
            $items = $sql->getArray('SELECT `id` FROM ' . $tableName . ' WHERE ' . implode(' OR ', $conditions));
            if (count($items)) {
                foreach ($items as $item) {
                    $sqlData = \rex_sql::factory();
                    $sqlData->setQuery('SELECT `name` FROM `' . \rex_yform_manager_table::table() . '` WHERE `table_name` = "' . $tableName . '"');

                    // Generiere CSRF-Token für die jeweilige Tabelle
                    $table = rex_yform_manager_table::get($tableName);
                    if ($table) {
                        $_csrf_key = $table->getCSRFKey();
                        $_csrf_params = rex_csrf_token::factory($_csrf_key)->getUrlParams();
                        $token = $_csrf_params['_csrf_token'];

                        // Erstelle den Link unter Verwendung von rex_url::backend()
                        $editLink = rex_url::backendController([
                            'page' => 'yform/manager/data_edit',
                            'table_name' => $tableName,
                            'data_id' => $item['id'],
                            'func' => 'edit',
                            '_csrf_token' => $token
                        ], false);

                        $messages .= '<li><a href="javascript:openPage(\'' . $editLink . '\')">' . $sqlData->getValue('name') . ' [id=' . $item['id'] . ']</a></li>';
                    }
                }
            }
        }

        if ($messages != '') {
            $warning[] = '<ul>' . $messages . '</ul>';
        }

        return $warning;
    }
}
