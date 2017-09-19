<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\AccountActionForm;
use common\models\Account;
use Yii;
use yii\base\Action;
use yii\web\NotFoundHttpException;

abstract class BaseAccountAction extends Action {

    final public function run(int $id): array {
        $className = $this->getFormClassName();
        /** @var AccountActionForm $model */
        $model = new $className($this->findAccount($id));
        $model->load($this->getRequestData());
        if (!$model->performAction()) {
            return $this->formatFailedResult($model);
        }

        return $this->formatSuccessResult($model);
    }

    abstract protected function getFormClassName(): string;

    public function getRequestData(): array {
        return Yii::$app->request->post();
    }

    public function getSuccessResultData(AccountActionForm $model): array {
        return [];
    }

    public function getFailedResultData(AccountActionForm $model): array {
        return [];
    }

    private function formatFailedResult(AccountActionForm $model): array {
        $response = [
            'success' => false,
            'errors' => $model->getFirstErrors(),
        ];

        $data = $this->getFailedResultData($model);
        if (!empty($data)) {
            $response['data'] = $data;
        }

        return $response;
    }

    private function formatSuccessResult(AccountActionForm $model): array {
        $response = [
            'success' => true,
        ];
        $data = $this->getSuccessResultData($model);
        if (!empty($data)) {
            $response['data'] = $data;
        }

        return $response;
    }

    private function findAccount(int $id): Account {
        $account = Account::findOne($id);
        if ($account === null) {
            throw new NotFoundHttpException();
        }

        return $account;
    }

}
