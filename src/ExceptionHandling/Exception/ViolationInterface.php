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

use JsonSerializable;

/**
 * Interface defining a violation which led to the {@see InvalidRequestBodyProblemException}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface ViolationInterface extends JsonSerializable
{
    public function getPropertyPath(): ?string;

    public function getMessage(): string;

    public function getConstraint(): string;
}
