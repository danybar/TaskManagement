<?php declare(strict_types=1);

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Users
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
    public string $username;

    /**
     * @ORM\Column(type="string")
     */
    public string $password;

    /**
     * @ORM\Column(type="string")
     */
    public string $email;

    /**
     * @ORM\Column(type="datetime")
     */
    public \DateTime $registerdate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public ?int $tenant_id = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    public ?string $avatar = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    public ?\DateTime $last_activity = null;

    public function __construct(
        string $username,
        string $password,
        string $email,
        \DateTime $registerdate = null,
        ?int $tenant_id = null,
        ?string $avatar = null,
        ?\DateTime $last_activity = null
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->email = $email;
        $this->registerdate = $registerdate !== null ? $registerdate : new \DateTime();
        $this->tenant_id = $tenant_id;
        $this->avatar = $avatar;
        $this->last_activity = $last_activity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getRegisterDate(): \DateTime
    {
        return $this->registerdate;
    }

    public function getTenantId(): ?int
    {
        return $this->tenant_id;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function getLastActivity(): ?\DateTime
    {
        return $this->last_activity;
    }
}
