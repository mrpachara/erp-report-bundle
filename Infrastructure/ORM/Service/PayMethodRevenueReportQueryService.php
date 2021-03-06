<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Erp\Bundle\ReportBundle\Domain\CQRS\PayMethodRevenueReportQuery as QueryInterface;
class PayMethodRevenueReportQueryService implements QueryInterface
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
    )
    {
        $this->repository = $doctrine->getRepository('ErpDocumentBundle:Revenue');
        $this->queryService = $queryService;
        
        $this->employeeRepos = $doctrine->getRepository('ErpMasterBundle:Employee');
        $this->vendorRepos = $doctrine->getRepository('ErpMasterBundle:Vendor');
        $this->projectRepos = $doctrine->getRepository('ErpMasterBundle:Project');
        $this->boqRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoq');
        $this->budgetTypeRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoqBudgetType');
    }

    function payMethodRevenueQueryBuilder($alias)
    {
        $qb = $this->repository->createQueryBuilder($alias);
        $qb
            ->select("{$alias}.code AS code")
                ->addSelect("{$alias}.id AS id")
                ->addSelect("{$alias}.approved AS approved")
                ->addSelect("{$alias}_requester.code AS requester")
                ->addSelect("{$alias}_project.code AS project")
                ->addSelect("{$alias}_boq.name AS boq")
                ->addSelect("{$alias}.paymentDate AS paymentDate")
                ->addSelect("{$alias}.netTotal AS netTotal")
            ->leftJoin("{$alias}.project","{$alias}_project")
            ->leftJoin("{$alias}.requester","{$alias}_requester")
            ->leftJoin("{$alias}.boq","{$alias}_boq")
            ->groupBy("{$alias}")
        ;

        return $this->queryService->assignActiveDocumentQuery($qb, $alias);
    }

    function payMethodRevenueSummary(array $filter = null, array &$filterDetail = null)
    {
        $filterDetail = [];
        $qb = $this->payMethodRevenueQueryBuilder('_entity');
        if(!empty($filter['start'])) {
            $qb
                ->andWhere('_entity.deliveryDate >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($filter['start']))
            ;
            $filterDetail['start'] = new \DateTimeImmutable($filter['start']);
        }
        if(!empty($filter['end'])) {
            $qb
                ->andWhere('_entity.deliveryDate <= :endDate')
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
        if(array_key_exists('paymentDate', $filter)) {
            $qb
            ->andWhere('_entity.paymentDate = :paymentDate')
            ->setParameter('paymentDate', $filter['paymentDate'])
            ;
            $filterDetail['paymentDate'] = $filter['paymentDate'];
        }
        
        return $qb->getQuery()->getArrayResult();

    }

}
