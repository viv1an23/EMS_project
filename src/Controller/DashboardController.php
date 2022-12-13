<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{

    public function __construct()
    {

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
}
