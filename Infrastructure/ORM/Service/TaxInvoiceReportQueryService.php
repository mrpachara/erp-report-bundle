<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Erp\Bundle\ReportBundle\Domain\CQRS\TaxInvoiceReportQuery as QueryInterface;

class TaxInvoiceReportQueryService implements QueryInterface
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
    protected $personRepos;

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
        $this->repository = $doctrine->getRepository('ErpDocumentBundle:TaxInvoice');
        $this->queryService = $queryService;

        $this->employeeRepos = $doctrine->getRepository('ErpMasterBundle:Employee');
        $this->vendorRepos = $doctrine->getRepository('ErpMasterBundle:Vendor');
        $this->projectRepos = $doctrine->getRepository('ErpMasterBundle:Project');
        $this->personRepos = $doctrine->getRepository('ErpMasterBundle:Person');
        $this->boqRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoq');
        $this->budgetTypeRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoqBudgetType');
    }

    function taxInvoiceQueryBuilder($alias)
    {
        $qb = $this->repository->createQueryBuilder($alias);
        $qb
            ->select("{$alias}.code AS code")
            ->addSelect("{$alias}.id AS id")
            ->addSelect("{$alias}.approved AS approved")
            ->addSelect("{$alias}_requester.code AS requester")
            ->addSelect("{$alias}_thingOwner.name AS owner")
            ->addSelect("{$alias}_project.code AS project")
            ->addSelect("{$alias}.vatCost AS vatCost")
            ->addSelect("{$alias}.excludeVat AS excludeVat")
            ->addSelect("{$alias}.docTotal AS docTotal")
            ->addSelect("{$alias}.tax AS tax")
            ->addSelect("{$alias}.taxCost AS taxCost")
            ->addSelect("{$alias}.taxFactor AS taxFactor")
            ->addSelect("{$alias}.payTotal AS payTotal")
            ->addSelect("{$alias}_boq.name AS boq")
            ->leftJoin("{$alias}.project", "{$alias}_project")
            ->leftJoin("{$alias}_project.owner", "{$alias}_owner")
            ->leftJoin("{$alias}_owner.thing", "{$alias}_thingOwner")
            ->leftJoin("{$alias}.requester", "{$alias}_requester")
            ->leftJoin("{$alias}.boq", "{$alias}_boq")
            ->groupBy("{$alias}");

        return $this->queryService->assignAliveDocumentQuery($qb, $alias);
    }

    function taxInvoiceSummary(array $filter = null, array &$filterDetail = null)
    {
        $filterDetail = [];
        $qb = $this->taxInvoiceQueryBuilder('_entity');
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
        if (!empty($filter['owner'])) {
            $qb
                ->andWhere('_entity_owner = :owner')
                ->setParameter('owner', $filter['owner']);
            $filterDetail['owner'] = $this->personRepos->find($filter['owner']);
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

        return $qb->getQuery()->getArrayResult();
    }
}
