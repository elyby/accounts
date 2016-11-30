<?php
namespace api\components\OAuth2\Exception;

class AccessDeniedException extends \League\OAuth2\Server\Exception\AccessDeniedException {

    public function __construct($redirectUri = null) {
        parent::__construct();
        $this->redirectUri = $redirectUri;
    }

}
