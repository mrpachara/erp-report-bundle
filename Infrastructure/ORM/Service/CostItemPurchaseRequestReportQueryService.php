<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Erp\Bundle\ReportBundle\Domain\CQRS\CostItemPurchaseRequestReportQuery as QueryInterface;
use Erp\Bundle\DocumentBundle\Entity\PurchaseDetail;

class CostItemPurchaseRequestReportQueryService implements QueryInterface
{
    /** @var EntityRepository */
    protected $repository;

    /** @var \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\DocumentQueryService */
    protected $queryService;

    function __construct(
        \symfony\Bridge\Doctrine\RegistryInterface $doctrine,
        \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\DocumentQueryService $queryService
    )
    {
        $this->repository = $doctrine->getRepository('ErpDocumentBundle:PurchaseRequestDetail');
        $this->queryService = $queryService;
    }

    function costItemDistributionPurchaseRequestQueryBuilder($alias)
    {
        $qb = $this->repository->createQueryBuilder($alias);
        $qb
            ->select("{$alias}.name AS costItemName")
                ->addSelect("{$alias}_purchase.id AS id")
                ->addSelect("{$alias}_purchase.code AS code")
                ->addSelect("{$alias}_purchase.approved AS approved")
                ->addSelect("{$alias}_costItem.type AS type")
                ->addSelect("{$alias}_costItem.unit AS unit")
                ->addSelect("{$alias}_costItem.price AS price")
                ->addSelect("{$alias}.quantity AS quantity")
                ->addSelect("{$alias}.total AS total")
                ->addSelect("{$alias}_costItem.code AS costItemCode")
                ->addSelect("{$alias}_project.code AS project")
                ->addSelect("{$alias}_boq.name AS boq")
                ->addSelect("{$alias}_budgetType.name AS budgetType")
                ->leftJoin("{$alias}.purchase","{$alias}_purchase")
                ->leftJoin("{$alias}.costItem","{$alias}_costItem")
                ->leftJoin("{$alias}_costItem.thing","{$alias}_thing")
                ->leftJoin("{$alias}_purchase.project","{$alias}_project")
                ->leftJoin("{$alias}_purchase.boq","{$alias}_boq")
                ->leftJoin("{$alias}_purchase.budgetType","{$alias}_budgetType")

            //->groupBy("{$alias}")
        ;

        return $this->queryService->assignActiveDocumentQuery($qb, "{$alias}_purchase");
    }

    function costItemDistributionPurchaseRequestSummary(array $filter = null)
    {
        $qb = $this->costItemDistributionPurchaseRequestQueryBuilder('_entity');
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
        if(!empty($filter['boq'])) {
            $qb
                ->andWhere('_entity_boq = :boq')
                ->setParameter('boq', $filter['boq'])
            ;
        }
        if(!empty($filter['budgetType'])) {
            $qb
                ->andWhere('_entity_budgetType = :budgetType')
                ->setParameter('budgetType', $filter['budgetType'])
            ;
        }

        if(!empty($filter['costItem'])) {
            $qb
                ->andWhere('_entity_costItem = :costItem')
                ->setParameter('costItem', $filter['costItem'])
            ;
        }


        return $qb->getQuery()->getArrayResult();

    }

}