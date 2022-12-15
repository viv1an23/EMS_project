<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\UserRepository;

/**
 *
 */
class UserService
{

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;
    /**
     * @var UserPasswordHasherInterface
     */
    private UserPasswordHasherInterface $encoder;
    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;
    /**
     * @var MailerInterface
     */
    private MailerInterface $mailer;

    /**
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param UserRepository $userRepository
     * @param MailerInterface $mailer
     */
    public function __construct(
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository              $userRepository,
        MailerInterface             $mailer
    )
    {
        $this->em = $em;
        $this->encoder = $userPasswordHasher;
        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
    }

    /**
     * @param $user
     * @return bool
     */
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

    /**
     * @param $user
     * @param $password
     * @return bool
     */
    public function isPasswordValid($user, $password): bool
    {
        return $this->encoder->isPasswordValid($user, $password);
    }

    /**
     * @param $user
     * @return bool
     * @throws TransportExceptionInterface
     */
    public function sendMail($user)
    {
        $otp  = mt_rand(100000, 999999);
        $mail = (new Email())
            ->from('EMS.management@gmail.com')
            ->to($user->getEmail())
            ->html('
<html>
  <body>
    <p>Hey<br>
       Hey! I Am Form EMS Management System.</p>
    <p>Your OTP For Password Changing Is <strong>' . $otp . '</strong></p>
  </body>
</html>'
            );
        $this->mailer->send($mail);
        $this->storeOPT($user,$otp);
        return true;
    }

    /**
     * @param $user
     * @param $otp
     * @return bool
     */
    public function storeOPT($user, $otp): bool
    {
        $user->setOptRequestedAt(new \DateTime());
        $user->setOptCode($otp);
        $this->em->persist($user);
        $this->em->flush();

        return true;
    }

    /**
     * @param $user
     * @param $otp
     * @return bool
     */
    public function isOTPValid($user, $otp): bool
    {
        return $user->getOptCode() == $otp;
    }

    /**
     * @param $user
     * @param $pass
     * @return bool
     */
    public function updatePassword($user, $pass): bool
    {
        $user->setPassword($this->encoder->hashPassword(
            $user,
            $pass
        ));
        $user->setOptRequestedAt(null);
        $user->setOptCode(null);
        $this->em->persist($user);
        $this->em->flush();
        return true;
    }
}