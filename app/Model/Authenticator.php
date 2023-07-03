<?php declare(strict_types=1);

use App\Model\Service\UserService;
use Nette\Security\IIdentity;

class Authenticator implements \Nette\Security\Authenticator
{

	public function __construct(
		private UserService $userService,
		private \Nette\Security\Passwords $passwords,
	) {}

	public function authenticate(string $username, string $password): IIdentity
	{
		$user = $this->userService->findUser($username);

		if ($user === null)
			throw new \Nette\Security\AuthenticationException('Uživatel nebyl nalezen.');

		if ($this->passwords->verify($password, $user->password) === false)
			throw new \Nette\Security\AuthenticationException('Špatné heslo.');

		return new \Nette\Security\SimpleIdentity($user->id, ['username' => $user->username], ['tenant_id' => $user->tenant_id]);
	}
}