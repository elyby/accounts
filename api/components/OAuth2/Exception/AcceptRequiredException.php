<?php
namespace api\components\OAuth2\Exceptions;

use League\OAuth2\Server\Exception\OAuthException;

class AcceptRequiredException extends OAuthException {

    public $httpStatusCode = 401;

    /**
     * {@inheritdoc}
     */
    public $errorType = 'accept_required';

    /**
     * {@inheritdoc}
     */
    public function __construct() {
        parent::__construct('Client must accept authentication request.');
    }

}
