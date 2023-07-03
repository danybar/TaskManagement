<?php declare(strict_types=1);

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Tasks
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public ?int $id = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public ?int $project_id;

    /**
     * @ORM\Column(type="string")
     */
    public string $title;

    /**
     * @ORM\Column(type="string")
     */
    public string $description;

    /**
     * @ORM\Column(type="string")
     */
    public string $status;

    /**
     * @ORM\Column(type="datetime")
     */
    public \DateTimeInterface $created_at;

    /**
     * @ORM\Column(type="datetime")
     */
    public \DateTimeInterface $due_date;

    /**
     * @ORM\Column(type="datetime")
     */
    public \DateTimeInterface $last_modified;

    /**
     * @ORM\Column(type="string")
     */
    public string $priority;

    /**
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumn(name="id_user", referencedColumnName="id")
     */
    public ?int $id_user = null;

    public function __construct(
        ?int $id = null,
        ?int $project_id = null,
        string $title,
        string $description,
        string $status,
        \DateTimeInterface $created_at,
        \DateTimeInterface $due_date,
        \DateTimeInterface $last_modified,
        string $priority,
        int $id_user,
    ) {
        $this->id = $id;
        $this->project_id = $project_id;
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->created_at = $created_at;
        $this->due_date = $due_date;
        $this->last_modified = $last_modified;
        $this->priority = $priority;
        $this->id_user = $id_user;
    }

    public static function create(?\Nette\Database\Table\ActiveRow $activeRow): ?self
    {
        if ($activeRow === null) {
            return null;
        }

        return new Tasks(
            $activeRow['id'],
            $activeRow['project_id'],
            $activeRow['title'],
            $activeRow['description'],
            $activeRow['status'],
            new \DateTimeImmutable($activeRow['created_at']),
            new \DateTimeImmutable($activeRow['due_date']),
            new \DateTimeImmutable($activeRow['last_modified']),
            $activeRow['priority']
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getProjectId(): ?int
    {
        return $this->project_id;
    }

    public function setProjectId(?int $project_id): void
    {
        $this->project_id = $project_id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getDueDate(): \DateTimeInterface
    {
        return $this->due_date;
    }

    public function setDueDate(\DateTimeInterface $due_date): void
    {
        $this->due_date = $due_date;
    }

    public function getLastModified(): \DateTimeInterface
    {
        return $this->last_modified;
    }

    public function setLastModified(\DateTimeInterface $last_modified): void
    {
        $this->last_modified = $last_modified;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): void
    {
        $this->priority = $priority;
    }

    public function getUserId(): int
    {
        return $this->id_user;
    }

    public function setUserId(int $id_user): self
    {
        $this->id_user = $id_user;
        return $this;
    }
}

