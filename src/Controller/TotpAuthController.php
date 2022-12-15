<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\TwoFactorType;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Endroid\QrCode\Builder\BuilderInterface;

class TotpAuthController extends AbstractController
{

    public function __construct(
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        private readonly EntityManagerInterface     $entityManager,
        private readonly BuilderInterface           $builder
    )
    {
    }

    #[Route('/totp/auth', name: 'app_totp_auth')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(TwoFactorType::class);
        $form->handleRequest($request);

        if (empty($user->getTotpSecret())) {
            $user->setTotpSecret($this->totpAuthenticator->generateSecret());
            $this->entityManager->flush();
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $code = $form->get('code')->getData();
            $codeVerified = $this->totpAuthenticator->checkCode($user, $code);

            if ($codeVerified) {
                $user->setIsTotpVerified(1);
                $this->entityManager->flush();
                return $this->redirectToRoute('app_logout');
            }
        }
        return $this->render('security/2fa_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/totp/generateqr', name: 'generate_qr')]
    public function displayQrCode(): Response
    {
        $user = $this->getUser();
        $qrCodeContent = $this->totpAuthenticator->getQRContent($user);

        $result = $this->builder
            ->data($qrCodeContent)
            ->size(500)
            ->build();

        return new Response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }
}
