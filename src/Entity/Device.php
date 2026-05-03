<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'device')]
#[ORM\Index(name: 'idx_refresh_token', columns: ['refresh_token_hash'])]
class Device
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\ManyToOne]
  #[ORM\JoinColumn(nullable: false)]
  private ?User $user = null;

  #[ORM\Column(length: 500)]
  private ?string $userAgent = null;

  #[ORM\Column(length: 45)]
  private ?string $ip = null;

  #[ORM\Column(length: 255, unique: true)]
  private ?string $refreshTokenHash = null;

  #[ORM\Column]
  private ?\DateTimeImmutable $expiresAt = null;

  #[ORM\Column]
  private ?bool $isRevoked = false;

  #[ORM\Column]
  private ?\DateTimeImmutable $lastUsedAt = null;

  #[ORM\Column(nullable: true)]
  private ?bool $isCompromised = false;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getUser(): ?User
  {
    return $this->user;
  }

  public function setUser(User $user): self
  {
    $this->user = $user;
    return $this;
  }

  public function getUserAgent(): ?string
  {
    return $this->userAgent;
  }

  public function setUserAgent(string $userAgent): self
  {
    $this->userAgent = $userAgent;
    return $this;
  }

  public function getIp(): ?string
  {
    return $this->ip;
  }

  public function setIp(string $ip): self
  {
    $this->ip = $ip;
    return $this;
  }

  public function getRefreshTokenHash(): ?string
  {
    return $this->refreshTokenHash;
  }

  public function setRefreshTokenHash(string $refreshTokenHash): self
  {
    $this->refreshTokenHash = $refreshTokenHash;
    return $this;
  }

  public function getExpiresAt(): ?\DateTimeImmutable
  {
    return $this->expiresAt;
  }

  public function setExpiresAt(\DateTimeImmutable $expiresAt): self
  {
    $this->expiresAt = $expiresAt;
    return $this;
  }

  public function isRevoked(): ?bool
  {
    return $this->isRevoked;
  }

  public function setIsRevoked(bool $isRevoked): self
  {
    $this->isRevoked = $isRevoked;
    return $this;
  }

  public function getLastUsedAt(): ?\DateTimeImmutable
  {
    return $this->lastUsedAt;
  }

  public function setLastUsedAt(\DateTimeImmutable $lastUsedAt): self
  {
    $this->lastUsedAt = $lastUsedAt;
    return $this;
  }

  public function isCompromised(): ?bool
  {
    return $this->isCompromised;
  }

  public function setIsCompromised(bool $isCompromised): self
  {
    $this->isCompromised = $isCompromised;
    return $this;
  }

  public function isExpired(): bool
  {
    return new \DateTimeImmutable() > $this->expiresAt;
  }

  public function isValid(): bool
  {
    return !$this->isRevoked && !$this->isExpired() && !$this->isCompromised;
  }
}
