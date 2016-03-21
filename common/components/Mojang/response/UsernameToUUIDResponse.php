<?php
namespace common\components\Mojang\response;

/**
 * http://wiki.vg/Mojang_API#Username_-.3E_UUID_at_time
 */
class UsernameToUUIDResponse {

    /**
     * @var string uuid пользователя без разделения на дефисы
     */
    public $id;

    /**
     * @var string ник пользователя в настоящем времени
     */
    public $name;

    /**
     * @var bool если имеет значение true, то значит аккаунт не мигрирован в Mojang аккаунт
     */
    public $legacy = false;

    /**
     * @var bool будет иметь значение true, если аккаунт находится в демо-режиме (не приобретена лицензия)
     */
    public $demo = false;

}
