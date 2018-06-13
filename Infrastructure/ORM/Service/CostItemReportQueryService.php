<?php

namespace Erp\Bundle\ReportBundle\Infrastructure\ORM\Service;

use \Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Erp\Bundle\ReportBundle\Domain\CQRS\CostItemReportQuery as QueryInterface;
use Erp\Bundle\DocumentBundle\Entity\PurchaseDetail;

class CostItemReportQueryService implements QueryInterface
{
    /** @var EntityRepository */
    protected $repository;

    /** @required */
    function setRepository(\symfony\Bridge\Doctrine\RegistryInterface $doctrine)
    {
        $this->repository = $doctrine->getRepository('ErpDocumentBundle:PurchaseOrderDetail');
    }

    function costItemQueryBuilder($alias)
    {
        $qb = $this->repository->createQueryBuilder($alias);
        $qb
            ->select("{$alias}_costItem.code AS code")
            ->addSelect("{$alias}_costItem.type AS type")
            ->addSelect("{$alias}_thing.name AS name")
            ->addSelect("{$alias}_costItem.unit AS unit")
            ->addSelect("{$alias}_costItem.price AS price")
            ->addSelect("{$alias}.quantity AS quantity")
            ->addSelect("{$alias}.total AS total")
            ->addSelect("{$alias}_purchase.code AS purchaseOrderCode")
            ->addSelect("{$alias}_requester.code AS requester")
            ->addSelect("{$alias}_vendor.code AS vendor")
            ->addSelect("{$alias}_project.code AS project")
            ->addSelect("{$alias}_boq.name AS boq")
            ->addSelect("{$alias}_budgetType.name AS budgetType")

            //->addSelect("{$alias}_budgetType.name AS budgetType")
            ->leftJoin("{$alias}.purchase","{$alias}_purchase")
            ->leftJoin("{$alias}.costItem","{$alias}_costItem")
            ->leftJoin("{$alias}_costItem.thing","{$alias}_thing")
            ->leftJoin("{$alias}_purchase.requester","{$alias}_requester")
            ->leftJoin("{$alias}_purchase.vendor","{$alias}_vendor")
            ->leftJoin("{$alias}_purchase.project","{$alias}_project")
            ->leftJoin("{$alias}_purchase.boq","{$alias}_boq")
            ->leftJoin("{$alias}_purchase.budgetType","{$alias}_budgetType")

            //->groupBy("{$alias}")
        ;

        return $qb;
    }

    function costItemSummary(array $filter = null)
    {
        $qb = $this->costItemQueryBuilder('_entity');
        if(!empty($filter['start'])) {
            $qb
                ->andWhere('_entity.tstmp >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($filter['start']))
            ;
        }
        if(!empty($filter['end'])) {
            $qb
                ->andWhere('_entity.tstmp <= :endDate')
                ->setParameter('endDate', new \DateTimeImmutable($filter['end']))
            ;
        }
        //if(!empty($filter['requester'])) {
        //    $qb
        //        ->andWhere('_entity_requester = :requester')
        //        ->setParameter('requester', $filter['requester'])
        //    ;
        //}
        //if(!empty($filter['vendor'])) {
        //    $qb
        //        ->andWhere('_entity_vendor = :vendor')
        //        ->setParameter('vendor', $filter['vendor'])
        //    ;
        //}
        //if(!empty($filter['projectname'])) {
        //    $qb
        //        ->andWhere('_entity_project.name = :projectname')
         //       ->setParameter('projectname', $filter['projectname'])
         //   ;
        //}
        //if(!empty($filter['projectCode'])) {
        //    $qb
        //        ->andWhere('_entity_project.code = :projectCode')
        //        ->setParameter('projectCode', $filter['projectCode'])
        //    ;
        //}

        return $qb->getQuery()->getArrayResult();

    }

}