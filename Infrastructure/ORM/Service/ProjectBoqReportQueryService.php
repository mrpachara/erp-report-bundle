<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqReportQuery as QueryInterface;
use Erp\Bundle\MasterBundle\Entity\ProjectBoq;
use Erp\Bundle\MasterBundle\Entity\ProjectBoqBudgetType;
class ProjectBoqReportQueryService implements QueryInterface
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
    
    /** @var \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\ProjectBoqWithSummaryQueryService */
    protected $queryService;

    function __construct(
        \symfony\Bridge\Doctrine\RegistryInterface $doctrine,
        \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\ProjectBoqWithSummaryQueryService $queryService
    )
    {
        $this->repository = $doctrine->getRepository('ErpMasterBundle:ProjectBoq');
        $this->queryService = $queryService;
        
        $this->employeeRepos = $doctrine->getRepository('ErpMasterBundle:Employee');
        $this->vendorRepos = $doctrine->getRepository('ErpMasterBundle:Vendor');
        $this->boqRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoq');
        $this->budgetTypeRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoqBudgetType');
    }

    function projectBoqSummary(string $idProject)
    {
        $boqs = $this->queryService->getAllProjectBoq($idProject);
        
        $results = [];
        
        /**
         * @var $boq ProjectBoq
         */
        foreach($boqs as $inx => $boq) {
            $result = [
                'name' => $boq->getName(),
                'value' => [
                    'contract' => (double)$boq->getBoqContract(),
                    'revenue' => (double)$boq->value['revenue']['approved'],
                    'remain' => (double) ($boq->getBoqContract() - $boq->value['revenue']['approved']),
                ],
                'cost' => [
                    'columns' => [],
                    'data' => [],
                ]
            ];
            
            /**
             * @var $budgetType ProjectBoqBudgetType
             */
            foreach($boq->getBudgetTypes() as $budgetType) {
                $result['cost']['columns'][] = [
                    'id' => $budgetType->getId(),
                    'name' => $budgetType->getName(),
                ];
            }
            
            $numbers = [];
            while($boq !== null) {
                $costData = [
                    'number' => implode('.', $numbers),
                    'name' => $boq->getName(),
                    'costs' => [],
                ];
                foreach($result['cost']['columns'] as $column) {
                    $costData['costs'][] = [
                        'budget' => (double)$boq->getBudgets()[$column['id']]->getBudget(),
                        'cost' => (double)$boq->getBudgets()[$column['id']]->cost['expense']['approved'],
                        'remain' => (double)($boq->getBudgets()[$column['id']]->getBudget() - $boq->getBudgets()[$column['id']]->cost['expense']['approved']),
                    ];
                }
                $result['cost']['data'][] = $costData;
                
                $number = array_pop($numbers) + 1;
                if($number <= count($boq->getChildren())) {
                    $numbers[] = $number;
                    $boq = $boq->getChildren()[$number - 1];
                } else {
                    $boq = $boq->getParent();
                }
            }
            
            
            $results[] = $result;
        }
        
        return $results;

    }

}
