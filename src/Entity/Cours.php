<?php

namespace App\Entity;

use App\Repository\CoursRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use App\Entity\User;
use App\Entity\Signalement;

#[ORM\Entity(repositoryClass: CoursRepository::class)]
class Cours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Nom = null;

    #[ORM\Column(length: 255)]
    private ?string $Description = null;

    #[ORM\ManyToOne(inversedBy: 'cours')]
    private ?User $Utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'cours')]
    private ?Departement $Departement = null;

    #[ORM\OneToMany(mappedBy: 'cours', targetEntity: Fichier::class, cascade: ['persist', 'remove'])]
    private Collection $fichiers;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'coursFavoris')]
    private Collection $favoris;

    #[ORM\OneToMany(mappedBy: 'cours', targetEntity: Signalement::class, cascade: ['persist', 'remove'])]
    private Collection $signalements;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;



    public function __construct()
    {
        $this->fichiers = new ArrayCollection();
        $this->favoris = new ArrayCollection();
        $this->signalements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->Nom;
    }

    public function setNom(string $Nom): static
    {
        $this->Nom = $Nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(string $Description): static
    {
        $this->Description = $Description;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->Utilisateur;
    }

    public function setUtilisateur(?User $Utilisateur): static
    {
        $this->Utilisateur = $Utilisateur;

        return $this;
    }

    public function getDepartement(): ?Departement
    {
        return $this->Departement;
    }

    public function setDepartement(?Departement $Departement): static
    {
        $this->Departement = $Departement;

        return $this;
    }
    public function getFichiers(): Collection
    {
        return $this->fichiers;
    }

    public function addFichier(Fichier $fichier): static
    {
        if (!$this->fichiers->contains($fichier)) {
            $this->fichiers[] = $fichier;
            $fichier->setCours($this);
        }

        return $this;
    }

    public function removeFichier(Fichier $fichier): static
    {
        if ($this->fichiers->removeElement($fichier)) {
            if ($fichier->getCours() === $this) {
                $fichier->setCours(null);
            }
        }

        return $this;
    }
    public function getFavoris(): Collection
    {
        return $this->favoris;
    }

    public function addFavori(User $user): static
    {
        if (!$this->favoris->contains($user)) {
            $this->favoris[] = $user;
        }

        return $this;
    }

    public function removeFavori(User $user): static
    {
        $this->favoris->removeElement($user);
        return $this;
    }

    public function getSignalements(): Collection
    {
        return $this->signalements;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }
}
