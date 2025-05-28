<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ErrorController extends AbstractController
{
    #[Route('/access-denied', name: 'app_access-denied', methods: ['GET', 'POST'])]
    public function accessDenied(Security $security): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $security->getUser();
        return $this->render('error/access_denied.html.twig',[
            'user' => $user,
        ]);
    }
}
