<?php

namespace App\Entity;

use App\Repository\DepartementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartementRepository::class)]
class Departement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Nom = null;

    /**
     * @var Collection<int, Cours>
     */
    #[ORM\OneToMany(targetEntity: Cours::class, mappedBy: 'Departement')]
    private Collection $cours;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'departement')]
    private Collection $users;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
        $this->users = new ArrayCollection();
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

    /**
     * @return Collection<int, Cours>
     */
    public function getCours(): Collection
    {
        return $this->cours;
    }

    public function addCour(Cours $cour): static
    {
        if (!$this->cours->contains($cour)) {
            $this->cours->add($cour);
            $cour->setDepartement($this);
        }

        return $this;
    }

    public function removeCour(Cours $cour): static
    {
        if ($this->cours->removeElement($cour)) {
            // set the owning side to null (unless already changed)
            if ($cour->getDepartement() === $this) {
                $cour->setDepartement(null);
            }
        }

        return $this;
    }
        public function __toString(): string
    {
        return $this->getNom() ?? ''; // ou getFullName(), ou autre méthode qui retourne un nom
    }

        /**
         * @return Collection<int, User>
         */
        public function getUsers(): Collection
        {
            return $this->users;
        }

        public function addUser(User $user): static
        {
            if (!$this->users->contains($user)) {
                $this->users->add($user);
                $user->setDepartement($this);
            }

            return $this;
        }

        public function removeUser(User $user): static
        {
            if ($this->users->removeElement($user)) {
                // set the owning side to null (unless already changed)
                if ($user->getDepartement() === $this) {
                    $user->setDepartement(null);
                }
            }

            return $this;
        }
}
