<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Erp\Bundle\ReportBundle\Domain\CQRS\WarrantyPurchaseOrderReportQuery as QueryInterface;

class WarrantyPurchaseOrderReportQueryService implements QueryInterface
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

    /** @var \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\DocumentQueryService */
    protected $queryService;

    function __construct(
        \symfony\Bridge\Doctrine\RegistryInterface $doctrine,
        \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\DocumentQueryService $queryService
    ) {
        $this->repository = $doctrine->getRepository('ErpDocumentBundle:PurchaseOrder');
        $this->queryService = $queryService;

        $this->employeeRepos = $doctrine->getRepository('ErpMasterBundle:Employee');
        $this->vendorRepos = $doctrine->getRepository('ErpMasterBundle:Vendor');
        $this->projectRepos = $doctrine->getRepository('ErpMasterBundle:Project');
        $this->boqRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoq');
        $this->budgetTypeRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoqBudgetType');
    }

    function warrantyPurchaseOrderQueryBuilder($alias)
    {
        $qb = $this->repository->createQueryBuilder($alias);
        $qb
            ->select("{$alias}.code AS code")
            ->addSelect("{$alias}.id AS id")
            ->addSelect("{$alias}.approved AS approved")
            ->addSelect("{$alias}_requester.code AS requester")
            ->addSelect("{$alias}_vendor.code AS vendor")
            ->addSelect("{$alias}_project.code AS project")
            ->addSelect("{$alias}_boq.name AS boq")
            ->addSelect("{$alias}_budgetType.name AS budgetType")
            ->addSelect("{$alias}.productWarranty AS productWarranty")
            ->addSelect("{$alias}.productWarrantyCost AS productWarrantyCost")
            ->addSelect("{$alias}.startDate AS startDate")
            ->addSelect("{$alias}.finishDate AS finishDate")
            ->leftJoin("{$alias}.project", "{$alias}_project")
            ->leftJoin("{$alias}.requester", "{$alias}_requester")
            ->leftJoin("{$alias}.vendor", "{$alias}_vendor")
            ->leftJoin("{$alias}.boq", "{$alias}_boq")
            ->leftJoin("{$alias}.budgetType", "{$alias}_budgetType")
            ->groupBy("{$alias}");

        return $this->queryService->assignAliveDocumentQuery($qb, $alias);
    }

    function warrantyPurchaseOrderSummary(array $filter = null, array &$filterDetail = null)
    {
        $filterDetail = [];
        $qb = $this->warrantyPurchaseOrderQueryBuilder('_entity');
        if (!empty($filter['start'])) {
            $startDate = new \DateTimeImmutable($filter['start']);
            $qb
                ->andWhere('_entity.tstmp >= :startDate')
                ->setParameter('startDate', $startDate);
            $filterDetail['start'] = $startDate;
        }
        if (!empty($filter['end'])) {
            $endDate = new \DateTimeImmutable($filter['end']);
            $qb
                ->andWhere('_entity.tstmp < :endDate')
                ->setParameter(
                    'endDate',
                    $endDate->modify('+1 day')
                );
            $filterDetail['end'] = $endDate;
        }
        if (array_key_exists('approved', $filter)) {
            $qb
                ->andWhere('_entity.approved = :approved')
                ->setParameter('approved', $filter['approved']);
            $filterDetail['approved'] = $filter['approved'];
        }
        if (!empty($filter['requester'])) {
            $qb
                ->andWhere('_entity_requester = :requester')
                ->setParameter('requester', $filter['requester']);
            $filterDetail['requester'] = $this->employeeRepos->find($filter['requester']);
        }
        if (!empty($filter['vendor'])) {
            $qb
                ->andWhere('_entity_vendor = :vendor')
                ->setParameter('vendor', $filter['vendor']);
            $filterDetail['vendor'] = $this->vendorRepos->find($filter['vendor']);
        }
        if (!empty($filter['project'])) {
            $qb
                ->andWhere('_entity_project = :project')
                ->setParameter('project', $filter['project']);
            $filterDetail['project'] = $this->projectRepos->find($filter['project']);
        }
        if (!empty($filter['boq'])) {
            $qb
                ->andWhere('_entity_boq = :boq')
                ->setParameter('boq', $filter['boq']);
            $filterDetail['boq'] = $this->boqRepos->find($filter['boq']);
        }
        if (!empty($filter['budgetType'])) {
            $qb
                ->andWhere('_entity_budgetType = :budgetType')
                ->setParameter('budgetType', $filter['budgetType']);
            $filterDetail['budgetType'] = $this->budgetTypeRepos->find($filter['budgetType']);
        }
        if (array_key_exists('productWarranty', $filter)) {
            $qb
                ->andWhere('_entity.productWarranty = :productWarranty')
                ->setParameter('productWarranty', $filter['productWarranty']);
            $filterDetail['productWarranty'] = $filter['productWarranty'];
        }

        return $qb->getQuery()->getArrayResult();
    }
}
