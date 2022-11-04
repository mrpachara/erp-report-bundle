<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\IncomeFinanceQueryService;
use Erp\Bundle\ReportBundle\Domain\CQRS\IncomeFinanceReportQuery as QueryInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IncomeFinanceReportQueryService implements QueryInterface
{
    /** @var IncomeFinanceQueryService */
    protected $incomeFinanceQuery;

    /** @var EntityRepository */
    protected $employeeRepos;

    /** @var EntityRepository */
    protected $projectRepos;

    /** @var EntityRepository */
    protected $personRepos;

    /** @var EntityRepository */
    protected $boqRepos;

    function __construct(
        IncomeFinanceQueryService $incomeFinanceQuery,
        RegistryInterface $doctrine
    ) {
        $this->incomeFinanceQuery = $incomeFinanceQuery;

        $this->employeeRepos = $doctrine->getRepository('ErpMasterBundle:Employee');
        $this->projectRepos = $doctrine->getRepository('ErpMasterBundle:Project');
        $this->personRepos = $doctrine->getRepository('ErpMasterBundle:Person');
        $this->boqRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoq');
    }

    function createQueryBuilder(string $alias)
    {
        $qb = $this->incomeFinanceQuery->createQueryBuilder($alias);
        $qb
            ->select("{$alias}.code AS code")
            ->addSelect("{$alias}.id AS id")
            ->addSelect("{$alias}.approved AS approved")
            ->addSelect("{$alias}_requester.code AS requester")
            ->addSelect("{$alias}_thingOwner.name AS owner")
            ->addSelect("{$alias}_project.code AS project")
            ->addSelect("{$alias}.docTotal AS docTotal")
            ->addSelect("{$alias}_boq.name AS boq")

            ->addSelect("{$alias}.vatFactor AS vatFactor")
            ->addSelect("{$alias}.vatCost AS vatCost")
            ->addSelect("{$alias}.excludeVat AS excludeVat")

            ->addSelect("{$alias}.taxFactor AS taxFactor")
            ->addSelect("{$alias}.tax AS tax")
            ->addSelect("{$alias}.taxCost AS taxCost")
            ->addSelect("{$alias}.payTotal AS payTotal")

            ->addSelect("{$alias}.retentionFactor AS retentionFactor")
            ->addSelect("{$alias}.retention AS retention")
            ->addSelect("{$alias}.retentionCost AS retentionCost")
            ->addSelect("{$alias}.retentionPayTotal AS retentionPayTotal")

            ->addSelect("{$alias}.paymentDate AS paymentDate")
            ->addSelect("{$alias}.netTotal AS netTotal")

            ->addSelect("{$alias}.deliveryDate AS deliveryDate")

            ->leftJoin("{$alias}.project", "{$alias}_project")
            ->leftJoin("{$alias}_project.owner", "{$alias}_owner")
            ->leftJoin("{$alias}_owner.thing", "{$alias}_thingOwner")
            ->leftJoin("{$alias}.requester", "{$alias}_requester")
            ->leftJoin("{$alias}.boq", "{$alias}_boq");

        $ratioAlias = "{$alias}_detail_ratio";
        $ratioQb = $this->incomeFinanceQuery->createDetailQueryBuilder($ratioAlias);
        $ratioQb = $this->incomeFinanceQuery->assignDetailRemainFilter($ratioQb, $ratioAlias);
        $ratioQb->andWhere("{$ratioAlias}.income = {$alias}");
        $ratioQb->select("SUM({$ratioAlias}.total)");

        $qb->addSelect('(' . $ratioQb->getDQL() . ') AS remain');

        return $this->incomeFinanceQuery->assignHeaderRemainFilter($qb, $alias, true);
    }

    function summarize(array $filter = null, array &$filterDetail = null)
    {
        $filterDetail = [];
        $qb = $this->createQueryBuilder('_entity');
        if (!empty($filter['start'])) {
            $qb
                ->andWhere('_entity.tstmp >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($filter['start']));
            $filterDetail['start'] = new \DateTimeImmutable($filter['start']);
        }
        if (!empty($filter['end'])) {
            $qb
                ->andWhere('_entity.tstmp <= :endDate')
                ->setParameter('endDate', new \DateTimeImmutable($filter['end']));
            $filterDetail['end'] = new \DateTimeImmutable($filter['end']);
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
        if (array_key_exists('retentionFactor', $filter)) {
            $qb
                ->andWhere('_entity.retentionFactor = :retentionFactor')
                ->setParameter('retentionFactor', $filter['retentionFactor']);
            $filterDetail['retentionFactor'] = $filter['retentionFactor'];
        }
        if (array_key_exists('paymentDate', $filter)) {
            $qb
                ->andWhere('_entity.paymentDate = :paymentDate')
                ->setParameter('paymentDate', $filter['paymentDate']);
            $filterDetail['paymentDate'] = $filter['paymentDate'];
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

            // Retention
            $data['retentionCost'] = $calRatio($data['retentionCost']);
            $data['retentionPayTotal'] = $calRatio($data['retentionPayTotal']);

            // Paymethod
            $data['netTotal'] = $calRatio($data['netTotal']);

            return $data;
        }, $qb->getQuery()->getArrayResult());
    }
}
