<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[ORM\Column(length: 20)]
    private ?string $Documento = null;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[ORM\Column(length: 60)]
    private ?string $Nombres = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\NotNull]
    #[ORM\Column(length: 80)]
    private ?string $Email = null;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[ORM\Column(length: 30)]
    private ?string $Celular = null;

    #[ORM\OneToOne(mappedBy: 'client_id', cascade: ['persist', 'remove'])]
    private ?Wallet $wallet = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocumento(): ?string
    {
        return $this->Documento;
    }

    public function setDocumento(string $documento): self
    {
        $this->Documento = $documento;

        return $this;
    }

    public function getNombres(): ?string
    {
        return $this->Nombres;
    }

    public function setNombres(string $nombres): self
    {
        $this->Nombres = $nombres;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->Email;
    }

    public function setEmail(string $email): self
    {
        $this->Email = $email;

        return $this;
    }

    public function getCelular(): ?string
    {
        return $this->Celular;
    }

    public function setCelular(string $celular): self
    {
        $this->Celular = $celular;

        return $this;
    }

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(Wallet $wallet): self
    {
        // set the owning side of the relation if necessary
        if ($wallet->getClientId() !== $this) {
            $wallet->setClientId($this);
        }

        $this->wallet = $wallet;

        return $this;
    }
}
