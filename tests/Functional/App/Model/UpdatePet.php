<?php

declare(strict_types=1);

namespace Nijens\OpenapiBundle\Tests\Functional\App\Model;

class UpdatePet
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

    public function __construct(string $name, array $photoUrls = [])
    {
        $this->name = $name;
        $this->photoUrls = $photoUrls;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
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
}
