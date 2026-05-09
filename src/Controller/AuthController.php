<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Device;
use App\Security\TokenService;
use App\Dto\DeviceResponseDto;
use App\Dto\LoginRequestDto;
use App\Dto\RefreshTokenRequestDto;
use App\Dto\ChangePasswordRequestDto;
use App\Dto\LogoutRequestDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class AuthController extends AbstractController
{
  public function __construct(
    private TokenService $tokenService,
    private EntityManagerInterface $entityManager,
    private UserPasswordHasherInterface $passwordHasher,
    private SerializerInterface $serializer
  ) {}

  #[Route('/api/login', methods: ['POST'])]
  public function login(
    #[MapRequestPayload] LoginRequestDto $loginData,
    Request $request
  ): JsonResponse {
    $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $loginData->email]);

    if (!$user || !$this->passwordHasher->isPasswordValid($user, $loginData->password)) {
      return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
    }

    $accessToken = $this->tokenService->generateAccessToken($user);
    $refreshToken = $this->tokenService->generateRefreshToken();

    $ip = $request->getClientIp();
    $userAgent = $request->headers->get('User-Agent', 'Unknown');

    $device = $this->tokenService->createDevice($user, $userAgent, $ip, $refreshToken);

    return $this->json([
      'access_token' => $accessToken,
      'refresh_token' => $refreshToken,
      'device_id' => $device->getId()
    ]);
  }

  #[Route('/api/token/refresh', methods: ['POST'])]
  public function refresh(
    #[MapRequestPayload] RefreshTokenRequestDto $refreshData
  ): JsonResponse {
    $device = $this->tokenService->getDeviceByRefreshToken($refreshData->refresh_token);

    if (!$device || !$device->isValid()) {
      return $this->json(['error' => 'Invalid or expired refresh token'], Response::HTTP_UNAUTHORIZED);
    }

    try {
      $newRefreshToken = $this->tokenService->generateRefreshToken();
      $this->tokenService->refreshTokens($device, $refreshData->refresh_token, $newRefreshToken);
      $newAccessToken = $this->tokenService->generateAccessToken($device->getUser());

      return $this->json([
        'access_token' => $newAccessToken,
        'refresh_token' => $newRefreshToken
      ]);
    } catch (\Exception $e) {
      $this->tokenService->markDeviceAsCompromised($device);
      return $this->json(['error' => 'Token compromised'], Response::HTTP_UNAUTHORIZED);
    }
  }

  #[Route('/api/logout', methods: ['POST'])]
  public function logout(
    #[MapRequestPayload] LogoutRequestDto $logoutData
  ): JsonResponse {
    $device = $this->tokenService->getDeviceByRefreshToken($logoutData->refresh_token);

    if ($device) {
      $device->setIsRevoked(true);
      $this->entityManager->flush();
    }

    return $this->json(['message' => 'Logged out successfully']);
  }

  #[Route('/api/devices', methods: ['GET'])]
  public function getDevices(): JsonResponse
  {
    /** @var User $user */
    $user = $this->getUser();

    if (!$user) {
      return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }

    $devices = $this->entityManager->getRepository(Device::class)->findBy(['user' => $user]);

    // Преобразуем в DTO
    $deviceDtos = array_map(function (Device $device) {
      return new DeviceResponseDto(
        id: $device->getId(),
        ip: $device->getIp(),
        userAgent: $device->getUserAgent(),
        lastUsedAt: $device->getLastUsedAt()->format('Y-m-d H:i:s'),
        isActive: $device->isValid()
      );
    }, $devices);

    // Сериализуем через Serializer
    $json = $this->serializer->serialize($deviceDtos, 'json');

    return new JsonResponse($json, Response::HTTP_OK, [], true);
  }

  #[Route('/api/change-password', methods: ['POST'])]
  public function changePassword(
    #[MapRequestPayload] ChangePasswordRequestDto $passwordData
  ): JsonResponse {
    /** @var User $user */
    $user = $this->getUser();

    if (!$user) {
      return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }

    if (!$this->passwordHasher->isPasswordValid($user, $passwordData->old_password)) {
      return $this->json(['error' => 'Invalid old password'], Response::HTTP_UNAUTHORIZED);
    }

    $user->setPassword($this->passwordHasher->hashPassword($user, $passwordData->new_password));
    $this->tokenService->revokeAllUserTokens($user);
    $this->entityManager->flush();

    return $this->json(['message' => 'Password changed successfully. All devices logged out.']);
  }
}
