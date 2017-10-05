<?php
namespace common\models;

final class OauthOwnerType {

    /**
     * Используется для сессий, принадлежащих непосредственно пользователям account.ely.by,
     * выполнивших парольную авторизацию и использующих web интерфейс
     */
    public const ACCOUNT = 'accounts';

    /**
     * Используется когда пользователь по протоколу oAuth2 authorization_code
     * разрешает приложению получить доступ и выполнять действия от своего имени
     */
    public const USER = 'user';

    /**
     * Используется для авторизованных по протоколу oAuth2 client_credentials
     */
    public const CLIENT = 'client';

}
