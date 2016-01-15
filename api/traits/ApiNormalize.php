<?php
namespace api\traits;


trait ApiNormalize {

    /**
     * Метод убирает все ошибки для поля, кроме первой и возвращает значения в формате
     * [
     *     'field1' => 'first_error_of_field1',
     *     'field2' => 'first_error_of_field2',
     * ]
     *
     * @param array $errors
     * @return array
     */
    public function normalizeModelErrors(array $errors) {
        $normalized = [];
        foreach($errors as $attribute => $attrErrors) {
            $normalized[$attribute] = $attrErrors[0];
        }

        return $normalized;
    }

}
