<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use Erp\Bundle\ReportBundle\Domain\CQRS\ProjectIncomeReportQuery as QueryInterface;
use Erp\Bundle\MasterBundle\Entity\Project;

class ProjectIncomeReportQueryService implements QueryInterface
{
    /** @var \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\ProjectBoqWithSummaryQueryService */
    protected $queryService;

    /** @var \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\ProjectDateSummaryQueryService */
    protected $queryDateService;
    
    /** @var \Erp\Bundle\MasterBundle\Domain\CQRS\ProjectQuery */
    private $domainQuery;
    
    public function __construct(
        \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\ProjectBoqWithSummaryQueryService $queryService,
        \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\ProjectDateSummaryQueryService $queryDateService,
        \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\ProjectBoqContractSummaryQueryService $queryContractService,
        \Erp\Bundle\MasterBundle\Domain\CQRS\ProjectQuery $domainQuery
        
    ) {
        $this->queryService = $queryService;
        $this->queryDateService = $queryDateService;
      //  $this->queryContractService = $queryContractService;
        $this->domainQuery = $domainQuery;
    }

//     public function boqSummary(string $idProject)
//     {
//         return $this->queryService->getProjectBoqsSummary($idProject);
//     }
    function projectIncomeSummary(string $idProject)
    {
        /** @var $project Project */
        $project = $this->domainQuery->find($idProject);
        
        if(empty($project)) return null;
        
        $dates = $this->queryDateService->getProjectDateSummary($project->getId());
        $projectDate = [
            'startDate' => null,
            'finishDate' => null,
            'contract' => null,
        ];
        
        if(count($dates) > 0) {
            $projectDate['startDate'] = $dates[0]['boqStartDate'];
            $projectDate['finishDate'] = $dates[0]['boqFinishDate'];
            $projectDate['contract'] = $dates[0]['boqContract'];
        }
        
        
    //    $budget = $this->queryContractService->getProjectBoqDataSummary($project->getId());
    //    $projectContract = [
    //        'total' => null,
    //    ];
        
   //     if(count($budget) > 0) {
    //        $projectContract['total'] = $budget[0]['total'];
   //     }
        
        return [
            'project' => $project,
            'data' => $this->projectContractSummary($project->getId()),
            'dates' => $projectDate,
     //       'contract' => $projectContract,
        ];
    }
    
    function projectContractSummary(string $idProject)
    {

        $boq = $this->queryService->getAllProjectBoq($idProject);

        return $boq;
        
    }
}
