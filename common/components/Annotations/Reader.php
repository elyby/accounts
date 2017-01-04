<?php
namespace common\components\Annotations;

class Reader extends \Minime\Annotations\Reader {

    /**
     * Поначаду я думал кэшировать эту штуку, но потом забил, т.к. всё всё равно завернул
     * в Yii::$app->cache и как-то надобность в отдельном кэше отпала, так что пока забьём
     * и оставим как заготовку на будущее
     *
     * @return \Minime\Annotations\Interfaces\ReaderInterface
     */
    public static function createFromDefaults() {
        return parent::createFromDefaults();
        //return new self(new \Minime\Annotations\Parser(), new RedisCache());
    }

}
