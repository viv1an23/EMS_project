<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\UserService;
use Knp\Component\Pager\PaginatorInterface;


class UserController extends AbstractController
{
    private UserRepository $userRepository;

    private UserService $userService;

    public function __construct(UserRepository $userRepository, UserService $userService, private EntityManagerInterface $em, private PaginatorInterface $paginator)
    {
        $this->userRepository = $userRepository;
        $this->userService = $userService;
    }

    #[Route('/user', name: 'app_user')]
    public function index(Request $request): Response
    {
        $allUsers = $this->userRepository->findAll();
        $users = $this->paginator->paginate(
        // Doctrine Query, not results
            $allUsers,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            5
        );
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
            'users' => $users
        ]);
    }

    #[Route('/user/store/{id?}', name: 'store_user')]
    public function store(Request $request, $id = null): Response
    {

        $user = empty($id) ? new User() : $this->userRepository->find($id);

        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $insertData = $this->userService->insert($form->getData());
            if ($insertData) {
                return $this->redirectToRoute('app_user');
            }
        }   
        return $this->render('user/store.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/update_status', name: 'update_status')]
    public function updateStatus(Request $request)
    {
        $id = $request->request->get('id');
        $user = $this->userRepository->find($id);
        $user->setActive(!$user->getActive());
        $this->em->persist($user);
        $this->em->flush();
        return new JsonResponse('1', Response::HTTP_OK);

    }
}
