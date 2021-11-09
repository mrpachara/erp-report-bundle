<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Erp\Bundle\ReportBundle\Domain\CQRS\VendorReportQuery as QueryInterface;

class VendorReportQueryService implements QueryInterface
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
        $this->repository = $doctrine->getRepository('ErpDocumentBundle:PurchaseOrder');
        $this->queryService = $queryService;

        $this->employeeRepos = $doctrine->getRepository('ErpMasterBundle:Employee');
        $this->vendorRepos = $doctrine->getRepository('ErpMasterBundle:Vendor');
        $this->projectRepos = $doctrine->getRepository('ErpMasterBundle:Project');
        $this->boqRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoq');
        $this->budgetTypeRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoqBudgetType');
        $this->costItemRepos = $doctrine->getRepository('ErpMasterBundle:CostItem');
    }

    function vendorGroupQueryBuilder($alias)
    {
        $qb = $this->repository->createQueryBuilder($alias);
        $qb
        ->select("{$alias}.code AS code")
            ->addSelect("{$alias}.approved AS approved")
            ->addSelect("{$alias}_requester.code AS requester")
            ->addSelect("{$alias}_vendor.code AS vendor")
            ->addSelect("{$alias}_vendorthing.name AS vendorName")
            ->addSelect("{$alias}_project.code AS project")
            ->addSelect("{$alias}_boq.name AS boq")
            ->addSelect("{$alias}_budgetType.name AS budgetType")
            ->addSelect("{$alias}.taxFactor AS taxFactor")
            ->addSelect("{$alias}.tax AS tax")
            ->addSelect("{$alias}.taxCost AS taxCost")
            ->addSelect("{$alias}_costItem.code AS costItemCode")
            ->addSelect("{$alias}_costItem.type AS type")
            ->addSelect("{$alias}_costItemthing.name AS costItemName")
            ->addSelect("{$alias}_costItem.unit AS costItemUnit")
            ->addSelect("SUM({$alias}_details.quantity) AS costItemQuantity")
            ->addSelect("SUM({$alias}_details.total) AS costItemTotal")
        ->leftJoin("{$alias}.project","{$alias}_project")
        ->leftJoin("{$alias}.requester","{$alias}_requester")
        ->leftJoin("{$alias}.vendor","{$alias}_vendor")
        ->leftJoin("{$alias}_vendor.thing","{$alias}_vendorthing")
        ->leftJoin("{$alias}.boq","{$alias}_boq")
        ->leftJoin("{$alias}.budgetType","{$alias}_budgetType")
        ->leftJoin("{$alias}.details","{$alias}_details")
        ->leftJoin("{$alias}_details.costItem","{$alias}_costItem")
        ->leftJoin("{$alias}_costItem.thing","{$alias}_costItemthing")
        ->groupBy("{$alias}_vendor.id, {$alias}_costItem.id")
        ;

        return $this->queryService->assignAliveDocumentQuery($qb,  $alias);
    }

    function vendorGroupSummary(array $filter = null, array &$filterDetail = null)
    {
        $filterDetail = [];
        $qb = $this->vendorGroupQueryBuilder('_entity');
        if(!empty($filter['start'])) {
            $qb
                ->andWhere('_entity.tstmp >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($filter['start']))
            ;
            $filterDetail['start'] = new \DateTimeImmutable($filter['start']);
        }
        if(!empty($filter['end'])) {
            $qb
                ->andWhere('_entity.tstmp <= :endDate')
                ->setParameter('endDate', new \DateTimeImmutable($filter['end']))
            ;
            $filterDetail['end'] = new \DateTimeImmutable($filter['end']);
        }
        if(array_key_exists('approved', $filter)) {
            $qb
            ->andWhere('_entity.approved = :approved')
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

        return $qb->getQuery()->getArrayResult();

    }


    function vendorDistributionQueryBuilder($alias)
    {
        $qb = $this->repository->createQueryBuilder($alias);
        $qb
        ->select("{$alias}.code AS code")
            ->addSelect("{$alias}.approved AS approved")
            ->addSelect("{$alias}_requester.code AS requester")
            ->addSelect("{$alias}_vendor.code AS vendor")
            ->addSelect("{$alias}_vendorthing.name AS vendorName")
            ->addSelect("{$alias}_project.code AS project")
            ->addSelect("{$alias}_boq.name AS boq")
            ->addSelect("{$alias}_budgetType.name AS budgetType")
            ->addSelect("{$alias}.taxFactor AS taxFactor")
            ->addSelect("{$alias}.tax AS tax")
            ->addSelect("{$alias}.taxCost AS taxCost")
            ->addSelect("{$alias}_costItem.code AS costItemCode")
            ->addSelect("{$alias}_costItem.type AS type")
            ->addSelect("{$alias}_costItemthing.name AS costItemName")
            ->addSelect("{$alias}_costItem.unit AS costItemUnit")
            ->addSelect("{$alias}_costItem.price AS costItemPrice")
            ->addSelect("{$alias}_details.quantity AS costItemQuantity")
            ->addSelect("{$alias}_details.total AS costItemTotal")
        ->leftJoin("{$alias}.project","{$alias}_project")
        ->leftJoin("{$alias}.requester","{$alias}_requester")
        ->leftJoin("{$alias}.vendor","{$alias}_vendor")
        ->leftJoin("{$alias}_vendor.thing","{$alias}_vendorthing")
        ->leftJoin("{$alias}.boq","{$alias}_boq")
        ->leftJoin("{$alias}.budgetType","{$alias}_budgetType")
        ->leftJoin("{$alias}.details","{$alias}_details")
        ->leftJoin("{$alias}_details.costItem","{$alias}_costItem")
        ->leftJoin("{$alias}_costItem.thing","{$alias}_costItemthing")

  //      ->groupBy("{$alias}")
        ;

        return $this->queryService->assignAliveDocumentQuery($qb,  $alias);
    }

    function vendorDistributionSummary(array $filter = null, array &$filterDetail = null)
    {
      $filterDetail = [];
      $qb = $this->vendorDistributionQueryBuilder('_entity');
      if(!empty($filter['start'])) {
          $qb
              ->andWhere('_entity.tstmp >= :startDate')
              ->setParameter('startDate', new \DateTimeImmutable($filter['start']))
          ;
          $filterDetail['start'] = new \DateTimeImmutable($filter['start']);
      }
      if(!empty($filter['end'])) {
          $qb
              ->andWhere('_entity.tstmp <= :endDate')
              ->setParameter('endDate', new \DateTimeImmutable($filter['end']))
          ;
          $filterDetail['end'] = new \DateTimeImmutable($filter['end']);
      }
      if(array_key_exists('approved', $filter)) {
          $qb
          ->andWhere('_entity.approved = :approved')
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
      return $qb->getQuery()->getArrayResult();
    }

}
