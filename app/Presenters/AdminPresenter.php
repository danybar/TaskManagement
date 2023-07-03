<?php declare(strict_types=1);

namespace App\Presenters;
use Nette\Application\UI\Presenter;
use App\Model\Service\UserService;
use App\Model\Service\ProjectService;
use Nette\Security\Passwords;
use Nette;

final class AdminPresenter extends Presenter
{

	public function __construct(
		private UserService $userService,
		private ProjectService $projectService,
		private Passwords $passwords,
	){$this->userService = $userService;}

	public function startup()
{
    parent::startup();

    if ($this->getUser()->isLoggedIn() === false && $this->getAction() !== 'signIn' && $this->getAction() !== 'registration') {
        $this->flashMessage('Tato sekce není přístupná bez přihlášení', 'danger');
        $this->redirect('signIn');
    } elseif ($this->getUser()->isLoggedIn() === true && $this->getAction() !== 'dashboard' && $this->getAction() !== 'signOut') {
		$this->userService->updateLastActivity($this->getUser()->getId());
        $this->redirect('dashboard');
    }
}

	public function actionSignIn()
	{
		$this->setLayout('admin.signIn');
	}

	public function actionDashboard()
	{
		$this->setLayout('admin');
	}

	public function actionSignOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('Odhlášení proběhlo úspěšně.', 'success');
		$this->redirect('signIn');
	}

	protected function createComponentSignInForm(): Nette\Application\UI\Form
	{
		$form = new Nette\Application\UI\Form();
		$form->addText('username', 'Username');
		$form->addPassword('password', 'Password');
		$form->addSubmit('send', 'Sign In');
		$form->onSuccess[] = [$this, 'signInFormSuccess'];

		return $form;
	}

	public function signInFormSuccess(Nette\Application\UI\Form $form)
	{
		$values = $form->getValues();
		try {
			$this->getUser()->login($values->username, $values->password);
			$this->userService->updateLastActivity($this->getUser()->getId());
		} catch (Nette\Security\AuthenticationException $e) {
			$this->flashMessage("Bylo zadáno špatné heslo", 'danger');
			$this->redirect('signIn');
		}

		$this->redirect('dashboard');
	}

	protected function createComponentRegistrationForm(): Nette\Application\UI\Form
	{
		$form = new Nette\Application\UI\Form();
        $form->addText('username', 'Jméno:')
            ->setRequired();
        $form->addPassword('password', 'Heslo:')
            ->setRequired();
		$form->addEmail('email', 'Email:')
            ->setRequired();
		$form->addText('code', 'Kód:')
            ->setRequired();
        $form->addSubmit('submit', 'Registrovat');
        $form->onSuccess[] = [$this, 'registrationFormSuccess'];

        return $form;
	}

	public function registrationFormSuccess(Nette\Application\UI\Form $form)
	{
		$values = $form->getValues();
		$user = $this->userService->findUser($values->username);
		$validcode = $this->userService->validcode($values->code);

		if ($user != null) {
			$this->flashMessage('Tento uživatel již existuje', 'danger');
			return;
		}elseif($validcode == null){
			$this->flashMessage('Tento kód nepatří žádné organizaci', 'danger');
			return;
		}
		
		$this->userService->registerUser($values->username, $values->password, $values->email, $values->code);
		$this->flashMessage('Registrace proběhla úspěšně', 'success');
		
		try {
			$this->getUser()->login($values->username, $values->password);
			$this->redirect('signIn');
		} catch (Nette\Security\AuthenticationException $e) {
			$this->flashMessage($e->getMessage(), 'danger');
			$this->redirect('signIn');
		}
	}

	public function getUserNameById(int $userId): ?string
    {
        return $this->userService->getUserNameById($userId);
    }

	public function getTaskCompletionRateByProjectId(int $id): ?float
    {
        return $this->projectService->getTaskCompletionRateByProjectId($id);
    }

	public function getAvatarById(int $userId): ?string
    {
        return $this->userService->getAvatarById($userId);
    }

	public function renderDashboard()
    {
		$this->template->count = $this->projectService->getProjectCountByTenantId();
		$this->template->projects = $this->projectService->getAllProjectsByTenantId();
		$this->template->projectscomplet = $this->projectService->getProjectCompletedCountByTenantId();
		$this->template->taskcount = $this->projectService->getNotCompleteTaskCountByTenantId();
		$this->template->taskCompletedcount = $this->projectService->getCompleteTaskCountByTenantId();
		$this->template->CountuserInTenant = $this->projectService->getTotalUserCountByTenantId();
		$this->template->usersdata = $this->projectService->getUsersByActiveTenantId();
		$this->template->organizationName = $this->projectService->getOrganizationNameByTenantId();

		$this->template->productivity = $this->projectService->getProjectSuccessRateByTenantId();

		$this->template->oldestregistration = $this->projectService->getOldestRegistrationDateByTenantId();
		$this->template->user_progress = $this->projectService->getProjectSuccessRateByUserId();

		$this->template->addFilter('getTaskCompletionRateByProjectId', [$this, 'getTaskCompletionRateByProjectId']);
		$this->template->addFilter('getAvatarById', [$this, 'getAvatarById']);
		$this->template->addFilter('getUserNameById', [$this, 'getUserNameById']);
		
		$this->template->accountName = $this->getUserNameById($this->getUser()->getId());
		$this->template->avatar = $this->getAvatarById($this->getUser()->getId());

		

    }

}
