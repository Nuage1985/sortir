<?php

namespace App\Entity;

use App\Repository\SortieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\EtatSortie;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: 'nom', message: 'Cette sortie existe déjà !')]
#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sortie:read'])]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(min: 5, max: 180, minMessage: '5 caractères minimum', maxMessage: '180 caractères maximum')]
    #[ORM\Column(length: 180)]
    #[Groups(['sortie:read'])]
    private ?string $nom = null;

    #[Assert\NotBlank(message: 'La date et l\'heure de début sont obligatoires.')]
    #[Assert\GreaterThan('now', message: 'La sortie doit se dérouler dans le futur !')]
    #[ORM\Column]
    #[Groups(['sortie:read'])]
    private ?\DateTime $dateHeureDebut = null;

    #[Assert\NotBlank(message: 'La durée est obligatoire.')]
    #[Assert\GreaterThanOrEqual(value: 30, message: 'La durée doit être d\'au moins {{ compared_value }} minutes.')]
    #[ORM\Column]
    #[Groups(['sortie:read'])]
    private ?int $duree = null;

    #[Assert\NotBlank(message: 'La date limite pour l\'inscription est requise.')]
    #[Assert\LessThan(propertyPath: 'dateHeureDebut', message: 'La date limite doit être antérieure à la date de la sortie')]
    #[Assert\GreaterThan(value: 'now', message: 'La date limite pour l\'inscription ne peut pas être une date passée.')]
    #[ORM\Column(nullable: true)]
    #[Groups(['sortie:read'])]
    private ?\DateTime $dateLimiteInscription = null;

    #[Assert\NotBlank(message: 'Le nombre de places est obligatoire.')]
    #[Assert\GreaterThanOrEqual(value: 2, message: 'Il faut au moins {{ compared_value }} participants pour une sortie.')]
    #[ORM\Column]
    #[Groups(['sortie:read'])]
    private ?int $nbInscriptionMax = null;

    #[Assert\NotBlank(message: 'Une description est requise.')]
    #[Assert\Length(min: 20, max: 255, minMessage: '20 caractères minimum', maxMessage: 'Limite de caractères dépassées, 255 maximum.')]
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['sortie:read'])]
    private ?string $infosSortie = null;

    // Colonne string en base, à gérer côté métier
    #[ORM\Column(length: 20, enumType: EtatSortie::class)]
    #[Groups(['sortie:read'])]
    private EtatSortie $etat = EtatSortie::EnCreation;

    #[Assert\NotBlank(message: 'Le lieu est obligatoire')]
    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sortie:read'])]
    private ?Lieu $lieu = null;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\ManyToMany(targetEntity: Participant::class, mappedBy: 'sorties')]
    #[Groups(['sortie:read'])]
    private Collection $participants;

    #[ORM\ManyToOne(targetEntity: Participant::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sortie:read'])]
    private ?Participant $organisateur = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sortie:read'])]
    private ?Campus $campus = null;

    #[Assert\NotBlank(message: 'Le motif d\'annulation est obligatoire.', groups: ['Annuler'])]
    #[Assert\Length(min: 10, minMessage: 'Le motif doit contenir au moins 10 caractères.', groups: ['Annuler'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motifAnnulation = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDateHeureDebut(): ?\DateTime
    {
        return $this->dateHeureDebut;
    }

    public function setDateHeureDebut(\DateTime $dateHeureDebut): static
    {
        $this->dateHeureDebut = $dateHeureDebut;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getDateLimiteInscription(): ?\DateTime
    {
        return $this->dateLimiteInscription;
    }

    public function setDateLimiteInscription(?\DateTime $dateLimiteInscription): static
    {
        $this->dateLimiteInscription = $dateLimiteInscription;

        return $this;
    }

    public function getNbInscriptionMax(): ?int
    {
        return $this->nbInscriptionMax;
    }

    public function setNbInscriptionMax(int $nbInscriptionMax): static
    {
        $this->nbInscriptionMax = $nbInscriptionMax;

        return $this;
    }

    public function getInfosSortie(): ?string
    {
        return $this->infosSortie;
    }

    public function setInfosSortie(?string $infosSortie): static
    {
        $this->infosSortie = $infosSortie;

        return $this;
    }

    public function getEtat(): EtatSortie
    {
        return $this->etat;
    }

    public function setEtat(EtatSortie $etat): void
    {
        $this->etat = $etat;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->addSorty($this);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): static
    {
        if ($this->participants->removeElement($participant)) {
            $participant->removeSorty($this);
        }

        return $this;
    }

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): static
    {
        $this->campus = $campus;

        return $this;
    }

    public function getMotifAnnulation(): ?string
    {
        return $this->motifAnnulation;
    }

    public function setMotifAnnulation(?string $motifAnnulation): static
    {
        $this->motifAnnulation = $motifAnnulation;

        return $this;
    }

    public function getOrganisateur(): ?Participant
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?Participant $organisateur): self
    {
        $this->organisateur = $organisateur;
        return $this;
    }
}
