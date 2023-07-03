<?php declare(strict_types=1);

namespace App\Model\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Projects
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public ?int $id = null;

    /**
     * @ORM\Column(type="integer")
     */
    public int $user_id;

    /**
     * @ORM\Column(type="string")
     */
    public string $name;

    /**
     * @ORM\Column(type="string")
     */
    public string $description;

    /**
     * @ORM\Column(type="datetime")
     */
    public \DateTimeInterface $created_at;

    /**
     * @ORM\Column(type="datetime")
     */
    public \DateTimeInterface $due_date;

    /**
     * @ORM\Column(type="string")
     */
    public string $status;

    /**
     * @ORM\Column(type="integer")
     */

      /**
     * @ORM\ManyToMany(targetEntity="Users")
     * @ORM\JoinTable(name="project_user",
     *      joinColumns={@ORM\JoinColumn(name="project_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private Collection $users;
    public int $tenant_id;

    public function __construct(
        int $user_id,
        string $name,
        string $description,
        \DateTimeInterface $created_at,
        \DateTimeInterface $due_date,
        string $status,
        int $tenant_id
    ) {
        $this->users = new ArrayCollection();
        $this->user_id = $user_id;
        $this->name = $name;
        $this->description = $description;
        $this->created_at = $created_at;
        $this->due_date = $due_date;
        $this->status = $status;
        $this->tenant_id = $tenant_id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $userId): self
    {
        $this->user_id = $userId;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->created_at = $createdAt;
        return $this;
    }

    public function getDueDate(): \DateTimeInterface
    {
        return $this->due_date;
    }

    public function setDueDate(\DateTimeInterface $dueDate): self
    {
        $this->due_date = $dueDate;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getTenantId(): int
    {
        return $this->tenant_id;
    }

    public function setTenantId(int $tenantId): self
    {
        $this->tenant_id = $tenantId;
        return $this;
    }
}
