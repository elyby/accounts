<?php
namespace api\request;

use Yii;
use yii\web\JsonParser;
use yii\web\RequestParserInterface;

/**
 * Since Yii2 doesn't provide an opportunity to make a fallback for an unparsed request,
 * the query parsing logic must be fully reimplemented.
 *
 * The code is taken from \yii\web\Request::getBodyParams() and reworked in such a way
 * that it tries to parse the request by the next steps:
 * - first check if PHP has managed to do it by itself, then we return its value;
 * - then we try to parse JSON, which is encoded in the body;
 * - if it doesn't work out, let's assume it's a PUT, DELETE or other request
 *   that PHP doesn't automatically overpower to parse, so we try to parse it ourselves.
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
