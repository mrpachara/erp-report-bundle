<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Erp\Bundle\ReportBundle\Domain\CQRS\RequesterReportQuery as QueryInterface;

class RequesterReportQueryService implements QueryInterface
{
    /** @var EntityRepository */
    protected $repository;

    /** @required */
    function setRepository(\symfony\Bridge\Doctrine\RegistryInterface $doctrine)
    {
        $this->repository = $doctrine->getRepository('ErpDocumentBundle:PurchaseOrder');
    }

    function requesterQueryBuilder($alias)
    {
        $qb = $this->repository->createQueryBuilder($alias);
        $qb
            ->select("{$alias}.code AS code")
                ->addSelect("{$alias}_requester.code AS requester")
                ->addSelect("{$alias}_vendor.code AS vendor")
                ->addSelect("{$alias}_projectThing.name AS project")
                ->addSelect("{$alias}_boq.name AS boq")
                ->addSelect("{$alias}_budgetType.name AS budgetType")
                ->addSelect("{$alias}_thing.name AS costItemName")
                ->addSelect("{$alias}_costItem.type AS type")
                ->addSelect("{$alias}_costItem.unit AS unit")
                ->addSelect("{$alias}_costItem.price AS price")
                ->addSelect("{$alias}_details.quantity AS quantity")
                ->addSelect("{$alias}_details.total AS total")

            ->leftJoin("{$alias}.requester","{$alias}_requester")
            ->leftJoin("{$alias}.vendor","{$alias}_vendor")
            ->leftJoin("{$alias}.project","{$alias}_project")
            ->leftJoin("{$alias}.boq","{$alias}_boq")
            ->leftJoin("{$alias}.budgetType","{$alias}_budgetType")
            ->leftJoin("{$alias}.details","{$alias}_details")
            ->leftJoin("{$alias}_details.costItem","{$alias}_costItem")
            ->leftJoin("{$alias}_costItem.thing","{$alias}_thing")
            ->leftJoin("{$alias}_project.thing","{$alias}_projectThing")
            ->groupBy("{$alias}")
        ;

        return $qb;
    }

    function requesterSummary(array $filter = null)
    {
        $qb = $this->requesterQueryBuilder('_entity');
        if(!empty($filter['start'])) {
            $qb
                ->andWhere('_entity.tstmp >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($filter['start']))
            ;
        }
        if(!empty($filter['end'])) {
            $qb
                ->andWhere('_entity.tstmp <= :endDate')
                ->setParameter('endDate', new \DateTimeImmutable($filter['end']))
            ;
        }
        if(!empty($filter['requester'])) {
            $qb
                ->andWhere('_entity_requester = :requester')
                ->setParameter('requester', $filter['requester'])
            ;
        }
        if(!empty($filter['vendor'])) {
            $qb
                ->andWhere('_entity_vendor = :vendor')
                ->setParameter('vendor', $filter['vendor'])
            ;
        }
        if(!empty($filter['project'])) {
            $qb
                ->andWhere('_entity_project = :project')
                ->setParameter('project', $filter['project'])
            ;
        }

        return $qb->getQuery()->getArrayResult();

    }

}
