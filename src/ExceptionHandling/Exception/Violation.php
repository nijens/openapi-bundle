<?php

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
 * Default violation implementation as part of {@see InvalidRequestBodyProblemException}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class Violation implements ViolationInterface
{
    /**
     * @var string
     */
    private $propertyPath;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $constraint;

    public function __construct(string $propertyPath, string $message, string $constraint)
    {
        $this->propertyPath = $propertyPath;
        $this->message = $message;
        $this->constraint = $constraint;
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
            'property' => $this->getPropertyPath(),
            'message' => $this->getMessage(),
            'constraint' => $this->getConstraint(),
        ];
    }

    public static function fromArray(array $violation): self
    {
        return new static(
            $violation['property'] ?? $violation['propertyPath'] ?? '',
            $violation['message'] ?? '',
            $violation['constraint'] ?? ''
        );
    }
}
