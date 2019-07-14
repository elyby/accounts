<?php
namespace common\models;

final class OauthOwnerType {

    /**
     * Used for sessions belonging directly to account.ely.by users
     * who have performed password authentication and are using the web interface
     */
    public const ACCOUNT = 'accounts';

    /**
     * Used when a user uses OAuth2 authorization_code protocol to allow an application
     * to access and perform actions on its own behalf
     */
    public const USER = 'user';

    /**
     * Used for clients authorized via OAuth2 client_credentials protocol
     */
    public const CLIENT = 'client';

}
