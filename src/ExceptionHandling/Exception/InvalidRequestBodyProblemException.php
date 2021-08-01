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

use Throwable;

final class InvalidRequestBodyProblemException extends ProblemException
{
    /**
     * @var array
     */
    private $violations;

    public function __construct(
        string $typeUri,
        string $title,
        int $statusCode,
        string $message = '',
        Throwable $previous = null,
        ?string $instanceUri = null,
        array $headers = [],
        array $violations = []
    ) {
        parent::__construct($typeUri, $title, $statusCode, $message, $previous, $instanceUri, $headers);

        $this->violations = $violations;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['violations'] = $this->getViolations();

        return $data;
    }
}
