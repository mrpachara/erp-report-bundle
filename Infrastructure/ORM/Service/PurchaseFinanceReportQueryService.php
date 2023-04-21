<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\PurchaseFinanceQueryService;
use Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseFinanceReportQuery as QueryInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PurchaseFinanceReportQueryService implements QueryInterface
{
    /** @var PurchaseFinanceQueryService */
    protected $purchaseFinanceQuery;

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

    function __construct(
        PurchaseFinanceQueryService $purchaseFinanceQuery,
        RegistryInterface $doctrine
    ) {
        $this->purchaseFinanceQuery = $purchaseFinanceQuery;

        $this->employeeRepos = $doctrine->getRepository('ErpMasterBundle:Employee');
        $this->vendorRepos = $doctrine->getRepository('ErpMasterBundle:Vendor');
        $this->projectRepos = $doctrine->getRepository('ErpMasterBundle:Project');
        $this->boqRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoq');
        $this->budgetTypeRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoqBudgetType');
    }

    function createQueryBuilder(string $alias)
    {
        $qb = $this->purchaseFinanceQuery->createQueryBuilder($alias);
        $qb
            ->select("{$alias}.code AS code")
            ->addSelect("{$alias}.id AS id")
            ->addSelect("{$alias}.approved AS approved")
            ->addSelect("{$alias}_requester.code AS requester")
            ->addSelect("{$alias}_vendor.code AS vendor")
            ->addSelect("{$alias}_project.code AS project")
            ->addSelect("{$alias}.docTotal AS docTotal")
            ->addSelect("{$alias}_boq.name AS boq")
            ->addSelect("{$alias}_budgetType.name AS budgetType")

            ->addSelect("{$alias}.vatFactor AS vatFactor")
            ->addSelect("{$alias}.vatCost AS vatCost")
            ->addSelect("{$alias}.excludeVat AS excludeVat")

            ->addSelect("{$alias}.taxFactor AS taxFactor")
            ->addSelect("{$alias}.tax AS tax")
            ->addSelect("{$alias}.taxCost AS taxCost")
            ->addSelect("{$alias}.payTotal AS payTotal")

            ->addSelect("{$alias}.payMethod AS payMethod")
            ->addSelect("{$alias}.dueDate AS dueDate")

            ->addSelect("{$alias}.productWarranty AS productWarranty")
            ->addSelect("{$alias}.productWarrantyCost AS productWarrantyCost")

            ->addSelect("{$alias}.payTerm AS payTerm")
            ->addSelect("{$alias}.payDeposit AS payDeposit")

            ->addSelect("{$alias}.startDate AS startDate")
            ->addSelect("{$alias}.finishDate AS finishDate")

            ->leftJoin("{$alias}.project", "{$alias}_project")
            ->leftJoin("{$alias}.requester", "{$alias}_requester")
            ->leftJoin("{$alias}.vendor", "{$alias}_vendor")
            ->leftJoin("{$alias}.boq", "{$alias}_boq")
            ->leftJoin("{$alias}.budgetType", "{$alias}_budgetType");

        $ratioAlias = "{$alias}_detail_ratio";
        $ratioQb = $this->purchaseFinanceQuery->createDetailQueryBuilder($ratioAlias);
        $ratioQb = $this->purchaseFinanceQuery->assignDetailRemainFilter($ratioQb, $ratioAlias);
        $ratioQb->andWhere("{$ratioAlias}.purchase = {$alias}");
        $ratioQb->select("SUM({$ratioAlias}.total)");

        $qb->addSelect('(' . $ratioQb->getDQL() . ') AS remain');

        return $this->purchaseFinanceQuery->assignHeaderRemainFilter($qb, $alias, true);
    }

    function summarize(array $filter = null, array &$filterDetail = null)
    {
        $filterDetail = [];
        $qb = $this->createQueryBuilder('_entity');
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
        // TODO for approved, the previous document must be reappeared again
        // NOTE how does unapproved mean?
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
        if (array_key_exists('vatFactor', $filter)) {
            $qb
                ->andWhere('_entity.vatFactor = :vatFactor')
                ->setParameter('vatFactor', $filter['vatFactor']);
            $filterDetail['vatFactor'] = $filter['vatFactor'];
        }
        if (array_key_exists('taxFactor', $filter)) {
            $qb
                ->andWhere('_entity.taxFactor = :taxFactor')
                ->setParameter('taxFactor', $filter['taxFactor']);
            $filterDetail['taxFactor'] = $filter['taxFactor'];
        }
        if (array_key_exists('payMethod', $filter)) {
            $qb
                ->andWhere('_entity.payMethod = :payMethod')
                ->setParameter('payMethod', $filter['payMethod']);
            $filterDetail['payMethod'] = $filter['payMethod'];
        }
        if (array_key_exists('productWarranty', $filter)) {
            $qb
                ->andWhere('_entity.productWarranty = :productWarranty')
                ->setParameter('productWarranty', $filter['productWarranty']);
            $filterDetail['productWarranty'] = $filter['productWarranty'];
        }
        if (array_key_exists('payTerm', $filter)) {
            $qb
                ->andWhere('_entity.payTerm = :payTerm')
                ->setParameter('payTerm', $filter['payTerm']);
            $filterDetail['payTerm'] = $filter['payTerm'];
        }
        if (!empty($filter['startDue'])) {
            $startDueDate = new \DateTimeImmutable($filter['startDue']);
            $qb
                ->andWhere('_entity.dueDate >= :startDueDate')
                ->setParameter('startDueDate', $startDueDate);
            $filterDetail['startDue'] = $startDueDate;
        }
        if (!empty($filter['endDue'])) {
            $endDueDate = new \DateTimeImmutable($filter['endDue']);
            $qb
                ->andWhere('_entity.dueDate < :endDueDate')
                ->setParameter(
                    'endDueDate',
                    $endDueDate->modify('+1 day')
                );
            $filterDetail['endDue'] = $endDueDate;
        }

        return array_map(function ($data) {
            $docTotal = (float) $data['docTotal'];
            $remain = (float) $data['remain'];
            $data['ratio'] = ($docTotal === 0.0) ? null : $remain / $docTotal;

            $calRatio = function ($value) use ($data) {
                if ($value === null || $data['ratio'] === null) {
                    return $value;
                }
                return number_format($value * $data['ratio'], 2, '.', '');
            };

            $data['docTotal'] = $calRatio($data['docTotal']);

            // VAT
            $data['vatCost'] = $calRatio($data['vatCost']);
            $data['excludeVat'] = $calRatio($data['excludeVat']);

            // TAX
            $data['taxCost'] = $calRatio($data['taxCost']);
            $data['payTotal'] = $calRatio($data['payTotal']);

            // Paymethod

            // Warranty
            $data['productWarrantyCost'] = $calRatio($data['productWarrantyCost']);

            // Deposit
            $data['payDeposit'] = $calRatio($data['payDeposit']);

            return $data;
        }, $qb->getQuery()->getArrayResult());
    }
}
