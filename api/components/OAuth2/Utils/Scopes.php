<?php
namespace api\components\OAuth2\Utils;

class Scopes {

    /**
     * По стандарту OAuth2 scopes должны разделяться пробелом, а не запятой. Косяк.
     * Так что оборачиваем функцию разбора скоупов, заменяя запятые на пробелы.
     * Заодно учитываем возможность передать скоупы в виде массива.
     *
     * @param string|array $scopes
     *
     * @return string
     */
    public static function format($scopes): string {
        if ($scopes === null) {
            return '';
        }

        if (is_array($scopes)) {
            return implode(' ', $scopes);
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return str_replace(',', ' ', $scopes);
    }

}
