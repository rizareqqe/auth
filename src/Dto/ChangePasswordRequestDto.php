<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordRequestDto
{
  #[Assert\NotBlank(message: "old password is required")]
  public string $old_password;

  #[Assert\NotBlank(message: "new password is required")]
  #[Assert\Length(min: 6, max: 255, minMessage: "password must be at least 6 characters")]
  #[Assert\NotCompromisedPassword(message: "this password has been leaked in a data breach")]
  public string $new_password;
}
