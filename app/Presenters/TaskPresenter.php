<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Entity\Tasks;
use App\Model\Entity\Users;
use Nette;
use Nette\Application\UI\Form;
use Doctrine\ORM\EntityManagerInterface;
use App\Model\Service\UserService;
use App\Model\Service\ProjectService;
use App\Model\Service\TaskService;


class TaskPresenter extends Nette\Application\UI\Presenter
{
    private $entityManager;

    private TaskService $taskService;

    public function __construct(EntityManagerInterface $entityManager, private UserService $userService, private ProjectService $projectService, TaskService $taskService)
    {
        $this->entityManager = $entityManager;
        $this->taskService = $taskService;
    }

    public function getUserNameById(int $userId): ?string
    {
        return $this->userService->getUserNameById($userId);
    }

    public function getAvatarById(int $userId): ?string
    {
        return $this->userService->getAvatarById($userId);
    }

    public function renderEdit($project_id, $task_id)
    {
        $this->template->accountName = $this->getUserNameById($this->getUser()->getId());
        $this->template->projectId = $project_id; 
        $this->template->avatar = $this->getAvatarById($this->getUser()->getId());
    }

    public function renderProfile($project_id, $task_id)
    {
        $this->template->accountName = $this->getUserNameById($this->getUser()->getId());

        $projectRepository = $this->entityManager->getRepository(Tasks::class);
        $task = $projectRepository->find((int)$task_id);
        
        $this->template->task = $task;
        $this->template->projectId = $project_id; 
        $this->template->avatar = $this->getAvatarById($this->getUser()->getId());
        
    }

    public function renderUser()
    {

        $this->template->addFilter('getUserNameById', [$this, 'getUserNameById']);
		$this->template->addFilter('getAvatarById', [$this, 'getAvatarById']);

        $this->template->accountName = $this->getUserNameById($this->getUser()->getId());

        $userId = $this->getUser()->getId();

        $task = $this->projectService->getTasksByUserId($userId);

        $this->template->tasks = $task;
        $this->template->avatar = $this->getAvatarById($this->getUser()->getId());

    }


    protected function createComponentTaskForm(): Form
    {
        $projectId = $this->getParameter('project_id');
    
        $form = new Form;

        $form->addHidden('project_id')
        ->setDefaultValue(((int)$projectId));

        $form->addText('name', 'Jméno projektu')
            ->setRequired('Zadejte jméno projektu.');

        $form->addTextArea('description', 'Obsah projektu');

        $form->addText('due_date', 'Datum splatnosti')
            ->setRequired('Zadejte datum splatnosti.');

        $form->addSelect('status', 'Stav', [
            'Not Started' => 'Not Started',
            'In Progress' => 'In Progress',
            'Completed' => 'Completed',
        ])->setRequired('Zvolte stav.');

        $form->addSelect('priority', 'Priorita:', ['high' => 'high', 'medium' => 'medium', 'low' => 'low'])
        ->setDefaultValue('high')
        ->setRequired('Vyberte prioritu.');

        $users = $this->entityManager->getRepository(Users::class)->findBy(['tenant_id' => $this->projectService->getTenantId()]);
        $userChoices = [];
        foreach ($users as $user) {
            $userChoices[$user->getId()] = $user->getUsername();
        }
    
        $this->template->users = $users;
    
        $form->addSelect('id_user', 'Zodpovědná osoba', $userChoices)
            ->setRequired('Vyberte zodpovědnou osobu.');

        $form->addSubmit('save', 'Uložit změny');

        $form->onSuccess[] = [$this, 'taskFormSucceeded'];

        $taskId = $this->getParameter('task_id');

        if ($taskId) {
            $task = $this->entityManager->getRepository(Tasks::class)->find($taskId);
            if ($task) {
                $form->setDefaults([
                    'name' => $task->getTitle(),
                    'project_id' => $task->getProjectId(),
                    'description' => $task->getDescription(),
                    'due_date' => $task->getDueDate()->format('Y-m-d'),
                    'status' => $task->getStatus(),
                    'priority' => $task->getPriority(),
                    'id_user' => $task->getUserId()
                ]);
            }
        }

        return $form;
    }

    public function taskFormSucceeded(Form $form, \stdClass $values): void
{
    $taskId = $this->getParameter('task_id');

    $createdAt = new \DateTime();
    $dueDate = new \DateTime($values->due_date); 
    $lastModified = new \DateTime(); 

    $project_id = $values->project_id !== null ? (int) $values->project_id : null;

    if ($taskId) {
        $task = $this->entityManager->getRepository(Tasks::class)->find($taskId);
    } else {
        $task = new Tasks(
            null, 
            $project_id, 
            $values->name, 
            $values->description, 
            $values->status,
            $createdAt, 
            $dueDate, 
            $lastModified,
            $values->priority,
            $values->id_user
        );
    
        $this->entityManager->persist($task);
    }

    $task->setTitle($values->name);
    $task->setDescription($values->description);
    $task->setDueDate($dueDate);
    $task->setStatus($values->status);
    $task->setPriority($values->priority);
    $task->setLastModified($lastModified);
    $task->setUserId($values->id_user);

    $this->entityManager->flush();

    $this->flashMessage('Úkol byl úspěšně uložen.', 'success');

    $this->redirect('Task:edit', [
        'project_id' => $task->getProjectId(),
        'task_id' => $task->getId(),
    ]);

}


}
