<?php

declare(strict_types=1);

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\ExceptionHandling\Exception;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Default implementation for an RFC 7807 problem JSON object response.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class ProblemException extends Exception implements ProblemExceptionInterface
{
    public const DEFAULT_TYPE_URI = 'about:blank';

    public const DEFAULT_TITLE = 'An error occurred.';

    /**
     * @var string
     */
    private $typeUri;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string|null
     */
    private $instanceUri;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var array<string, string>
     */
    private $headers;

    public function __construct(
        string $typeUri,
        string $title,
        int $statusCode,
        string $message = '',
        Throwable $previous = null,
        ?string $instanceUri = null,
        array $headers = []
    ) {
        parent::__construct($message, 0, $previous);

        $this->typeUri = $typeUri;
        $this->title = $title;
        $this->instanceUri = $instanceUri;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function getTypeUri(): string
    {
        return $this->typeUri;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDetail(): ?string
    {
        return $this->getMessage();
    }

    public function getInstanceUri(): ?string
    {
        return $this->instanceUri;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function withTypeUri(string $typeUri): ProblemExceptionInterface
    {
        $exception = $this->clone();
        $exception->typeUri = $typeUri;

        return $exception;
    }

    public function withTitle(string $title): ProblemExceptionInterface
    {
        $exception = $this->clone();
        $exception->title = $title;

        return $exception;
    }

    public function withInstanceUri(string $instanceUri): ProblemExceptionInterface
    {
        $exception = $this->clone();
        $exception->instanceUri = $instanceUri;

        return $exception;
    }

    public function withStatusCode(int $statusCode): ProblemExceptionInterface
    {
        $exception = $this->clone();
        $exception->statusCode = $statusCode;

        return $exception;
    }

    public function withHeaders(array $headers): ProblemExceptionInterface
    {
        $exception = $this->clone();
        $exception->headers = $headers;

        return $exception;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->getTypeUri(),
            'title' => $this->getTitle(),
            'status' => $this->getStatusCode(),
            'detail' => $this->getDetail(),
            'instance' => $this->getInstanceUri(),
        ];
    }

    public static function fromHttpException(
        HttpExceptionInterface $exception,
        ?int $statusCode = null,
        string $typeUri = self::DEFAULT_TYPE_URI,
        string $title = self::DEFAULT_TITLE,
        string $instanceUri = null
    ): self {
        if ($statusCode === null) {
            $statusCode = $exception->getStatusCode();
        }

        return new static(
            $typeUri,
            $title,
            $statusCode,
            $exception->getMessage(),
            $exception,
            $instanceUri,
            $exception->getHeaders()
        );
    }

    public static function fromThrowable(
        Throwable $throwable,
        int $statusCode = 500,
        string $typeUri = self::DEFAULT_TYPE_URI,
        string $title = self::DEFAULT_TITLE,
        string $instanceUri = null
    ): self {
        return new static($typeUri, $title, $statusCode, $throwable->getMessage(), $throwable, $instanceUri);
    }

    /**
     * @return self
     */
    protected function clone()
    {
        return new static(
            $this->getTypeUri(),
            $this->getTitle(),
            $this->getStatusCode(),
            $this->getMessage(),
            $this->getPrevious(),
            $this->getInstanceUri(),
            $this->getHeaders()
        );
    }
}
