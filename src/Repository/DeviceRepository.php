<?php

namespace App\Repository;

use App\Entity\Device;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DeviceRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Device::class);
  }

  public function findValidByRefreshTokenHash(string $refreshTokenHash): ?Device
  {
    return $this->createQueryBuilder('d')
      ->where('d.refreshTokenHash = :hash')
      ->andWhere('d.isRevoked = false')
      ->andWhere('d.isCompromised = false')
      ->andWhere('d.expiresAt > :now')
      ->setParameter('hash', $refreshTokenHash)
      ->setParameter('now', new \DateTimeImmutable())
      ->getQuery()
      ->getOneOrNullResult();
  }

  public function getUserActiveDevicesCount(User $user): int
  {
    return $this->createQueryBuilder('d')
      ->select('COUNT(d.id)')
      ->where('d.user = :user')
      ->andWhere('d.isRevoked = false')
      ->andWhere('d.isCompromised = false')
      ->andWhere('d.expiresAt > :now')
      ->setParameter('user', $user)
      ->setParameter('now', new \DateTimeImmutable())
      ->getQuery()
      ->getSingleScalarResult();
  }

  public function removeOldestDevices(User $user, int $keepCount): void
  {
    $devices = $this->createQueryBuilder('d')
      ->where('d.user = :user')
      ->andWhere('d.isRevoked = false')
      ->andWhere('d.isCompromised = false')
      ->orderBy('d.lastUsedAt', 'DESC')
      ->setParameter('user', $user)
      ->getQuery()
      ->getResult();

    foreach (array_slice($devices, $keepCount) as $device) {
      $device->setIsRevoked(true);
    }
  }

  public function revokeAllUserTokens(User $user): void
  {
    $this->createQueryBuilder('d')
      ->update()
      ->set('d.isRevoked', ':revoked')
      ->where('d.user = :user')
      ->setParameter('revoked', true)
      ->setParameter('user', $user)
      ->getQuery()
      ->execute();
  }

  //    /**
  //     * @return Device[] Returns an array of Device objects
  //     */
  //    public function findByExampleField($value): array
  //    {
  //        return $this->createQueryBuilder('d')
  //            ->andWhere('d.exampleField = :val')
  //            ->setParameter('val', $value)
  //            ->orderBy('d.id', 'ASC')
  //            ->setMaxResults(10)
  //            ->getQuery()
  //            ->getResult()
  //        ;
  //    }

  //    public function findOneBySomeField($value): ?Device
  //    {
  //        return $this->createQueryBuilder('d')
  //            ->andWhere('d.exampleField = :val')
  //            ->setParameter('val', $value)
  //            ->getQuery()
  //            ->getOneOrNullResult()
  //        ;
  //    }
}
