<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseOrderReportQuery as QueryInterface;
class PurchaseOrderReportQueryService implements QueryInterface
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
        $this->repository = $doctrine->getRepository('ErpDocumentBundle:PurchaseOrder');
        $this->queryService = $queryService;
    }

    function purchaseOrderQueryBuilder($alias)
    {
        $qb = $this->repository->createQueryBuilder($alias);
        $qb
            ->select("{$alias}.code AS code")
                ->addSelect("{$alias}.id AS id")
                ->addSelect("{$alias}.approved AS approved")
                ->addSelect("{$alias}_requester.code AS requester")
                ->addSelect("{$alias}_vendor.code AS vendor")
                ->addSelect("{$alias}_project.code AS project")
                ->addSelect("{$alias}.vatCost AS vatCost")
                ->addSelect("{$alias}.excludeVat AS excludeVat")
                ->addSelect("{$alias}.docTotal AS total")
                ->addSelect("{$alias}.tax AS tax")
                ->addSelect("{$alias}.taxCost AS taxCost")
                ->addSelect("{$alias}.taxFactor AS taxFactor")
                ->addSelect("{$alias}.payTotal AS payTotal")
                ->addSelect("{$alias}_boq.name AS boq")
                ->addSelect("{$alias}_budgetType.name AS budgetType")
            ->leftJoin("{$alias}.project","{$alias}_project")
            ->leftJoin("{$alias}.requester","{$alias}_requester")
            ->leftJoin("{$alias}.vendor","{$alias}_vendor")
            ->leftJoin("{$alias}.boq","{$alias}_boq")
            ->leftJoin("{$alias}.budgetType","{$alias}_budgetType")
            ->groupBy("{$alias}")
        ;

        return $this->queryService->assignActiveDocumentQuery($qb, $alias);
    }

    function purchaseOrderSummary(array $filter = null)
    {
        $qb = $this->purchaseOrderQueryBuilder('_entity');
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

        return $qb->getQuery()->getArrayResult();

    }

}
