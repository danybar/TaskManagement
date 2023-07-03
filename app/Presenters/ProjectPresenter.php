<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Entity\Projects;
use App\Model\Entity\Users;
use Nette;
use Nette\Application\UI\Form;
use Doctrine\ORM\EntityManagerInterface;
use App\Model\Service\UserService;
use App\Model\Service\ProjectService;
use App\Model\Service\TaskService;

class ProjectPresenter extends Nette\Application\UI\Presenter
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

    public function renderEdit($id)
    {
        $this->template->accountName = $this->getUserNameById($this->getUser()->getId());
        $this->template->projectId = $id; 
        $this->template->avatar = $this->getAvatarById($this->getUser()->getId());
    }

    public function renderProfile($id)
    {
        $this->template->accountName = $this->getUserNameById($this->getUser()->getId());

        $this->template->avatar = $this->getAvatarById($this->getUser()->getId());
       
        $projectRepository = $this->entityManager->getRepository(Projects::class);
        $project = $projectRepository->find((int)$id);

        if (!$project) {
            $this->error('Projekt nebyl nalezen');
        }

        $tasks = $this->projectService->getAllTasksByProjectId((int)$id);

        $this->template->project = $project;
        $this->template->tasks = $tasks;
    }

    public function renderTask($id)
    {
        $this->template->accountName = $this->getUserNameById($this->getUser()->getId());
        $this->template->projectId = $id;
        $this->template->avatar = $this->getAvatarById($this->getUser()->getId());

        $this->template->addFilter('getAvatarById', [$this, 'getAvatarById']);
        $this->template->addFilter('getUserNameById', [$this, 'getUserNameById']);
       
        $projectRepository = $this->entityManager->getRepository(Projects::class);
        $project = $projectRepository->find((int)$id);

        if (!$project) {
            $this->error('Projekt nebyl nalezen');
        }

        $tasks = $this->projectService->getAllTasksByProjectId((int)$id);

        $this->template->project = $project;
        $this->template->tasks = $tasks;
    }

    public function getTaskCompletionRateByProjectId(int $id): ?float
    {
        return $this->projectService->getTaskCompletionRateByProjectId($id);
    }

    public function renderUser()
    {
        $this->template->accountName = $this->getUserNameById($this->getUser()->getId());
        $this->template->avatar = $this->getAvatarById($this->getUser()->getId());
        $this->template->addFilter('getAvatarById', [$this, 'getAvatarById']);
        $this->template->addFilter('getUserNameById', [$this, 'getUserNameById']);
        $this->template->addFilter('getTaskCompletionRateByProjectId', [$this, 'getTaskCompletionRateByProjectId']);

        $userId = $this->getUser()->getId();
        $projects = $this->projectService->getProjectsByUserId($userId);
        $this->template->projects = $projects;

    }

    public function actionDelete($id, $project_id) 
    { 
        $this->taskService->deleteTaskById($id);
        $this->flashMessage('Úkol byl úspěšně smazán', 'success');
        $this->redirect('Project:task', $project_id);
    }

    public function actionDeleteProject($id) 
    {
        $this->projectService->deleteProjectById($id);
        $this->flashMessage('Úkol byl úspěšně smazán', 'success');
        $this->redirect('Admin:dashboard');
    }

    public function actionDeleteProjectUser($id) 
    {
        $this->projectService->deleteProjectById($id);
        $this->flashMessage('Úkol byl úspěšně smazán', 'success');
        $this->redirect('Project:user');
    }

    protected function createComponentProjectForm(): Form
{
    $form = new Form;

    $form->addText('name', 'Jméno projektu')
        ->setRequired('Vyplňte jméno projektu.');

    $form->addTextArea('description', 'Obsah projektu');

    $form->addText('due_date', 'Datum splatnosti')
        ->setRequired('Vyplňte datum splatnosti.');

    $form->addSelect('status', 'Stav', ['Not Completed' => 'Not Completed', 'Completed' => 'Completed'])
        ->setRequired('Vyberte stav.');

    $users = $this->entityManager->getRepository(Users::class)->findBy(['tenant_id' => $this->projectService->getTenantId()]);
    $userChoices = [];
    foreach ($users as $user) {
        $userChoices[$user->getId()] = $user->getUsername();
    }

    $this->template->users = $users;

    $form->addSelect('user_id', 'Zodpovědná osoba', $userChoices)
        ->setRequired('Vyberte zodpovědnou osobu.');

    $form->addSubmit('save', 'Uložit změny');

    $form->onSuccess[] = [$this, 'ProjectFormSucceeded'];

    $projectId = $this->getParameter('id');

    if ($projectId) {
        $project = $this->entityManager->getRepository(Projects::class)->find($projectId);
        if ($project) {
            $form->setDefaults([
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'due_date' => $project->getDueDate()->format('Y-m-d'),
                'status' => $project->getStatus(),
                'user_id' => $project->getUserId()
            ]);
        }
    }
    return $form;
}

    public function ProjectFormSucceeded(Form $form, \stdClass $values): void
    {
        $projectId = $this->getParameter('id');

    if ($projectId) {
        $project = $this->entityManager->getRepository(Projects::class)->find($projectId);
        if (!$project) {
            throw new \Nette\Application\BadRequestException('Projekt nebyl nalezen.');
        }
    } else {
        $userId = $this->projectService->getUserId();
        $createdAt = new \DateTime();
        $tenantId = $this->projectService->getTenantId();
        $project = new Projects($userId, '', '', $createdAt, new \DateTime(), '', $tenantId);
        $this->entityManager->persist($project);
    }

    $project->setName($values->name);
    $project->setDescription($values->description);
    $project->setDueDate(new \DateTime($values->due_date));
    $project->setStatus($values->status === 'Completed' ? 'Completed' : 'Not Completed');
    $project->setUserId($values->user_id);

    $this->entityManager->flush();

    $this->flashMessage('Projekt byl úspěšně uložen.', 'success');
    $this->redirect('Project:edit', $project->getId());
    }


}
