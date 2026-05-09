<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RefreshTokenRequestDto
{
  #[Assert\NotBlank(message: "refresh token is required")]
  public string $refresh_token;
}
