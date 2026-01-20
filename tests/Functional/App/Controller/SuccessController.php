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

namespace Nijens\OpenapiBundle\Tests\Functional\App\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for functional testing that always returns a 200 OK response.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class SuccessController
{
    public function __invoke(): Response
    {
        return new Response('', Response::HTTP_OK);
    }
}
