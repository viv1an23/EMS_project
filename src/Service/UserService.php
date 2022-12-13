<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\UserRepository;

class UserService
{

    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $encoder;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository              $userRepository
    )
    {
        $this->em = $em;
        $this->encoder = $userPasswordHasher;
        $this->userRepository = $userRepository;
    }

    public function insert($user)
    {
        $user->setPassword($this->encoder->hashPassword(
            $user,
            $user->getPassword()
        ));
        $this->em->persist($user);
        $this->em->flush();

        return true;
    }

}