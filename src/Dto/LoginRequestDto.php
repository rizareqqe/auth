<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequestDto
{
  #[Assert\NotBlank(message: "email is required")]
  #[Assert\Email(message: "invalid email format")]
  public string $email;

  #[Assert\NotBlank(message: "password is required")]
  #[Assert\Length(min: 6, max: 255, minMessage: "password must be at least 6 characters")]
  public string $password;
}
