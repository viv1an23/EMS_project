<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;

class DashboardController extends AbstractController
{

    private UserService $userService;
    private TotpAuthenticatorInterface $totpAuthenticator;

    public function __construct(UserService $userService, TotpAuthenticatorInterface $totpAuthenticator , private readonly EntityManagerInterface $em)
    {
        $this->userService = $userService;
        $this->totpAuthenticator = $totpAuthenticator;
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(UserRepository $userRepository): Response
    {
        $count = [
            'user' => $userRepository->countUsers()
        ];
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
            'count' => $count
        ]);
    }

    #[Route('/changepassword', name: 'changepassword')]
    public function changePassword(Request $request): JsonResponse
    {
        $result = array();
        parse_str($request->request->get('formData'), $result);
        if ($result['change_code'] == '' && $this->totpAuthenticator->checkCode($this->getUser(), $result['change_totp_code'])) {
            if ($this->userService->sendMail($this->getUser())) {
                return new JsonResponse('0', Response::HTTP_OK);
            }
        } else if ($result['change_code'] != null) {
            $this->userService->isOTPValid($this->getUser(), $result['change_code']) ? $this->userService->updatePassword($this->getUser(), $result['change_new_password']) : '';
            return new JsonResponse('1', Response::HTTP_OK);
        }
        return new JsonResponse('Invalid Code', Response::HTTP_OK);
    }

    #[Route('/updateprofile', name: 'app_update_profile')]
    public function updateProfile(Request $request)
    {
        $user = $this->getUser();
        $user->setFirstName($request->request->get('update_fname'));
        $user->setLastName($request->request->get('update_lname'));
        $user->setEmail($request->request->get('update_email'));
        $user->setEnforceOtp($request->request->has('update_totp') ? 1 : 0);
        $this->em->persist($user);
        $this->em->flush();
        $this->addFlash('success', 'User Update Successfully');
        return $this->redirectToRoute('app_dashboard');
    }
}
