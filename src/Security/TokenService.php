<?php

namespace App\Security;

use App\Entity\Device;
use App\Entity\User;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class TokenService
{
  private const MAX_DEVICES = 5;
  private const REFRESH_TOKEN_LIFETIME = 604800;

  public function __construct(
    private JWTTokenManagerInterface $jwtManager,
    private EntityManagerInterface $entityManager,
    private DeviceRepository $deviceRepository
  ) {}

  public function generateAccessToken(User $user): string
  {
    return $this->jwtManager->create($user);
  }

  public function generateRefreshToken(): string
  {

    return bin2hex(random_bytes(64));
  }

  public function hashRefreshToken(string $refreshToken): string
  {
    return password_hash($refreshToken, PASSWORD_BCRYPT);
  }

  public function createDevice(User $user, string $userAgent, string $ip, string $refreshToken): Device
  {
    $activeDevicesCount = $this->deviceRepository->getUserActiveDevicesCount($user);

    if ($activeDevicesCount >= self::MAX_DEVICES) {
      $this->deviceRepository->removeOldestDevices($user, self::MAX_DEVICES - 1);
    }

    $device = new Device();
    $device->setUser($user);
    $device->setUserAgent($userAgent);
    $device->setIp($ip);

    $device->setRefreshTokenHash($this->hashRefreshToken($refreshToken));
    $device->setExpiresAt(new \DateTimeImmutable('+' . self::REFRESH_TOKEN_LIFETIME . ' seconds'));
    $device->setLastUsedAt(new \DateTimeImmutable());

    $this->entityManager->persist($device);
    $this->entityManager->flush();

    return $device;
  }

  public function refreshTokens(Device $device, string $oldRefreshToken, string $newRefreshToken): Device
  {
    if ($device->isRevoked() || $device->isCompromised()) {
      throw new \Exception('Token already used or compromised');
    }

    $device->setRefreshTokenHash($this->hashRefreshToken($newRefreshToken));
    $device->setLastUsedAt(new \DateTimeImmutable());
    $device->setExpiresAt(new \DateTimeImmutable('+' . self::REFRESH_TOKEN_LIFETIME . ' seconds'));

    $this->entityManager->flush();

    return $device;
  }

  public function revokeAllUserTokens(User $user): void
  {
    $this->deviceRepository->revokeAllUserTokens($user);
  }

  public function markDeviceAsCompromised(Device $device): void
  {
    $device->setIsCompromised(true);
    $this->revokeAllUserTokens($device->getUser());
    $this->entityManager->flush();
  }

  public function getDeviceByRefreshToken(string $refreshToken): ?Device
  {
    $devices = $this->deviceRepository->findAll();

    foreach ($devices as $device) {
      if (password_verify($refreshToken, $device->getRefreshTokenHash())) {
        return $device;
      }
    }

    return null;
  }
}
