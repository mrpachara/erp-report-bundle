<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Erp\Bundle\ReportBundle\Domain\CQRS\ProjectContractReportQuery as QueryInterface;
use Erp\Bundle\DocumentBundle\Entity\IncomeDetail;
use Erp\Bundle\DocumentBundle\Entity\BillingNote;
use Erp\Bundle\DocumentBundle\Entity\TaxInvoice;
use Erp\Bundle\DocumentBundle\Entity\Revenue;
use Erp\Bundle\MasterBundle\Entity\ProjectBoq;
use Doctrine\ORM\EntityManager;
use Erp\Bundle\DocumentBundle\Entity\DeliveryNote;
class ProjectContractReportQueryService implements QueryInterface
{
    /** @var EntityManager */
    protected $em;
    
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
        $this->em = $doctrine->getEntityManagerForClass('ErpDocumentBundle:Income');
        $this->repository = $doctrine->getRepository('ErpDocumentBundle:Income');
        $this->queryService = $queryService;
        
        $this->employeeRepos = $doctrine->getRepository('ErpMasterBundle:Employee');
        $this->vendorRepos = $doctrine->getRepository('ErpMasterBundle:Vendor');
        $this->boqRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoq');
        $this->budgetTypeRepos = $doctrine->getRepository('ErpMasterBundle:ProjectBoqBudgetType');
        $this->incomeDetailRepos = $doctrine->getRepository('ErpDocumentBundle:IncomeDetail');
    }

    function projectContractSummary(string $idProject)
    {
        $boqs = $this->queryService->getAllProjectBoq($idProject);
        
        return $this->prepareResult($boqs);
    }

    function projectContractSummaryEach(string $idProject, string $id) {
        $contract = $this->queryService->getAllProjectContractByBoq($idProject, $id);
        return $this->prepareResult($contract);
    }
    
    function prepareResult(array $incomes)
    {
        $results = [];
        
        /**
         * @var $incomeDetail IncomeDetail
         */
        foreach($incomes as $inx => $income) {
            $project = $income->getProject();
            $boq = $income->getBoq();
            
            if(!$income->updatable()) continue;
            
            
            $result = [
                'approved' => $income->getApproved(),
                'projectCode' => $project->getCode(),
                'projectName' => $project->getName(),
                'budgetName' => $boq->getName(),
                'dtype' => $this->em->getClassMetadata(get_class($income))->discriminatorValue,
                'docCode' => $income->getCode(),
                'detailText' => implode(', ', array_reduce($income->getDetails(), function($carry, $detail) {
                    $carry[] = $detail->getName();
                    return $carry;
                }, [])),
                'contract' => 0,
                'vatCost' => 0,
                'excludeVat' => 0,
                'taxCost' => 0,
                'payTotal' => 0,
                'retention' => 0,
                'retentionCost' => 0,
                'retentionPayTotal' => 0,
                'paymentDate' => null,
            ];
            
            if(
                ($income instanceof DeliveryNote)
                ) {
                    $result['contract'] = $income->getTotal();
                    $result['vatCost'] = 0;
                    $result['excludeVat'] = $income->getTotal();
                    $result['taxCost'] = 0;
                    $result['payTotal'] = $income->getTotal();
                    $result['retention'] = 0;
                    $result['retentionCost'] = 0;
                    $result['retentionPayTotal'] = $income->getTotal();
                    $result['paymentDate'] = null;
                } else {
                    $result['contract'] = $income->getDocTotal();
                    $result['vatCost'] = $income->getVatCost();
                    $result['excludeVat'] = $income->getExcludeVat();
                    $result['taxCost'] = $income->getTaxCost();
                    $result['payTotal'] = $income->getPayTotal();
                    $result['retention'] = $income->getRetention();
                    $result['retentionCost'] = $income->getRetentionCost();
                    $result['retentionPayTotal'] = $income->getRetentionPayTotal();
                    $result['paymentDate'] = $income->getPaymentDate();
                }
                
                
            
            $results[] = $result;
        }
        
        return $results;
    }
}
