<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Erp\Bundle\ReportBundle\Domain\CQRS\CostItemPurchaseOrderReportQuery as QueryInterface;

class CostItemPurchaseOrderReportQueryService implements QueryInterface
{
    /** @var EntityRepository */
    protected $repository;
    
    /** @var EntityRepository */
    protected $employeeRepos;
    
    /** @var EntityRepository */
    protected $vendorRepos;
    
    /** @var EntityRepository */
    protected $projectRepos;
    
    /** @var EntityRepository */
    protected $boqRepos;
    
    /** @var EntityRepository */
    protected $budgetTypeRepos;
    
    /** @var EntityRepository */
    protected $costItemRepos;
    
    /** @var \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\DocumentQueryService */
    protected $queryService;

    function __construct(
        \symfony\Bridge\Doctrine\RegistryInterface $doctrine,
        \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\DocumentQueryService $queryService
    )
    {
        $this->repository = $doctrine->getRepository('ErpDocumentBundle:PurchaseOrderDetail');
        $this->queryService = $queryService;
        
        $this->employeeRepos = $doctrine->getRepository('ErpMasterBundle:Employee');
        $this->vendorRepos = $doctrine->getRepository('ErpMasterBundle:Vendor');
        $this->projectRepos = $doctrine->getRepository('ErpMasterBundle:Project');
        $this->boqRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoq');
        $this->budgetTypeRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoqBudgetType');
        $this->costItemRepos = $doctrine->getRepository('ErpMasterBundle:CostItem');
    }

    function costItemGroupPurchaseOrderQueryBuilder($alias)
    {
        $qb = $this->repository->createQueryBuilder($alias);
        $qb
            ->select("{$alias}_costItem.code AS code")
                ->addSelect("{$alias}_purchase.approved AS approved")
                ->addSelect("{$alias}_costItem.type AS type")
                ->addSelect("{$alias}_thing.name AS name")
                ->addSelect("{$alias}_costItem.unit AS unit")
                ->addSelect("{$alias}_costItem.price AS price")
                ->addSelect("SUM({$alias}.quantity) AS quantity")
                ->addSelect("SUM({$alias}.total) AS total")
                ->addSelect("{$alias}_purchase.id AS id")
                ->addSelect("{$alias}_purchase.code AS purchaseOrderCode")
                ->addSelect("{$alias}_project.code AS project")
                ->addSelect("{$alias}_boq.name AS boq")
                ->addSelect("{$alias}_budgetType.name AS budgetType")
                ->leftJoin("{$alias}.purchase","{$alias}_purchase")
                ->leftJoin("{$alias}.costItem","{$alias}_costItem")
                ->leftJoin("{$alias}_costItem.thing","{$alias}_thing")
                ->leftJoin("{$alias}_purchase.project","{$alias}_project")
                ->leftJoin("{$alias}_purchase.boq","{$alias}_boq")
                ->leftJoin("{$alias}_purchase.budgetType","{$alias}_budgetType")
                ->groupBy("{$alias}_costItem.id")
        ;

        return $this->queryService->assignActiveDocumentQuery($qb, "{$alias}_purchase");
    }

