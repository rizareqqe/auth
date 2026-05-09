<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class DeviceResponseDto
{
  #[Assert\NotBlank]
  #[Assert\Type('integer')]
  public int $id;

  #[Assert\NotBlank]
  #[Assert\Ip]
  public string $ip;

  #[Assert\NotBlank]
  #[Assert\Length(max: 255)]
  public string $userAgent;

  #[Assert\NotBlank]
  #[Assert\DateTime(format: 'Y-m-d H:i:s')]
  public string $lastUsedAt;

  #[Assert\Type('bool')]
  public bool $isActive;

  public function __construct(
    int $id,
    string $ip,
    string $userAgent,
    string $lastUsedAt,
    bool $isActive
  ) {
    $this->id = $id;
    $this->ip = $ip;
    $this->userAgent = $userAgent;
    $this->lastUsedAt = $lastUsedAt;
    $this->isActive = $isActive;
  }
}
