<?php
namespace api\request;

use Yii;
use yii\web\JsonParser;
use yii\web\RequestParserInterface;

/**
 * Т.к. Yii2 не предоставляет возможности сделать fallback для неспаршенного
 * request, нужно полностью реимплементировать логику парсинга запроса.
 *
 * Код взят из \yii\web\Request::getBodyParams() и вывернут таким образом,
 * чтобы по нисходящей пытаться спарсить запрос:
 * - сначала проверяем, если PHP справился сам, то возвращаем его значение
 * - дальше пробуем спарсить JSON, который закодирован в теле
 * - если не вышло, то предположим, что это PUT, DELETE или иной другой запрос,
 *   который PHP автоматически не осиливает спарсить, так что пытаемся его спарсить
 *   самостоятельно
 */
class RequestParser implements RequestParserInterface {

    public function parse($rawBody, $contentType) {
        if (!empty($_POST)) {
            return $_POST;
        }

        /** @var JsonParser $parser */
        $parser = Yii::createObject(JsonParser::class);
        $parser->throwException = false;
        $result = $parser->parse($rawBody, $contentType);
        if (!empty($result)) {
            return $result;
        }

        mb_parse_str($rawBody, $bodyParams);
        if (!empty($bodyParams)) {
            return $bodyParams;
        }

        return [];
    }

}
