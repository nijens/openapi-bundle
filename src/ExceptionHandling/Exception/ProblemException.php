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
        string $typeUri = 'about:blank',
        string $title = 'An error occurred.',
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
        string $typeUri = 'about:blank',
        string $title = 'An error occurred.',
        string $instanceUri = null
    ): self {
        return new static($typeUri, $title, $statusCode, $throwable->getMessage(), $throwable, $instanceUri);
    }
}
