<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class LogoutRequestDto
{
  #[Assert\NotBlank(message: "refresh token is required")]
  public string $refresh_token;
}
