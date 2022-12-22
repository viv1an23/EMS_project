<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\RegisterType;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;


class UserController extends AbstractController
{
    private UserRepository $userRepository;

    private UserService $userService;

    public function __construct(UserRepository $userRepository, UserService $userService,
                                private EntityManagerInterface $em,
                                private PaginatorInterface $paginator,
                                private Filesystem $filesystem)
    {
        $this->userRepository = $userRepository;
        $this->userService = $userService;
    }

    #[Route('/user', name: 'app_user')]
    public function index(Request $request): Response
    {
        $allUsers = $this->userRepository->allUsers($this->getUser()->getId());
        $users = $this->paginator->paginate(
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

    #[Route('/user/action/{id?}', name: 'store_user')]
    public function store(Request $request, User $userobj = null): Response
    {
        $arr = [];
        if ($userobj instanceof User) {
            $arr['show_password_field'] = false;
        }
        $user = $userobj instanceof User ? $userobj : new User();

        $form = $this->createForm(RegisterType::class, $user, $arr);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageData = $form->get('image')->getData();
            $actionData = $this->userService->insertOrUpdate($form->getData(),$imageData);
            if ($actionData) {
                $this->addFlash('success', 'Action Done Successfully');
                return $this->redirectToRoute('app_user');
            }
        }
        return $this->render('user/store.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/delete/{id}', name: 'delete_user')]
    public function delete(Request $request, User $user = null): JsonResponse
    {
        $credentials = array();
        parse_str($request->request->get('formData'), $credentials);

        $submittedToken = $credentials['token'];

        if ($this->isCsrfTokenValid('delete-item', $submittedToken)) {
            $this->filesystem->remove('../public/'.$user->getImage());
            $this->em->remove($user);
            $this->em->flush();
        }
        return new JsonResponse('1', Response::HTTP_OK);
    }

    #[Route('/user/update_status/{id}', name: 'update_status')]
    public function updateStatus(Request $request, User $user): Response
    {
        $user->setActive(!$user->getActive());
        $this->em->persist($user);
        $this->em->flush();
        return $this->redirectToRoute('app_user');
    }
}
