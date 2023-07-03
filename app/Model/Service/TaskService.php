<?php declare(strict_types=1);

namespace App\Model\Service;
use App\Model\Entity\Projects;
use App\Model\Entity\Tasks;
use App\Model\Entity\Tenants;
use App\Model\Entity\Users;
use Nette\Security\User;
use Doctrine\ORM\EntityManagerInterface;

class TaskService
{
    private User $user;
    private Projects $project;
    private Users $userId;
    private Tasks $task;
    private Tenants $tenants;

    private EntityManagerInterface $entityManager;

    public function __construct(User $user, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    public function deleteTaskById($taskId) {
        
        $task = $this->entityManager->getRepository(Tasks::class)->find($taskId);
    
        if (!$task) {
            throw $this->createNotFoundException(
                'Nebyl nalezen žádný task '.$taskId
            );
        }
      
        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }

}