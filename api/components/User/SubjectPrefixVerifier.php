<?php
namespace api\components\User;

use Emarref\Jwt\Claim\Subject;
use Emarref\Jwt\Exception\InvalidSubjectException;
use Emarref\Jwt\Token;
use Emarref\Jwt\Verification\VerifierInterface;
use yii\helpers\StringHelper;

class SubjectPrefixVerifier implements VerifierInterface {

    private $subjectPrefix;

    public function __construct(string $subjectPrefix) {
        $this->subjectPrefix = $subjectPrefix;
    }

    public function verify(Token $token): void {
        /** @var Subject $subjectClaim */
        $subjectClaim = $token->getPayload()->findClaimByName(Subject::NAME);
        $subject = ($subjectClaim === null) ? null : $subjectClaim->getValue();

        if (!StringHelper::startsWith($subject, $this->subjectPrefix)) {
            throw new InvalidSubjectException();
        }
    }

}
