<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class DeviceRequestDto
{
  #[Assert\NotBlank]
  #[Assert\Ip(version: Assert\Ip::V4)]
  public string $ip;

  #[Assert\NotBlank]
  #[Assert\Length(min: 5, max: 255)]
  public string $userAgent;

  #[Assert\IsTrue(message: "device must be active")]
  public bool $isActive;
}
