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
use Throwable;

/**
 * The interface describing the information required for an RFC 7807 problem JSON object response.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc7807#section-3
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface ProblemExceptionInterface extends Throwable, JsonSerializable
{
    public function getTypeUri(): string;

    public function getTitle(): string;

    public function getDetail(): ?string;

    public function getInstanceUri(): ?string;

    public function getStatusCode(): int;

    public function getHeaders(): array;
}
