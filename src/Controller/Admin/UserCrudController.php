<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;
    private AdminUrlGenerator $adminUrlGenerator;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        AdminUrlGenerator $adminUrlGenerator,
        EntityManagerInterface $entityManager
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $toggleActive = Action::new('toggleActive', 'Activer/Désactiver', 'fa fa-user-slash')
            ->linkToCrudAction('toggleActive')
            ->setCssClass('btn btn-warning');

        return $actions
            ->add(Action::INDEX, $toggleActive)
            ->update(Action::INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('Modifier')->setIcon('fa fa-edit');
            })
            // Par exemple, si tu veux modifier DELETE, fais comme ceci (optionnel)
            ->update(Action::INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('Supprimer')->setIcon('fa fa-trash');
            });
    }


    public function toggleActive(Request $request): Response
    {
        $userId = $request->query->get('entityId');
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            $this->addFlash('danger', 'Utilisateur introuvable.');
            $url = $this->adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl();
            return $this->redirect($url);
        }

        if ($user->getDeletedAt() === null) {
            // Désactiver (soft delete)
            $user->setDeletedAt(new \DateTime());
            $this->addFlash('warning', 'Utilisateur désactivé.');
        } else {
            // Réactiver
            $user->setDeletedAt(null);
            $this->addFlash('success', 'Utilisateur activé.');
        }

        $this->entityManager->flush();

        $url = $this->adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl();
        return $this->redirect($url);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            EmailField::new('email', 'Email'),
            TextField::new('nom', 'Nom d\'utilisateur'),
            TextField::new('prenom', 'Prénom d\'utilisateur'),
            AssociationField::new('departement', 'Département'),
            IntegerField::new('nombreCours', 'Nombre de cours')->onlyOnIndex(),
            ChoiceField::new('roles', 'Rôles')
                ->allowMultipleChoices()
                ->setChoices([
                    'Administrateur' => 'ROLE_ADMIN',
                    'Utilisateur' => 'ROLE_USER',
                ])
                ->renderExpanded()
                ->renderAsBadges(),
            TextField::new('plainPassword', 'Mot de passe')
                ->onlyOnForms()
                ->setFormTypeOption('attr.type', 'password'),
            TextField::new('statut', 'Statut')->onlyOnIndex(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) return;

        if ($entityInstance->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $entityInstance,
                $entityInstance->getPlainPassword()
            );
            $entityInstance->setPassword($hashedPassword);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) return;

        if ($entityInstance->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $entityInstance,
                $entityInstance->getPlainPassword()
            );
            $entityInstance->setPassword($hashedPassword);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}
