<?php
namespace api\modules\authserver\models;

use Yii;
use yii\base\Model;

abstract class Form extends Model {

    public function formName() {
        return '';
    }

    public function loadByGet() {
        return $this->load(Yii::$app->request->get());
    }

    public function loadByPost() {
        $data = Yii::$app->request->post();
        if (empty($data)) {
            // TODO: помнится у Yii2 есть механизм парсинга данных входящего запроса. Лучше будет сделать это там
            $data = json_decode(Yii::$app->request->getRawBody(), true);
        }

        return $this->load($data);
    }

}
