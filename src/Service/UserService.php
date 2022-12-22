<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Container;

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
        MailerInterface             $mailer,
    private readonly Filesystem $filesystem)
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
    public function insertOrUpdate($user, $imageData): bool
    {

        $user->getId() == null ? $user->setPassword($this->encoder->hashPassword($user, $user->getPassword())) : $this->filesystem->remove('../public/'.$user->getImage());

        $user->setImage($this->storeImage($imageData));
        $this->em->persist($user);
        $this->em->flush();

        return true;
    }

    public function storeImage($image)
    {

        $imageName = md5('1999' . date('d:Y:s')) . '.' . $image->guessExtension();
        $image->move('../public/uploads/images', $imageName);
        return 'uploads/images/' . $imageName;
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
    public function sendMail($user, $forForgotPass = false): bool
    {
        $url = '';
        $token = '';
        if($forForgotPass){
            $token = md5($user->getPassword() . date('m ss'));
            $url = "http://127.0.0.1:8000/admin/change-password/".$token;
            $url  = "And Url Is " . $url;
        }
        $otp = mt_rand(100000, 999999);
        $mail = (new Email())
            ->from('EMS.management@gmail.com')
            ->to($user->getEmail())
            ->html('
<html>
  <body>
    <p>Hey<br>
       Hey! I Am Form EMS Management System.</p>
    <p>Your OTP For Password Changing Is <strong>' . $otp . '</strong></p>'.$url.'
  </body>
</html>'
            );
        $this->mailer->send($mail);
        $this->storeOPTAndToken($user, $otp, $token);
        return true;
    }

    /**
     * @param $user
     * @param $otp
     * @return bool
     */
    public function storeOPTAndToken($user, $otp, $token = null): bool
    {
        $user->setOptRequestedAt(new \DateTime());
        $user->setOptCode($otp);
        $user->setForgotToken($token);
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
        $user->setForgotToken(null);
        $user->setOptRequestedAt(null);
        $user->setOptCode(null);
        $this->em->persist($user);
        $this->em->flush();
        return true;
    }
}
