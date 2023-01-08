<?php

namespace App\Entity;

use App\Repository\WalletRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WalletRepository::class)]
class Wallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private ?string $balance = null;

    #[ORM\OneToOne(inversedBy: 'wallet', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    public function getClientId(): ?Client
    {
        return $this->client_id;
    }

    public function setClientId(Client $client_id): self
    {
        $this->client_id = $client_id;

        return $this;
    }
}
