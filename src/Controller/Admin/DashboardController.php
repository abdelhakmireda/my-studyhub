<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Cours;
use App\Entity\Departement;
use App\Repository\CoursRepository;
use App\Repository\DepartementRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Symfony\Contracts\Service\Attribute\Required;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;

class DashboardController extends AbstractDashboardController
{
    private UserRepository $userRepository;
    private CoursRepository $coursRepository;
    private DepartementRepository $departementRepository;
    private AdminUrlGenerator $adminUrlGenerator;

    #[Required]
    public function setRepositories(
        UserRepository $userRepository,
        CoursRepository $coursRepository,
        DepartementRepository $departementRepository
    ): void {
        $this->userRepository = $userRepository;
        $this->coursRepository = $coursRepository;
        $this->departementRepository = $departementRepository;
    }
    #[Required]
    public function setAdminUrlGenerator(AdminUrlGenerator $adminUrlGenerator): void
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $totalUsers = $this->userRepository->count([]);

        $totalAdmins = $this->userRepository->countByRole('ROLE_ADMIN');

        $totalCours = $this->coursRepository->count([]);

        $totalDepartements = $this->departementRepository->count([]);

        $cards = [
            [
                'title' => 'Utilisateurs',
                'value' => $totalUsers,
                'color' => '#0d47a1',
                'numbercolor' => 'rgba(79, 170, 234, 0.5)',
                'icon' => 'fas fa-users',
                'route' => $this->adminUrlGenerator->setController(UserCrudController::class)->generateUrl(),
            ],
            [
                'title' => 'Admins',
                'value' => $totalAdmins,    
                'color' => '#b71c1c',
                'numbercolor' => 'rgba(229, 115, 115, 0.5)',
                'icon' => 'fas fa-user-shield',
                'route' => $this->adminUrlGenerator->setController(UserCrudController::class)->generateUrl(),
            ],
            [
                'title' => 'Cours',
                'value' => $totalCours,
                'color' => '#e65100',
                'numbercolor' => 'rgba(255, 183, 77, 0.5)',
                'icon' => 'fas fa-book-open',
                'route' => $this->adminUrlGenerator->setController(CoursCrudController::class)->generateUrl(),
            ],
            [
                'title' => 'Départements',
                'value' => $totalDepartements,
                'color' => '#004d40',
                'numbercolor' => 'rgba(77, 182, 172, 0.5)',
                'icon' => 'fas fa-building',
                'route' => $this->adminUrlGenerator->setController(DepartementCrudController::class)->generateUrl(),
            ],
        ];

        return $this->render('admin/dashboard.html.twig', [
            'cards' => $cards,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Tableau de bord');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Accueil', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', User::class);
        yield MenuItem::subMenu('Cours', 'fas fa-book')->setSubItems([
            MenuItem::linkToCrud('Tous les cours', 'fas fa-list', Cours::class),
            MenuItem::linkToRoute('Cours signalés', 'fas fa-flag', 'admin_signaled_courses'),
        ]);
        yield MenuItem::linkToCrud('Départements', 'fas fa-building', Departement::class);
    }

    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addCssFile('styles/app.css');
    }

    #[Route('/admin/cours-signales', name: 'admin_signaled_courses')]
    public function coursSignales(): Response
    {
        $coursSignales = $this->coursRepository->findAll(); // récup tous les cours

        // Filtrer les cours signalés (nombre signalements >= 3)
        $coursSignales = array_filter($coursSignales, fn(Cours $cours) => !$cours->isDisplayable());

        return $this->render('admin/cours_signales.html.twig', [
            'coursSignales' => $coursSignales,
        ]);
    }

}
