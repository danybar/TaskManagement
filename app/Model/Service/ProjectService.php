<?php declare(strict_types=1);

namespace App\Model\Service;
use App\Model\Entity\Projects;
use App\Model\Entity\Tasks;
use App\Model\Entity\Tenants;
use App\Model\Entity\Users;
use Nette\Security\User;
use Doctrine\ORM\EntityManagerInterface;

class ProjectService
{
    private User $user;
    private Projects $project;
    private Users $userId;
    private Tasks $task;
    private Tenants $tenants;

    public function __construct(User $user, private EntityManagerInterface $em)
    {
        $this->user = $user;
    }

    public function getTenantId()
    { 
        if ($this->user->isLoggedIn()) {
            $identity = $this->user->getIdentity();
            $tenantId = $identity->tenant_id;
            return $tenantId;
        }
    }

    public function getUserId()
{
    if ($this->user->isLoggedIn()) {
        $identity = $this->user->getIdentity();
        $userId = $identity->id;
        return $userId;
    }
}

    public function getProjectCountByTenantId()
    {   
        $repository = $this->em->getRepository(Projects::class);
        $count = $repository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.tenant_id = :tenant_id')
            ->setParameter('tenant_id', $this->getTenantId())
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function getProjectCompletedCountByTenantId()
    {
        $repository = $this->em->getRepository(Projects::class);
        $count = $repository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.tenant_id = :tenant_id')
            ->andWhere('p.status = :status')
            ->setParameter('tenant_id', $this->getTenantId())
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function getAllProjectsByTenantId(): array
    {
        $repository = $this->em->getRepository(Projects::class);
        $qb = $repository->createQueryBuilder('p');
        $qb->where('p.tenant_id = :tenantId')
        ->setParameter('tenantId', $this->getTenantId())
        ->orderBy('p.due_date', 'ASC');
        $projects = $qb->getQuery()->getResult();
        return $projects;
    }

    public function getProjectCompletedCountByUserId()
    {
        $repository = $this->em->getRepository(Projects::class);
        $count = $repository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.user_id = :user_id')
            ->andWhere('p.status = :status')
            ->setParameter('user_id', $this->getUserId())
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function getProjectCountByUserId()
    {
        $repository = $this->em->getRepository(Projects::class);
        $count = $repository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.user_id = :user_id')
            ->setParameter('user_id', $this->getUserId())
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function getProjectSuccessRateByUserId()
    {
        $completedCount = $this->getProjectCompletedCountByUserId();
        $totalCount = $this->getProjectCountByUserId();

        if ($totalCount > 0) {
            $successRate = ($completedCount / $totalCount) * 100;
            return round($successRate, 2);
        }

        return 0;
    }

    public function getProjectSuccessRateByTenantId(): float
    {
        $totalProjects = $this->getProjectCountByTenantId();
        $completedProjects = $this->getProjectCompletedCountByTenantId();

        if ($totalProjects > 0) {
            $successRate = ($completedProjects / $totalProjects) * 100;
            return round($successRate, 1);
        } else {
            return 0;
        }
    }

    public function getNotCompleteTaskCountByTenantId(): int
    {
        $repository = $this->em->getRepository(Tasks::class);
        $qb = $repository->createQueryBuilder('t');
        $qb->select('COUNT(t.id)')
            ->join('App\Model\Entity\Projects', 'p', 'WITH', 't.project_id = p.id')
            ->where('p.tenant_id = :tenantId')
            ->andWhere('t.status != :status')
            ->setParameter('tenantId', $this->getTenantId())
            ->setParameter('status', 'completed');
        $count = $qb->getQuery()->getSingleScalarResult();
        return $count;
    }

    public function getCompleteTaskCountByTenantId(): int
    {
        $repository = $this->em->getRepository(Tasks::class);
        $qb = $repository->createQueryBuilder('t');
        $qb->select('COUNT(t.id)')
            ->join('App\Model\Entity\Projects', 'p', 'WITH', 't.project_id = p.id')
            ->where('p.tenant_id = :tenantId')
            ->andWhere('t.status = :status')
            ->setParameter('tenantId', $this->getTenantId())
            ->setParameter('status', 'completed');
        $count = $qb->getQuery()->getSingleScalarResult();
        return $count;
    }

    public function getOldestRegistrationDateByTenantId(): ?\DateTime
    {
        $repository = $this->em->getRepository(Users::class);
        $qb = $repository->createQueryBuilder('u');
        $qb->select('MIN(u.registerdate)')
            ->where('u.tenant_id = :tenantId')
            ->setParameter('tenantId', $this->getTenantId());
        $oldestDate = $qb->getQuery()->getSingleScalarResult();

        if ($oldestDate) {
            return new \DateTime($oldestDate);
        } else {
            return null;
        }
    }

    public function getTotalUserCountByTenantId(): int
    {
        $repository = $this->em->getRepository(Users::class);
        $qb = $repository->createQueryBuilder('u');
        $qb->select('COUNT(u.id)')
            ->where('u.tenant_id = :tenantId')
            ->setParameter('tenantId', $this->getTenantId());
        $count = $qb->getQuery()->getSingleScalarResult();
        return $count;
    }

    public function getUsersByActiveTenantId(): array
    {
        $activeTenantId = $this->getTenantId(); 

        $repository = $this->em->getRepository(Users::class);
        $users = $repository->findBy(['tenant_id' => $activeTenantId]);

        return $users;
    }

    public function getOrganizationNameByTenantId(): ?string
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('t.name')
            ->from(Tenants::class, 't')
            ->where('t.id = :tenantId')
            ->setParameter('tenantId', $this->getTenantId());

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getTaskCompletionRateByProjectId(int $id): float
    {
        $repository = $this->em->getRepository(Tasks::class);
        
    
        $qbTotal = $repository->createQueryBuilder('t');
        $qbTotal->select('COUNT(t.id)')
                ->where('t.project_id = :projectId')
                ->setParameter('projectId', $id);
        $totalTasks = $qbTotal->getQuery()->getSingleScalarResult();
        
    
        $qbNotCompleted = $repository->createQueryBuilder('t');
        $qbNotCompleted->select('COUNT(t.id)')
                    ->where('t.project_id = :projectId')
                    ->andWhere('t.status != :status')
                    ->setParameter('projectId', $id)
                    ->setParameter('status', 'Completed');
        $notCompletedTasks = $qbNotCompleted->getQuery()->getSingleScalarResult();

    
        if ($totalTasks === 0) {
            return 0.0;
        }
        
        $completedTasks = $totalTasks - $notCompletedTasks;
        $completionRate = ($completedTasks / $totalTasks) * 100;
        
        return round($completionRate, 0);
    }

    public function getAllTasksByProjectId(int $id): array
    {
        $repository = $this->em->getRepository(Tasks::class);
        $qb = $repository->createQueryBuilder('t');
        $qb->where('t.project_id = :projectId')
            ->setParameter('projectId', $id)
            ->orderBy('t.due_date', 'ASC');
        $tasks = $qb->getQuery()->getResult();
        return $tasks;
    }

    public function deleteProjectById($id) {
        $project = $this->em->getRepository(Projects::class)->find($id);
    
        if (!$project) {
            throw $this->createNotFoundException(
                'Nebyl nalezen žádný projekt s ID '.$id
            );
        }
        
        $tasks = $this->em->getRepository(Tasks::class)->findBy(['project_id' => $id]);
    
        foreach ($tasks as $task) {
            $this->em->remove($task);
        }
    
        $this->em->remove($project);
        $this->em->flush();
    }


    public function getProjectsByUserId(int $userId): array
    {
        $repository = $this->em->getRepository(Projects::class);
        $qb = $repository->createQueryBuilder('p');
        $qb->where('p.user_id = :userId')
            ->setParameter('userId', $userId);

        $projects = $qb->getQuery()->getResult();
        return $projects;
    }

    public function getTasksByUserId(int $userId): array
    {
        $repository = $this->em->getRepository(Tasks::class);
        $qb = $repository->createQueryBuilder('t');
        $qb->where('t.id_user = :id_user')
            ->setParameter('id_user', $userId);

        $tasks = $qb->getQuery()->getResult();
        return $tasks;
    }




}