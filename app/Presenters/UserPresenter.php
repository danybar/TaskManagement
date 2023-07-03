<?php declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Model\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use App\Model\Service\UserService;

class UserPresenter extends Nette\Application\UI\Presenter
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, private UserService $userService,)
    {
        $this->entityManager = $entityManager;
    }

    public function createComponentUserProfileForm(): Form
    {
        $form = new Form;

        $form->addText('link', 'Odkaz:');
       
        $form->addText('username', 'Uživatelské jméno:')
            ->setRequired('Prosím, zadejte své uživatelské jméno.');

        $form->addEmail('email', 'Email:')
            ->setRequired('Prosím, zadejte svůj email.');

        $form->addSubmit('save', 'Uložit změny');
        $form->onSuccess[] = [$this, 'userProfileFormSucceeded'];

        $userId = $this->getUser()->getId();
        $user = $this->entityManager->getRepository(Users::class)->find($userId);

        if ($user) {
            $form->setDefaults([
                'link' => $user->getAvatar(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail()
            ]);
        }

        return $form;
    }

    public function userProfileFormSucceeded(Form $form, \stdClass $values): void
    {
        $userId = $this->getUser()->getId();
        $user = $this->entityManager->getRepository(Users::class)->find($userId);

        if ($user) {
            $user->setUsername($values->username);
            $user->setEmail($values->email);
            $user->setAvatar($values->link);

            $this->entityManager->flush();

            $this->flashMessage('Informace o profilu byly úspěšně aktualizovány.', 'success');
            $this->redirect('this');
        } else {
            $this->flashMessage('Uživatel nebyl nalezen.', 'error');
        }
    }

    protected function createComponentPasswordChangeForm(): Form
    {
        $form = new Form; 
        $form->addPassword('currentPassword', 'Aktuální heslo')
            ->setRequired('Zadejte aktuální heslo.');

        $form->addPassword('newPassword', 'Nové heslo')
            ->setRequired('Zadejte nové heslo.');

        $form->addPassword('confirmNewPassword', 'Potvrďte nové heslo')
            ->setRequired('Zadejte nové heslo pro potvrzení.');

        $form->addSubmit('save', 'Uložit změny');
        $form->onSuccess[] = [$this, 'passwordChangeFormSucceeded'];

        return $form;
    }

    public function passwordChangeFormSucceeded(Form $form, \stdClass $values): void
    {
        $userId = $this->getUser()->getId();
        $user = $this->entityManager->getRepository(Users::class)->find($userId);

        if (!$user || !password_verify($values->currentPassword, $user->getPassword())) {
            $this->flashMessage('Aktuální heslo je nesprávné.', 'danger');
            return;
        }

        if ($values->newPassword !== $values->confirmNewPassword) {
            $this->flashMessage('Nová hesla se neshodují.', 'danger');
            return;
        }

        $user->setPassword(password_hash($values->newPassword, PASSWORD_DEFAULT));
        $this->entityManager->flush();

        $this->flashMessage('Heslo bylo úspěšně změněno.', 'success');
        $this->redirect('this');
    }

    public function getUserNameById(int $userId): ?string
    {
        return $this->userService->getUserNameById($userId);
    }

    public function getAvatarById(int $userId): ?string
    {
        return $this->userService->getAvatarById($userId);
    }

    public function renderEdit()
    {
        $this->template->avatar = $this->getAvatarById($this->getUser()->getId());
        $this->template->accountName = $this->getUserNameById($this->getUser()->getId());
    }

}
