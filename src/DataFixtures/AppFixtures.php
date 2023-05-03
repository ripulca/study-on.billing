<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $user = new User();
        $user->setEmail('user@studyon.com')
            ->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    'password'
                )
            )
            ->setBalance(500.0);

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
        $manager->persist($user);
        $manager->persist($user_admin);
        $manager->flush();
    }
}
