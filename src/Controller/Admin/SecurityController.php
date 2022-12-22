<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\ChangePassType;
use App\Form\ForgotPassType;
use App\Form\RegisterType;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Repository\UserRepository;

class SecurityController extends AbstractController
{

    private EntityManagerInterface $entityManager;
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface           $tokenStorage, EntityManagerInterface $entityManager,
                                private readonly UserRepository $userRepository,
                                private readonly UserService    $userService)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            $user = $this->getUser();
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/forgot_pass', name: 'app_forgot_password')]
    public function forgotPassword(Request $request)
    {
        $form = $this->createForm(ForgotPassType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $email = $form->get('email')->getData();
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if ($user) {
                $sendMail = $this->userService->sendMail($user, true);
                if ($sendMail) {
                    $this->addFlash('success', 'Mail Is Send Successfully Please Change Password');
                    return $this->redirectToRoute('app_login');
                }
            }
            $this->addFlash('error', 'Invalid User');
        }
        return $this->render('security/forgot-pass.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/change-password/{token}', name: 'change-password-token')]
    public function changePassword($token , Request $request)
    {
        $user = $this->userRepository->findOneBy(['forgot_token' => $token]);
        if ($user == null){
            throw new  \Exception('Invalid Token');
        }
        $form = $this->createForm(ChangePassType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            if ($user->getOptCode() == $form->get('code')->getData()) {
                $this->userService->updatePassword($user, $form->get('password')->getData());
                return $this->redirectToRoute('app_login');
            }
            $this->addFlash('error', 'Invalid Code');
        }
        return $this->render('security/change-pass.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
