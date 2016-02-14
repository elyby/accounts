<?php
namespace common\components\oauth\Exception;

class AccessDeniedException extends \League\OAuth2\Server\Exception\AccessDeniedException {

    public function __construct($redirectUri = null) {
        parent::__construct();
        $this->redirectUri = $redirectUri;
    }

}
