<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Course;
use App\Service\PaymentService;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    private RefreshTokenGeneratorInterface $refreshTokenGenerator;
    private RefreshTokenManagerInterface $refreshTokenManager;
    private PaymentService $paymentService;
    public function __construct(UserPasswordHasherInterface $passwordHasher, 
    RefreshTokenGeneratorInterface $refreshTokenGenerator, 
    RefreshTokenManagerInterface $refreshTokenManager,
    PaymentService $paymentService
    )
    {
        $this->passwordHasher = $passwordHasher;
        $this->refreshTokenGenerator = $refreshTokenGenerator;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->paymentService = $paymentService;
    }

    public function load(ObjectManager $manager): void
    {

        $user = new User();
        $user->setEmail('user@studyon.com')
            ->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    'password'
                )
            )
            ->setBalance(500.0);
        $manager->persist($user);
        
        $user_admin = new User();
        $user_admin->setEmail('user_admin@studyon.com')
            ->setRoles(['ROLE_SUPER_ADMIN'])
            ->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    'password'
                )
            )
            ->setBalance(1000.0);
        $manager->persist($user_admin);
        $coursesByCode = $this->createCourses($manager);

        $this->paymentService->deposit($user, 500.55);
        $this->paymentService->deposit($user, 123.32);

        $transaction = $this->paymentService->payment($user, $coursesByCode['php_1']);
        $transaction->setCreated((new \DateTime())->sub(new \DateInterval('P2D')));
        $transaction->setExpires((new \DateTime())->sub(new \DateInterval('P1D')));
        $manager->persist($transaction);

        $transaction = $this->paymentService->payment($user, $coursesByCode['js_1']);
        $manager->persist($transaction);

        $transaction = $this->paymentService->payment($user, $coursesByCode['php_1']);
        $transaction->setExpires((new \DateTime())->add(new \DateInterval('PT23H')));

        $manager->persist($transaction);

        $manager->flush();
    }

    public function createCourses(ObjectManager $manager): array
    {
        $coursesByCode = [];

        foreach (self::COURSES_DATA as $courseData) {
            $course = (new Course())
                ->setCode($courseData['code'])
                ->setType($courseData['type']);
            if (isset($courseData['price'])) {
                $course->setPrice($courseData['price']);
            }

            $coursesByCode[$courseData['code']] = $course;
            $manager->persist($course);
        }
        return $coursesByCode;
    }

    private const COURSES_DATA = [
        [
            'code' => 'figma_1',
            'type' => 0 // free
        ], [
            'code' => 'php_1',
            'type' => 1, // rent
            'price' => 20
        ], [
            'code' => 'js_1',
            'type' => 2, // buy
            'price' => 30
        ], [
            'code' => 'test_buy',
            'type' => 2, // buy
            'price' => 40
        ], [
            'code' => 'test_rent',
            'type' => 1, // rent
            'price' => 10
        ],
    ];
}