    function costItemGroupPurchaseOrderSummary(array $filter = null, array &$filterDetail = null)
    {
        $filterDetail = [];
        $qb = $this->costItemGroupPurchaseOrderQueryBuilder('_entity');
        if(!empty($filter['start'])) {
            $qb
                ->andWhere('_entity_purchase.tstmp >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($filter['start']))
            ;
            $filterDetail['start'] = new \DateTimeImmutable($filter['start']);
        }
        if(!empty($filter['end'])) {
            $qb
                ->andWhere('_entity_purchase.tstmp <= :endDate')
                ->setParameter('endDate', new \DateTimeImmutable($filter['end']))
            ;
            $filterDetail['end'] = new \DateTimeImmutable($filter['end']);
        }
        if(array_key_exists('approved', $filter)) {
            $qb
            ->andWhere('_entity_purchase.approved = :approved')
            ->setParameter('approved', $filter['approved'])
            ;
            $filterDetail['approved'] = $filter['approved'];
        }
        if(!empty($filter['requester'])) {
           $qb
               ->andWhere('_entity_requester = :requester')
               ->setParameter('requester', $filter['requester'])
           ;
           $filterDetail['requester'] = $this->employeeRepos->find($filter['requester']);
        }
        if(!empty($filter['vendor'])) {
           $qb
               ->andWhere('_entity_vendor = :vendor')
               ->setParameter('vendor', $filter['vendor'])
           ;
           $filterDetail['vendor'] = $this->vendorRepos->find($filter['vendor']);
        }
        if(!empty($filter['project'])) {
            $qb
                ->andWhere('_entity_project = :project')
                ->setParameter('project', $filter['project'])
            ;
            $filterDetail['project'] = $this->projectRepos->find($filter['project']);
        }
        if(!empty($filter['boq'])) {
            $qb
                ->andWhere('_entity_boq = :boq')
                ->setParameter('boq', $filter['boq'])
            ;
            $filterDetail['boq'] = $this->boqRepos->find($filter['boq']);
        }
        if(!empty($filter['budgetType'])) {
            $qb
                ->andWhere('_entity_budgetType = :budgetType')
                ->setParameter('budgetType', $filter['budgetType'])
            ;
            $filterDetail['budgetType'] = $this->budgetTypeRepos->find($filter['budgetType']);
        }

        if(!empty($filter['costItem'])) {
            $qb
                ->andWhere('_entity_costItem = :costItem')
                ->setParameter('costItem', $filter['costItem'])
            ;
            $filterDetail['costItem'] = $this->costItemRepos->find($filter['costItem']);
        }
        
        if(!empty($filter['type'])) {
            $qb
            ->andWhere('_entity_costItem.type = :type')
            ->setParameter('type', $filter['type'])
            ;
            $filterDetail['type'] = $this->costItemRepos->find($filter['type']);
        }

        return $qb->getQuery()->getArrayResult();

    }


    function costItemDistributionPurchaseOrderQueryBuilder($alias)
    {
        $qb = $this->repository->createQueryBuilder($alias);
        $qb
            ->select("{$alias}_costItem.code AS code")
                ->addSelect("{$alias}_purchase.approved AS approved")
                ->addSelect("{$alias}_costItem.type AS type")
                ->addSelect("{$alias}_thing.name AS name")
                ->addSelect("{$alias}_costItem.unit AS unit")
                ->addSelect("{$alias}_costItem.price AS price")
                ->addSelect("{$alias}.quantity AS quantity")
                ->addSelect("{$alias}.total AS total")
                ->addSelect("{$alias}_purchase.id AS id")
                ->addSelect("{$alias}_purchase.code AS purchaseOrderCode")
                ->addSelect("{$alias}_project.code AS project")
                ->addSelect("{$alias}_boq.name AS boq")
                ->addSelect("{$alias}_budgetType.name AS budgetType")
                ->addSelect("{$alias}_requester.code AS requester")
                ->addSelect("{$alias}_vendor.code AS vendor")
                ->leftJoin("{$alias}.purchase","{$alias}_purchase")
                ->leftJoin("{$alias}_purchase.requester","{$alias}_requester")
                ->leftJoin("{$alias}_purchase.vendor","{$alias}_vendor")
                ->leftJoin("{$alias}.costItem","{$alias}_costItem")
                ->leftJoin("{$alias}_costItem.thing","{$alias}_thing")
                ->leftJoin("{$alias}_purchase.project","{$alias}_project")
                ->leftJoin("{$alias}_purchase.boq","{$alias}_boq")
                ->leftJoin("{$alias}_purchase.budgetType","{$alias}_budgetType")

            //->groupBy("{$alias}")
        ;

        return $this->queryService->assignActiveDocumentQuery($qb, "{$alias}_purchase");
    }

    function costItemDistributionPurchaseOrderSummary(array $filter = null, array &$filterDetail = null)
    {
        $filterDetail = [];
        $qb = $this->costItemDistributionPurchaseOrderQueryBuilder('_entity');
        if(!empty($filter['start'])) {
            $qb
                ->andWhere('_entity_purchase.tstmp >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($filter['start']))
            ;
            $filterDetail['start'] = new \DateTimeImmutable($filter['start']);
        }
        if(!empty($filter['end'])) {
            $qb
                ->andWhere('_entity_purchase.tstmp <= :endDate')
                ->setParameter('endDate', new \DateTimeImmutable($filter['end']))
            ;
            $filterDetail['end'] = new \DateTimeImmutable($filter['end']);
        }
        if(array_key_exists('approved', $filter)) {
            $qb
            ->andWhere('_entity_purchase.approved = :approved')
            ->setParameter('approved', $filter['approved'])
            ;
            $filterDetail['approved'] = $filter['approved'];
        }
        if(!empty($filter['requester'])) {
           $qb
               ->andWhere('_entity_requester = :requester')
               ->setParameter('requester', $filter['requester'])
           ;
           $filterDetail['requester'] = $this->employeeRepos->find($filter['requester']);
        }
        if(!empty($filter['vendor'])) {
           $qb
               ->andWhere('_entity_vendor = :vendor')
               ->setParameter('vendor', $filter['vendor'])
           ;
           $filterDetail['vendor'] = $this->vendorRepos->find($filter['vendor']);
        }
        if(!empty($filter['project'])) {
            $qb
                ->andWhere('_entity_project = :project')
                ->setParameter('project', $filter['project'])
            ;
            $filterDetail['project'] = $this->projectRepos->find($filter['project']);
        }
        if(!empty($filter['boq'])) {
            $qb
                ->andWhere('_entity_boq = :boq')
                ->setParameter('boq', $filter['boq'])
            ;
            $filterDetail['boq'] = $this->boqRepos->find($filter['boq']);
        }
        if(!empty($filter['budgetType'])) {
            $qb
                ->andWhere('_entity_budgetType = :budgetType')
                ->setParameter('budgetType', $filter['budgetType'])
            ;
            $filterDetail['budgetType'] = $this->budgetTypeRepos->find($filter['budgetType']);
        }

        if(!empty($filter['costItem'])) {
            $qb
                ->andWhere('_entity_costItem = :costItem')
                ->setParameter('costItem', $filter['costItem'])
            ;
            $filterDetail['costItem'] = $this->costItemRepos->find($filter['costItem']);
        }

        if(!empty($filter['type'])) {
            $qb
            ->andWhere('_entity_costItem.type = :type')
            ->setParameter('type', $filter['type'])
            ;
            $filterDetail['type'] = $this->costItemRepos->find($filter['type']);
        }
        
        return $qb->getQuery()->getArrayResult();

    }

}
