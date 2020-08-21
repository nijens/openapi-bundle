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

namespace Nijens\OpenapiBundle\Json\Exception;

use RuntimeException;

/**
 * Thrown when the JSON pointer does not exist.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class InvalidJsonPointerException extends RuntimeException
{
}
