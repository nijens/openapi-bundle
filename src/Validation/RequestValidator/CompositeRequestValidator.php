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

namespace Nijens\OpenapiBundle\Validation\RequestValidator;

use Nijens\OpenapiBundle\ExceptionHandling\Exception\RequestProblemExceptionInterface;
use Symfony\Component\HttpFoundation\Request;

final class CompositeRequestValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface[]
     */
    private $validators;

    /**
     * @param ValidatorInterface[] $validators
     */
    public function __construct(iterable $validators)
    {
        $this->validators = $validators;
    }

    public function validate(Request $request): ?RequestProblemExceptionInterface
    {
        foreach ($this->validators as $validator) {
            $exception = $validator->validate($request);
            if ($exception instanceof RequestProblemExceptionInterface) {
                return $exception;
            }
        }

        return null;
    }
}
