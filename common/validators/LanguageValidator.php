<?php
namespace common\validators;

use Yii;
use yii\validators\Validator;

class LanguageValidator extends Validator {

    public $message = 'error.unsupported_language';

    protected function validateValue($value) {
        if (empty($value)) {
            return null;
        }

        $files = $this->getFilesNames();
        if (in_array($value, $files)) {
            return null;
        }

        return [$this->message, []];
    }

    protected function getFilesNames() {
        $files = array_values(array_filter(scandir($this->getFolderPath()), function(&$value) {
            return $value !== '..' && $value !== '.';
        }));

        return array_map(function($value) {
            return basename($value, '.json');
        }, $files);
    }

    protected function getFolderPath() {
        return Yii::getAlias('@frontend/src/i18n');
    }

}
