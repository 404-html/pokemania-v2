<?php
namespace AppBundle\Repository;

use AppBundle\Entity\Report;
use Doctrine\ORM\EntityRepository;

class ReportRepository extends EntityRepository
{
    public function countReports(int $userId): ?int
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('count(reports.id)');
        $qb->from('AppBundle:Report', 'reports');
        $qb->where('reports.userId = :id');
        $qb->andWhere('reports.isRead = 0');
        $qb->setParameter(':id', $userId);

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getReports(int $userId): array
    {
        return $this->createQueryBuilder('r')
                    ->where('r.userId = :id')
                    ->setParameter(':id', $userId)
                    ->orderBy('r.id', 'DESC')
                    ->getQuery()
                    ->getResult();
    }

    public function markReportsAsRead(int $userId): void
    {
        $this->createQueryBuilder('r')
            ->update()
            ->set('r.isRead', 1)
            ->where('r.userId = :id')
            ->andWhere('r.isRead = 0')
            ->setParameter(':id', $userId)
            ->getQuery()
            ->getResult();
    }

    public function getOneReport(int $userId, int $id): ?Report
    {
        return $this->createQueryBuilder('r')
                    ->where('r.userId = :userId')
                    ->andWhere('r.id = :id')
                    ->setParameter(':userId', $userId)
                    ->setParameter(':id', $id)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    public function deleteOneReport(int $userId, int $reportId): array
    {
        return $this->createQueryBuilder('r')
            ->delete()
            ->where('r.id = :reportId')
            ->andWhere('r.userId = :userId')
            ->setParameter(':reportId', $reportId)
            ->setParameter(':userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function deleteReports(int $userId): void
    {
        $this->createQueryBuilder('r')
            ->delete()
            ->where('r.userId = :userId')
            ->setParameter(':userId', $userId)
            ->getQuery()
            ->getResult();
    }
}
