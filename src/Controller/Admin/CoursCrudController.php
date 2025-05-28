<?php

namespace App\Controller\Admin;

use App\Entity\Cours;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Component\Security\Core\Security;

class CoursCrudController extends AbstractCrudController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getEntityFqcn(): string
    {
        return Cours::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('Nom', 'Nom du cours'),
            TextEditorField::new('Description'),

            // Champ utilisateur affiché mais non modifiable (affiche l’utilisateur connecté)
            AssociationField::new('Utilisateur', 'Ajouté par')->onlyOnIndex(),

            // Sur le formulaire, on ne montre pas ce champ car on l’assigne automatiquement
            AssociationField::new('Departement', 'Département'),
        ];
    }

    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Cours) return;
        
        // Assigner l'utilisateur connecté uniquement si ce n'est pas déjà défini
        if ($entityInstance->getUtilisateur() === null) {
            $user = $this->security->getUser();
            $entityInstance->setUtilisateur($user);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }
}
