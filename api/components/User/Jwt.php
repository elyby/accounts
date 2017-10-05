<?php
namespace api\components\User;

use Emarref\Jwt\Verification\Context;
use Emarref\Jwt\Verification\SubjectVerifier;

class Jwt extends \Emarref\Jwt\Jwt {

    protected function getVerifiers(Context $context): array {
        $verifiers = parent::getVerifiers($context);
        foreach ($verifiers as $i => $verifier) {
            if (!$verifier instanceof SubjectVerifier) {
                continue;
            }

            $verifiers[$i] = new SubjectPrefixVerifier($context->getSubject());
            break;
        }

        return $verifiers;
    }

}
