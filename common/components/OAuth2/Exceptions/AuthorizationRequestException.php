<?php
declare(strict_types=1);

namespace common\components\OAuth2\Exceptions;

use League\OAuth2\Server\Exception\OAuthServerException;
use Throwable;

/**
 * The library's exceptions keep the details about the error only within the hint text: the invalid parameter
 * isn't stored at all, and the message is the general description of the error type.
 * This exception keeps the parameter, that has caused the error, separately and the message describes
 * the specific error, so both of them can be passed to the frontend as is.
 *
 * The library's constructor is final, so the instances are created only via the static methods.
 */
final class AuthorizationRequestException extends OAuthServerException {

    private ?string $parameter = null;

    public function getParameter(): ?string {
        return $this->parameter;
    }

    public static function create(
        string $errorType,
        string $description,
        ?string $parameter = null,
        int $httpStatusCode = 400,
        ?string $redirectUri = null,
        ?Throwable $previous = null,
    ): self {
        $exception = new self($description, 0, $errorType, $httpStatusCode, null, $redirectUri, $previous);
        $exception->parameter = $parameter;

        return $exception;
    }

    public static function invalidParameter(string $parameter, string $description, ?string $redirectUri = null, ?Throwable $previous = null): self {
        return self::create('invalid_request', $description, $parameter, 400, $redirectUri, $previous);
    }

    /**
     * Converts the library's exception, keeping its error type, status code and the redirect uri
     * unless the new one is passed.
     */
    public static function fromLibraryException(OAuthServerException $e, ?string $parameter = null, ?string $redirectUri = null): self {
        return self::create(
            $e->getErrorType(),
            self::describe($e),
            $parameter,
            $e->getHttpStatusCode(),
            $redirectUri ?? $e->getRedirectUri(),
            $e,
        );
    }

    /**
     * Builds the error description for any OAuth exception. The library's hint is more specific than the message,
     * but only its default form ("Check the `X` parameter") needs the message to be understandable.
     *
     * The result fits the error_description charset (RFC 6749 section 4.1.2.1), since the hint may contain
     * the value passed by the client (e.g. the scope name).
     */
    public static function describe(OAuthServerException $e): string {
        $message = $e->getMessage();
        $hint = (string)$e->getHint();
        if (!$e instanceof self && $hint !== '') {
            $message = str_starts_with($hint, 'Check the ') ? rtrim($message, '.') . '. ' . $hint : $hint;
        }

        return (string)preg_replace('/[^\x20\x21\x23-\x5B\x5D-\x7E]/', '?', $message);
    }

}
