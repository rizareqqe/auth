<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(unique: true)]
  #[Assert\NotBlank]
  #[Assert\Email]
  private ?string $email = null;

  #[ORM\Column]
  private ?string $password = null;

  #[ORM\Column(type: 'json')]
  private array $roles = [];

  #[ORM\OneToMany(mappedBy: 'user', targetEntity: Device::class, cascade: ['remove'])]
  private Collection $devices;

  public function __construct()
  {
    $this->devices = new ArrayCollection();
    $this->roles = ['ROLE_USER'];
  }

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getEmail(): ?string
  {
    return $this->email;
  }

  public function setEmail(string $email): self
  {
    $this->email = $email;
    return $this;
  }

  public function getPassword(): ?string
  {
    return $this->password;
  }

  public function setPassword(string $password): self
  {
    $this->password = $password;
    return $this;
  }

  public function getRoles(): array
  {
    $roles = $this->roles;
    $roles[] = 'ROLE_USER';
    return array_unique($roles);
  }

  public function setRoles(array $roles): self
  {
    $this->roles = $roles;
    return $this;
  }

  public function getDevices(): Collection
  {
    return $this->devices;
  }

  public function eraseCredentials(): void {}

  public function getUserIdentifier(): string
  {
    return $this->email;
  }
}
