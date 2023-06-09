<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

class UserDTO
{
    #[Serializer\Type('string')]
    #[Assert\Email(message: 'Email не является валидным.')]
    #[Assert\NotBlank(message: 'Email не может быть пуст.')]
    private ?string $username = null;

    #[Serializer\Type('string')]
    #[Assert\Length(min: 6, minMessage: 'Пароль должен содержать минимум 6 символов.')]
    #[Assert\NotBlank(message: 'Пароль не может быть пуст.')]
    private ?string $password = null;

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
}