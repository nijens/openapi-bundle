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

namespace Nijens\OpenapiBundle\Tests\Functional\App\Model;

class Pet
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $status = 'available';

    /**
     * @var string[]
     */
    private $photoUrls;

    public function __construct(int $id, string $name, array $photoUrls = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->photoUrls = $photoUrls;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string[]
     */
    public function getPhotoUrls(): array
    {
        return $this->photoUrls;
    }

    public function getNotVisible(): bool
    {
        return false;
    }
}
