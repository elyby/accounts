<?php
declare(strict_types=1);

namespace api\components\OAuth2\Utils;

class Scopes {

    /**
     * In the earlier versions of Accounts Ely.by backend we had a comma-separated scopes
     * list, while by OAuth2 standard it they should be separated by a space. Shit happens :)
     * So override scopes validation function to reformat passed value.
     *
     * @param string|array $scopes
     * @return string
     */
    public static function format($scopes): string {
        if ($scopes === null) {
            return '';
        }

        if (is_array($scopes)) {
            return implode(' ', $scopes);
        }

        return str_replace(',', ' ', $scopes);
    }

}
