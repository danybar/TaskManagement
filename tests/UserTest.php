<?php declare(strict_types=1);

use App\Model\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Nette\Security\Passwords;

class UserTest extends TestCase
{
    private UserService $userService;

    protected function setUp(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $passwords = $this->createMock(Passwords::class);
        $this->userService = new UserService($entityManager, $passwords);
        $this->userService->setEntityManager($entityManager);
    }

    public function testFindUser(): void
    {
        $username = 'testuser';
        $expectedUser = $this->createMock(App\Model\Entity\Users::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(Doctrine\ORM\EntityRepository::class);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['username' => $username])
            ->willReturn($expectedUser);

        $this->userService->setEntityManager($entityManager);

        $user = $this->userService->findUser($username);

        $this->assertSame($expectedUser, $user);
    }

    public function testValidCode(): void
    {
        $code = 'testcode';
        $expectedTenant = $this->createMock(App\Model\Entity\Tenants::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(Doctrine\ORM\EntityRepository::class);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => $code])
            ->willReturn($expectedTenant);

        $this->userService->setEntityManager($entityManager);

        $tenant = $this->userService->validcode($code);

        $this->assertSame($expectedTenant, $tenant);
    }

   public function testRegisterUser(): void
    {
        $username = 'testuser';
        $password = 'testpassword';
        $email = 'test@example.com';
        $code = 'testcode';

        $expectedTenant = $this->createMock(App\Model\Entity\Tenants::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $userRepository = $this->createMock(Doctrine\ORM\EntityRepository::class);
        $passwords = $this->createMock(Nette\Security\Passwords::class);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(App\Model\Entity\Tenants::class)
            ->willReturn($userRepository);

        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => $code])
            ->willReturn($expectedTenant);

        $passwords->expects($this->once())
            ->method('hash')
            ->with($password)
            ->willReturn('hashedpassword');

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($user) use ($username, $email, $expectedTenant) {
                return $user instanceof App\Model\Entity\Users
                    && $user->getUsername() === $username
                    && $user->getEmail() === $email
                    && $user->getTenantId() === $expectedTenant->getId();
            }));

        $entityManager->expects($this->once())
            ->method('flush');

        $this->userService = new UserService($entityManager, $passwords);
        $this->userService->setEntityManager($entityManager);

        $user = $this->userService->registerUser($username, $password, $email, $code);

        $this->assertInstanceOf(App\Model\Entity\Users::class, $user);
    }


}
