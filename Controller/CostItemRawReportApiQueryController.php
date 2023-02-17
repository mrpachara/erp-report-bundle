<?php

namespace Erp\Bundle\ReportBundle\Controller;

use Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery;
use Erp\Bundle\MasterBundle\Domain\CQRS\CostItemQuery;
use Erp\Bundle\MasterBundle\Entity\CostItem;
use Erp\Bundle\ReportBundle\Authorization\CostItemRawReportAuthorization;
use Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery;
use FOS\RestBundle\Controller\Annotations as Rest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * CostItem Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/cost-item")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class CostItemRawReportApiQueryController
{
    use ReportGranterTrait;

    private CostItemQuery $domainQuery;

    private SettingQuery $settingQuery;

    private TempFileItemQuery $fileQuery;

    /**
     * @var CostItemRawReportAuthorization
     */
    protected $authorization;

    function __construct(
        CostItemQuery $domainQuery,
        SettingQuery $settingQuery,
        TempFileItemQuery $fileQuery,
        CostItemRawReportAuthorization $authorization
    ) {
        $this->domainQuery = $domainQuery;
        $this->settingQuery = $settingQuery;
        $this->fileQuery = $fileQuery;
        $this->authorization = $authorization;

        $this->grant($this->authorization->access());
    }

    /**
     * @Rest\Get("/export.{format}")
     */
    public function costItemDistributionPurchaseRequestSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        /** @var CostItem[] $data */
        $data = $this->domainQuery->findAll();
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                throw new BadRequestHttpException("No implementation for '${format}' yet.");
                break;
            case 'xlsx':
                $this->grant($this->authorization->excel());

                $costFormat = '#,##0.00_-;[Red]-#,##0.00_-;??"-"??_-;[Green]@_-';

                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->mergeCells("A1:H1");
                $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:H1')->getFont()->setSize(16)->setBold(true);
                $sheet->setCellValue('A1', 'รายงานข้อมูลสินค้า (COST ITEM REPORT)');
                $sheet->getStyle('A10:H10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle('A10:H10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A10:H10')->getFill()->getStartColor()->setRGB('DCDCDC');
                $sheet->getStyle('A10:H10')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A10:H10')->getAlignment()->setVertical('center');
                $sheet->setCellValue('A10', 'ลำดับ');
                $sheet->setCellValue('B10', 'รหัส');
                $sheet->setCellValue('C10', 'ประเภท');
                $sheet->setCellValue('D10', 'ชื่อ');
                $sheet->setCellValue('E10', 'หน่วย');
                $sheet->setCellValue('F10', 'ราคา/หน่วย');
                $sheet->setCellValue('G10', 'รายละเอียด');
                $sheet->setCellValue('H10', 'หมายเหตุ');

                $row = 11;
                $count = 1;
                $itemStartRow = $row;
                foreach ($data as $item) {
                    $sheet->setCellValue('A' . $row, $count);
                    $sheet->setCellValue('B' . $row, $item->getCode());
                    $sheet->setCellValue('C' . $row, $item->getType());
                    $sheet->setCellValue('D' . $row, $item->getName());
                    $sheet->setCellValue('E' . $row, $item->getUnit());
                    $sheet->setCellValue('F' . $row, $item->getPrice());
                    $sheet->setCellValue('G' . $row, $item->getDescription());
                    $sheet->setCellValue('H' . $row, $item->getRemark());
                    $row++;
                    $count++;
                }
                $itemEndRow = $row - 1;

                $tableEndRow = $itemEndRow;

                $sheet->getStyle("A{$itemStartRow}:C{$tableEndRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("E{$itemStartRow}:E{$tableEndRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A{$itemStartRow}:H{$tableEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("F{$itemStartRow}:F{$tableEndRow}")->getNumberFormat()->setFormatCode($costFormat);

                $writer = new Xlsx($spreadsheet);
                $writer->setPreCalculateFormulas(false);
                $fileName = 'RP-MT-CI_rev.2.1.0_' . date('Ymd_His', time()) . '.xlsx';
                $temp_file = tempnam(sys_get_temp_dir(), $fileName);
                $writer->save($temp_file);
                $response = new BinaryFileResponse($temp_file);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
                break;
            default:
                throw new BadRequestHttpException("Unknown format '${format}'");
        }
    }
}
