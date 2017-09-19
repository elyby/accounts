<?php
namespace api\components\User;

use Emarref\Jwt\Claim\AbstractClaim;

class ScopesClaim extends AbstractClaim {

    const NAME = 'ely-scopes';

    /**
     * ScopesClaim constructor.
     *
     * @param array|string $value
     */
    public function __construct($value = null) {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        parent::__construct($value);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string {
        return self::NAME;
    }

}
