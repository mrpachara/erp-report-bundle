<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use Erp\Bundle\ReportBundle\Domain\CQRS\ProjectReportQuery as QueryInterface;
use Erp\Bundle\MasterBundle\Entity\Project;

class ProjectReportQueryService implements QueryInterface
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
        \Erp\Bundle\MasterBundle\Domain\CQRS\ProjectQuery $domainQuery
        
    ) {
        $this->queryService = $queryService;
        $this->queryDateService = $queryDateService;
        $this->domainQuery = $domainQuery;
    }

//     public function boqSummary(string $idProject)
//     {
//         return $this->queryService->getProjectBoqsSummary($idProject);
//     }
    function projectSummary(string $idProject)
    {
        /** @var $project Project */
        $project = $this->domainQuery->find($idProject);
        
        if(empty($project)) return null;
        
        $dates = $this->queryDateService->getProjectDateSummary($project->getId());
        
        $projectDate = [
            'startDate' => null,
            'finishDate' => null,
        ];
        if(count($dates) > 0) {
            $projectDate['startDate'] = $dates[0]['boqStartDate'];
            $projectDate['finishDate'] = $dates[0]['boqFinishDate'];
        }
        
        return [
            'project' => $project,
            'data' => $this->projectBoqSummary($project->getId()),
            'dates' => $projectDate,
        ];
    }
    
    function projectBoqSummary(string $idProject)
    {

        $boq = $this->queryService->getAllProjectBoq($idProject);

        return $boq;
        
    }
}
