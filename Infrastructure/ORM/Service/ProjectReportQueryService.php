<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Erp\Bundle\ReportBundle\Domain\CQRS\ProjectReportQuery as QueryInterface;

use Erp\Bundle\MasterBundle\Entity\Project;

class ProjectReportQueryService implements QueryInterface
{
    /** @var \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\ProjectBoqSummaryQueryService */
    protected $queryService;

    public function __construct(
        \Erp\Bundle\DocumentBundle\Infrastructure\ORM\Service\ProjectBoqSummaryQueryService $queryService
    ) {
        $this->queryService = $queryService;
    }

    public function boqSummary(string $idProject)
    {
        return $this->queryService->getProjectBoqsSummary($idProject);
    }
}
