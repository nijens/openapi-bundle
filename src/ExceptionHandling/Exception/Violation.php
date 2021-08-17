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

/**
 * Default violation implementation as part of the {@see InvalidRequestBodyProblemException}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class Violation implements ViolationInterface
{
    /**
     * @var string
     */
    private $constraint;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string|null
     */
    private $propertyPath;

    public function __construct(string $constraint, string $message, ?string $propertyPath = null)
    {
        $this->constraint = $constraint;
        $this->message = $message;
        $this->propertyPath = $propertyPath;
    }

    public function getPropertyPath(): string
    {
        return $this->propertyPath;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getConstraint(): string
    {
        return $this->constraint;
    }

    public function jsonSerialize(): array
    {
        return [
            'constraint' => $this->getConstraint(),
            'message' => $this->getMessage(),
            'property' => $this->getPropertyPath(),
        ];
    }

    public static function fromArray(array $violation): self
    {
        return new static(
            $violation['constraint'] ?? '',
            $violation['message'] ?? '',
            $violation['property'] ?? $violation['propertyPath'] ?? null
        );
    }
}
