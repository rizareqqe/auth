<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Device;
use App\Security\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
  public function __construct(
    private TokenService $tokenService,
    private EntityManagerInterface $entityManager,
    private UserPasswordHasherInterface $passwordHasher
  ) {}

  #[Route('/api/login', methods: ['POST'])]
  public function login(Request $request): JsonResponse
  {
    $data = json_decode($request->getContent(), true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

    if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
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
  public function refresh(Request $request): JsonResponse
  {
    $data = json_decode($request->getContent(), true);
    $oldRefreshToken = $data['refresh_token'] ?? '';

    if (!$oldRefreshToken) {
      return $this->json(['error' => 'Refresh token required'], Response::HTTP_BAD_REQUEST);
    }

    $device = $this->tokenService->getDeviceByRefreshToken($oldRefreshToken);

    if (!$device || !$device->isValid()) {
      return $this->json(['error' => 'Invalid or expired refresh token'], Response::HTTP_UNAUTHORIZED);
    }

    try {
      $newRefreshToken = $this->tokenService->generateRefreshToken();

      $this->tokenService->refreshTokens($device, $oldRefreshToken, $newRefreshToken);

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
  public function logout(Request $request): JsonResponse
  {
    $data = json_decode($request->getContent(), true);
    $refreshToken = $data['refresh_token'] ?? '';

    if (!$refreshToken) {
      return $this->json(['error' => 'Refresh token required'], Response::HTTP_BAD_REQUEST);
    }

    $device = $this->tokenService->getDeviceByRefreshToken($refreshToken);

    if ($device) {
      $device->setIsRevoked(true);
      $this->entityManager->flush();
    }

    return $this->json(['message' => 'Logged out successfully']);
  }

  #[Route('/api/devices', methods: ['GET'])]
  public function getDevices(Request $request): JsonResponse
  {
    /** @var User $user */
    $user = $this->getUser();

    if (!$user) {
      return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }

    $devices = $this->entityManager->getRepository(Device::class)->findBy(['user' => $user]);

    $deviceData = array_map(function ($device) {
      return [
        'id' => $device->getId(),
        'ip' => $device->getIp(),
        'userAgent' => $device->getUserAgent(),
        'lastUsedAt' => $device->getLastUsedAt()->format('Y-m-d H:i:s'),
        'isActive' => $device->isValid()
      ];
    }, $devices);

    return $this->json($deviceData);
  }

  #[Route('/api/change-password', methods: ['POST'])]
  public function changePassword(Request $request): JsonResponse
  {
    /** @var User $user */
    $user = $this->getUser();

    if (!$user) {
      return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }

    $data = json_decode($request->getContent(), true);
    $oldPassword = $data['old_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';

    if (!$this->passwordHasher->isPasswordValid($user, $oldPassword)) {
      return $this->json(['error' => 'Invalid old password'], Response::HTTP_UNAUTHORIZED);
    }

    $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));

    $this->tokenService->revokeAllUserTokens($user);

    $this->entityManager->flush();

    return $this->json(['message' => 'Password changed successfully. All devices logged out.']);
  }
}
