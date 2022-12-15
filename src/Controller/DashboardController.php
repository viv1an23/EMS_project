<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\UserService;

class DashboardController extends AbstractController
{

    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(UserRepository $userRepository): Response
    {
        if ($this->getUser()) {
            $count = [
                'user' => $userRepository->countUsers()
            ];
            return $this->render('dashboard/index.html.twig', [
                'controller_name' => 'DashboardController',
                'count' => $count
            ]);
        } else {
            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/changepassword', name: 'changepassword')]
    public function changePassword(Request $request): JsonResponse
    {
        $result = array();
        parse_str($request->request->get('formData'), $result);
        if ($result['change_code'] == '' && $this->userService->isPasswordValid($this->getUser(), $result['change_old_password'])) {
            if ($this->userService->sendMail($this->getUser())) {
                return new JsonResponse('0', Response::HTTP_OK);
            }
        } else if ($result['change_code'] != null) {
            if ($this->userService->isOTPValid($this->getUser(), $result['change_code']))
            {
                $this->userService->updatePassword($this->getUser(), $result['change_new_password']);
            }
            return new JsonResponse('1', Response::HTTP_OK);
        }
        return new JsonResponse('Wrong Password', Response::HTTP_OK);
    }
}
