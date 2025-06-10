<?php
// src/Controller/HomeController.php

namespace App\Controller;

use App\Controller\Admin\UserCrudController;
use App\Entity\User;
use App\Repository\CoursRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

final class HomeController extends AbstractController
{
     private AdminUrlGenerator $adminUrlGenerator;
     private ?User $user;
    public function __construct(Security $Security)
    {
        $this->user = $Security->getUser();
    }
    #[Required]
    public function setAdminUrlGenerator(AdminUrlGenerator $adminUrlGenerator): void
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }
    #[Route('/home/mycours', name: 'app_home')]
    public function index(CoursRepository $coursRepository): Response
    {
        
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos cours.');
        }

        // On récupère les cours du user connecté via le repository
        $cours = $coursRepository->findBy([
            'Utilisateur' => $user,
        ]);


        return $this->render('home/index.html.twig', [
            'route'=>$this->adminUrlGenerator->setController(UserCrudController::class)->generateUrl(),
            'user'=>$user,
            'cours' => $cours,
        ]);
    }
    #[Route('/home/mycours/favorite', name: 'app_home_favorite')]
    public function Myfavorite(): Response
    {
        $user = $this->user;

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos favoris.');
        }

        // Les cours favoris sont dans l'entité User via coursFavoris
        $coursFavoris = $user->getCoursFavoris();

        return $this->render('home/index.html.twig', [
            'route' => $this->adminUrlGenerator->setController(UserCrudController::class)->generateUrl(),
            'user' => $user,
            'cours' => $coursFavoris, // cours ici = favoris
        ]);
    }

     #[Route('/home/allcours', name: 'app_home_allcours')]
    public function allcours(CoursRepository $coursRepository): Response
    {
        
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos cours.');
        }

        // On récupère les cours du user connecté via le repository
        $cours = $coursRepository->findAll();


        return $this->render('home/index.html.twig', [
            'route'=>$this->adminUrlGenerator->setController(UserCrudController::class)->generateUrl(),
            'user'=>$user,
            'cours' => $cours,
        ]);
    }
    #[Route('/home/dashboard', name: 'app_home_dashboard')]
    public function dashboard(CoursRepository $coursRepository): Response
    {
         $user = $this->getUser();
        $myCoursCount = $coursRepository->count(['Utilisateur' => $user]);
        $allCoursCount = $coursRepository->count([]);

        $cards = [
            [
                'title' => '📚 Mes cours',
                'value' => $myCoursCount,
                'color' => '#0d47a1',
                'numbercolor' => 'rgba(79, 170, 234, 0.5)',
                'icon' => 'fas fa-book-reader',
                'route' => $this->generateUrl('app_home'),
            ],
            [
                'title' => '🌍 Tous les cours',
                'value' => $allCoursCount,
                'color' => '#004d40',
                'numbercolor' => 'rgba(77, 182, 172, 0.5)',
                'icon' => 'fas fa-globe',
                'route' => $this->generateUrl('app_home_allcours'),
            ],
        ];

        return $this->render('home/dashboard.html.twig', [
            'cards' => $cards,
            'user' => $user,
        ]);
    }

}


