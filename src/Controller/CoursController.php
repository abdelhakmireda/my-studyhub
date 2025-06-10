<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\Fichier;
use App\Entity\Signalement;
use App\Entity\User;
use App\Form\CoursForm;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/cours')]
final class CoursController extends AbstractController
{
    private ?User $user;
    public function __construct(Security $Security)
    {
        $this->user = $Security->getUser();
    }

    #[Route('/new', name: 'app_cours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $cour = new Cours();

        $cour->setUtilisateur($this->user);
        $departement = $this->user?->getDepartement();
        $cour->setDepartement($departement);

        $form = $this->createForm(CoursForm::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // On vide les fichiers ajoutés par le formulaire pour les reconstruire
            foreach ($cour->getFichiers() as $fichier) {
                $cour->removeFichier($fichier);
            }

            // Pour chaque sous-formulaire fichier, on récupère le fichier uploadé
            foreach ($form->get('fichiers') as $fichierForm) {
                /** @var UploadedFile|null $uploadedFile */
                $uploadedFile = $fichierForm->get('nomFichier')->getData();

                if ($uploadedFile) {
                    // Création d'un nom de fichier sécurisé et unique
                    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                    // Déplacement du fichier
                    try {
                        $uploadedFile->move(
                            $this->getParameter('pdf_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        throw new \Exception("Erreur lors de l'upload du fichier : " . $e->getMessage());
                    }

                    // Création et configuration d'un nouvel objet Fichier
                    $fichier = new Fichier();
                    $fichier->setNom($originalFilename);
                    $fichier->setPath($newFilename);
                    $fichier->setCours($cour);

                    $cour->addFichier($fichier);
                }
            }

            $entityManager->persist($cour);
            $entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('cours/new.html.twig', [
            'user' => $this->user,
            'cour' => $cour,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id}', name: 'app_cours_show', methods: ['GET'])]
    public function show(Cours $cour): Response
    {
        return $this->render('cours/show.html.twig', [
            'cour' => $cour,
            'user' => $this->user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_cours_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CoursForm::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cours/edit.html.twig', [
            'user' => $this->user,
            'cour' => $cour,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_cours_delete', methods: ['POST'])]
    public function delete(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cour->getId(), $request->request->get('_token'))) {
            // Récupérer le chemin du dossier des fichiers
            $pdfDirectory = $this->getParameter('pdf_directory');

            // Parcourir tous les fichiers liés au cours
            foreach ($cour->getFichiers() as $fichier) {
                $filePath = $pdfDirectory . '/' . $fichier->getPath();

                // Vérifier que le fichier existe avant de tenter de le supprimer
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Suppression de l'entité Cours (cascade supprimera aussi les fichiers en BDD si configuré)
            $entityManager->remove($cour);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }
     #[Route('/cours/{id}/favoris', name: 'cours_toggle_favori')]
    public function toggleFavori(Cours $cours, EntityManagerInterface $em, Cours $cour): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($user->getCoursFavoris()->contains($cours)) {
            $user->removeCoursFavori($cours);
            $this->addFlash('info', 'Cours retiré des favoris.');
        } else {
            $user->addCoursFavori($cours);
            $this->addFlash('success', 'Cours ajouté aux favoris.');
        }

        $em->flush();

        return $this->render('cours/_favoris_button.html.twig', [
            'cour' => $cour,
            'update' => true,
        ]);
    }
#[Route('/signalement/{id}', name: 'cours_signalement')]
public function signalerOuSupprimerSignalement(Cours $cours, EntityManagerInterface $em, Security $security): Response
{
    $user = $security->getUser();
    if (!$user) {
        $this->addFlash('error', 'Vous devez être connecté pour signaler un cours.');
        return $this->redirectToRoute('app_home_dashboard');
    }

    // Vérifier si l'utilisateur a déjà signalé ce cours
    foreach ($cours->getSignalements() as $signalement) {
        if ($signalement->getUser() === $user) {
            // SUPPRIMER le signalement existant
            $em->remove($signalement);
            $em->flush();

            $this->addFlash('success', 'Signalement retiré avec succès.');
            return $this->render('cours/_signalement_button.html.twig', [
                'cour' => $cours,
                'update' => true,
            ]);
        }
    }

    // AJOUTER un nouveau signalement
    $signalement = new Signalement();
    $signalement->setCours($cours);
    $signalement->setUser($user);
    $em->persist($signalement);
    $em->flush();

    // Vérifier si le cours doit être masqué
    if ($cours->getNombreSignalements() >= 3) {
        $cours->setDeletedAt(new \DateTimeImmutable());
        $em->flush();
    }

    $this->addFlash('success', 'Cours signalé avec succès.');
    return $this->render('cours/_signalement_button.html.twig', [
        'cour' => $cours,
        'update' => true,
    ]);
}
}
