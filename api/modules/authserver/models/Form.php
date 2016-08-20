<?php
namespace api\modules\authserver\models;

use Yii;
use yii\base\Model;

abstract class Form extends Model {

    public function loadByGet() {
        return $this->load(Yii::$app->request->get());
    }

    public function loadByPost() {
        $data = Yii::$app->request->post();
        // TODO: проверить, парсит ли Yii2 raw body и что он делает, если там неспаршенный json
        /*if (empty($data)) {
            $data = $request->getJsonRawBody(true);
        }*/

        return $this->load($data);
    }

}
