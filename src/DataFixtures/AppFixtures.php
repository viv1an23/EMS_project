<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
         $user = new User();
         $password = $this->userPasswordHasher->hashPassword($user, 'admin');
         $user
             ->setEmail('admin@admin.com')
             ->setFirstName('admin')
             ->setLastName('new')
             ->setPassword($password)
             ->setRoles(['ROLE_ADMIN'])
         ;
         $manager->persist($user);

        $manager->flush();
    }
}
