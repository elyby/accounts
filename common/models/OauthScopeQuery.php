<?php
namespace common\models;

use yii\helpers\ArrayHelper;

class OauthScopeQuery {

    private $scopes;

    private $internal;

    private $owner;

    public function __construct(array $scopes) {
        $this->scopes = $scopes;
    }

    public function onlyPublic(): self {
        $this->internal = false;
        return $this;
    }

    public function onlyInternal(): self {
        $this->internal = true;
        return $this;
    }

    public function usersScopes(): self {
        $this->owner = 'user';
        return $this;
    }

    public function machineScopes(): self {
        $this->owner = 'machine';
        return $this;
    }

    public function all(): array {
        return ArrayHelper::getColumn(array_filter($this->scopes, function($value) {
            $shouldCheckInternal = $this->internal !== null;
            $isInternalMatch = $value['internal'] === $this->internal;
            $shouldCheckOwner = $this->owner !== null;
            $isOwnerMatch = $value['owner'] === $this->owner;

            return (!$shouldCheckInternal || $isInternalMatch)
                && (!$shouldCheckOwner || $isOwnerMatch);
        }), 'value');
    }

}
