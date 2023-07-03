<?php declare(strict_types=1);

namespace App\Model\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Tenants
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public ?int $id = null;

    /**
     * @ORM\Column(type="string")
     */
    public string $name;

    /**
     * @ORM\Column(type="datetime")
     */
    public \DateTimeInterface $created_at;

    /**
     * @ORM\Column(type="string")
     */
    public string $code;

    public function __construct(
        string $name,
        string $created_at,
        string $code
    ) {
        $this->name = $name;
        $this->created_at = new DateTime($created_at);
        $this->code = $code;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
