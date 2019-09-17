<?php
declare(strict_types=1);

namespace api\exceptions;

/**
 * The exception can be used for cases where the outcome doesn't seem to be expected,
 * but can theoretically happen. The goal is to capture these areas and refine the logic
 * if such situations do occur.
 *
 * @deprecated use \Webmozart\Assert\Assert to ensure, that action has been successfully performed
 */
class ThisShouldNotHappenException extends Exception {

}
