<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements TwoFactorInterface, PasswordAuthenticatedUserInterface, UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = ['ROLE_USER'];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $opt_requested_at = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $opt_code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totp_secret = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isTotpVerified = null;

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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

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

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getOptRequestedAt(): ?\DateTimeInterface
    {
        return $this->opt_requested_at;
    }

    public function setOptRequestedAt(?\DateTimeInterface $opt_requested_at): self
    {
        $this->opt_requested_at = $opt_requested_at;

        return $this;
    }

    public function getOptCode(): ?string
    {
        return $this->opt_code;
    }

    public function setOptCode(?string $opt_code): self
    {
        $this->opt_code = $opt_code;

        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return (bool)$this->totp_secret;
    }

    public function getTotpAuthenticationUsername(): string
    {
        // TODO: Implement getTotpAuthenticationUsername() method.
        return $this->getUserIdentifier();
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        return new TotpConfiguration($this->totp_secret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function getTotpSecret(): ?string
    {
        return $this->totp_secret;
    }

    public function setTotpSecret(?string $totp_secret): self
    {
        $this->totp_secret = $totp_secret;

        return $this;
    }

    public function isIsTotpVerified(): ?bool
    {
        return $this->isTotpVerified;
    }

    public function setIsTotpVerified(?bool $isTotpVerified): self
    {
        $this->isTotpVerified = $isTotpVerified;

        return $this;
    }
}