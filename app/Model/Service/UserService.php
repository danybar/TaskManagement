<?php declare(strict_types=1);

namespace App\Model\Service;

use App\Model\Entity\Users;
use App\Model\Entity\Tenants;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Security\Passwords;

class UserService
{
	public function __construct(
		private EntityManagerInterface $em,
		private Passwords $passwords
	)
	{}

	public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->em = $entityManager;
    }

	public function findUser(string $username): ?Users
	{
		/** @var Users|null $user */
		$user = $this->em->getRepository(Users::class)->findOneBy(['username' => $username]);
        return $user;
	}

	public function validcode(string $code): ?Tenants
	{
		/** @var Tenants|null $code */
		$code = $this->em->getRepository(Tenants::class)->findOneBy(['code' => $code]);
		return $code;
	}

	public function registerUser(string $username, string $password, string $email, string $code): Users
	{
		$organization = $this->em->getRepository(Tenants::class)->findOneBy(['code' => $code]);
        $user = new Users($username, $this->passwords->hash($password), $email, null, $organization->id);

        $this->em->persist($user);
        $this->em->flush();
		
        return $user;
	}

	public function getUserNameById(int $userId): ?string
    {
        $userRepository = $this->em->getRepository(Users::class);
        $user = $userRepository->find($userId);

        if ($user instanceof Users) {
            return $user->getUsername();
        }

        return null;
    }

	public function getAvatarById(int $userId): string
	{
		$userRepository = $this->em->getRepository(Users::class);
		$user = $userRepository->find($userId);

		if ($user instanceof Users) {
			$avatar = $user->getAvatar();
			if (!empty($avatar)) {
				return $avatar;
			}
		}

		return '/images/avatar/avatar-1.jpg';
	}

	public function updateLastActivity(int $userId): void
	{
		$user = $this->em->getRepository(Users::class)->find($userId);
		
		if ($user !== null) {
			$user->last_activity = new \DateTime();
			$this->em->flush();
		}
	}

}