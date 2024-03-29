<?php

namespace Erp\Bundle\ReportBundle\Controller;

use Erp\Bundle\ReportBundle\Authorization\CostItemExpenseReportAuthorization;
use FOS\RestBundle\Controller\Annotations as Rest;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * CostItem Expense Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/cost-item-ep")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class CostItemExpenseReportApiQueryController
{
    use ReportGranterTrait;

    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\CostItemExpenseReportQuery
     */
    private $domainQuery;

    /**
     * @var \Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery
     */
    protected $settingQuery = null;

    /**
     * @var \Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery
     */
    protected $fileQuery = null;

    /**
     * @var \Twig_Environment
     */
    protected $templating;

    /**
     * @var \Erp\Bundle\DocumentBundle\Service\PDFService
     */
    protected $pdfService = null;

    /**
     * @var CostItemExpenseReportAuthorization
     */
    protected $authorization;

    /**
     * CostItemExpenseReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\CostItemExpenseReportQuery $domainQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\CostItemExpenseReportQuery $domainQuery,
        \Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery $settingQuery,
        \Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery $fileQuery,
        \Twig_Environment $templating,
        \Erp\Bundle\DocumentBundle\Service\PDFService $pdfService,
        CostItemExpenseReportAuthorization $authorization
    ) {
        $this->domainQuery = $domainQuery;
        $this->settingQuery = $settingQuery;
        $this->fileQuery = $fileQuery;
        $this->templating = $templating;
        $this->pdfService = $pdfService;
        $this->authorization = $authorization;

        $this->grant($this->authorization->access());
    }

    private function reduce(array $items): array
    {
        if (!$this->authorization->quantity()) {
            $items = \array_map(function ($item) {
                unset($item['quantity']);
                return $item;
            }, $items);
        }

        if (!$this->authorization->price()) {
            $items = \array_map(function ($item) {
                unset($item['price']);
                unset($item['total']);
                return $item;
            }, $items);
        }

        return $items;
    }

    private function getGroup(?array $filter = null, ?array &$filterDetail = null)
    {
        return $this->reduce(
            $this->domainQuery->costItemGroupExpenseSummary($filter, $filterDetail)
        );
    }

    private function getDistribution(?array $filter = null, ?array &$filterDetail = null)
    {
        return $this->reduce(
            $this->domainQuery->costItemDistributionExpenseSummary($filter, $filterDetail)
        );
    }

    /**
     * @Rest\Get("/group")
     */
    public function costItemGroupExpenseSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->getGroup($request->getQueryParams()),
        ];
    }

    /**
     * @Rest\Get("/distribution")
     */
    public function costItemDistributionExpenseSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->getDistribution($request->getQueryParams()),
        ];
    }

    /**
     * @Rest\Get("/distribution/quantity/export.{format}")
     */
    public function costItemDistributionExpenseSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->getDistribution($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $this->grant($this->authorization->pdf());

                $view = $this->templating->render('@ErpReport/pdf/cost-item-distribution-ep-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
                break;
            case 'xlsx':
                $this->grant($this->authorization->excel());

                $costFormat = '#,##0.00_-;[Red]-#,##0.00_-;??"-"??_-;[Green]@_-';

                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->mergeCells("A1:H1");
                $sheet->mergeCells("B2:H2");
                $sheet->mergeCells("B3:H3");
                $sheet->mergeCells("B4:H4");
                $sheet->mergeCells("B5:H5");
                $sheet->mergeCells("B6:H6");
                $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:H1')->getFont()->setSize(16)->setBold(true);
                $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('C7:C8')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('D7:D8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                $sheet->setCellValue('A1', 'รายงานปริมาณสินค้า โดย ใบจ่ายเงิน (CI-Quantity by EP)');
                $sheet->setCellValue('A2', 'โครงการ : ');
                $sheet->setCellValue('A3', 'งบประมาณ : ');
                $sheet->setCellValue('A4', 'ประเภท : ');
                $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
                $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
                $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
                // $sheet->setCellValue('A8', 'XXXXX : ');
                $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
                $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
                $sheet->setCellValue('E8', 'รายการสินค้า : ');
                $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
                $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
                $sheet->setCellValue('B4', (!isset($filterDetail['budgetType'])) ? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
                $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
                $sheet->setCellValue('B6', (!isset($filterDetail['vendor'])) ? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
                $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
                // $sheet->setCellValue('B8', (!isset($filterDetail['xxxXxxxx']))? 'xxxxx' : ($filterDetail['xxxXxxxx']? 'XXX' : 'XXX'));
                $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
                $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));
                $sheet->setCellValue('F8', (!isset($filterDetail['costItem'])) ? 'ทั้งหมด' : "[{$filterDetail['costItem']->getCode()}] {$filterDetail['costItem']->getName()}");

                $sheet->mergeCells('A9:A10');
                $sheet->mergeCells('B9:C9');
                $sheet->mergeCells('D9:H9');
                $sheet->getStyle('A9:H10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle('A9:H10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A9:H10')->getFill()->getStartColor()->setRGB('DCDCDC');
                $sheet->getStyle('A9:H10')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A9:H10')->getAlignment()->setVertical('center');
                $sheet->setCellValue('A9', 'ลำดับ');
                $sheet->setCellValue('B9', 'เอกสาร');
                $sheet->setCellValue('B10', 'เลขที่');
                $sheet->setCellValue('C10', 'สถานะ');
                $sheet->setCellValue('D9', 'ข้อมูลสินค้า');
                $sheet->setCellValue('D10', 'รหัส');
                $sheet->setCellValue('E10', 'ประเภท');
                $sheet->setCellValue('F10', 'รายการ');
                $sheet->setCellValue('G10', 'หน่วย');
                $sheet->setCellValue('H10', 'ปริมาณ');

                $row = 11;
                $count = 1;
                $itemStartRow = $row;
                foreach ($data as $item) {
                    $sheet->setCellValue('A' . $row, $count);
                    $sheet->setCellValue('B' . $row, $item['expenseCode']);
                    $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
                    $sheet->setCellValue('D' . $row, $item['code']);
                    $sheet->setCellValue('E' . $row, $item['type']);
                    $sheet->setCellValue('F' . $row, $item['name']);
                    $sheet->setCellValue('G' . $row, $item['unit']);
                    $sheet->setCellValue('H' . $row, $item['quantity']);
                    $row++;
                    $count++;
                }
                $itemEndRow = $row - 1;

                foreach (range('H', 'H') as $colName) {
                    $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
                }
                $row++;

                $tableEndRow = $row - 1;

                $sheet->getStyle("A{$itemStartRow}:E{$tableEndRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("G{$itemStartRow}:G{$tableEndRow}")->getAlignment()->setHorizontal('center');

                $sheet->getStyle("A{$itemStartRow}:H{$tableEndRow}")->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle("H{$itemStartRow}:H{$tableEndRow}")->getNumberFormat()
                    ->setFormatCode($costFormat);

                $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:H{$tableEndRow}");
                $itemFooterStyle->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('DCDCDC');
                $itemFooterStyle->getFont()
                    ->setBold(true);

                $sheet->getStyle("H{$tableEndRow}:H{$tableEndRow}")->getFont()
                    ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

                // Create your Office 2007 Excel (XLSX Format)
                $writer = new Xlsx($spreadsheet);
                $writer->setPreCalculateFormulas(false);

                // Create a Temporary file in the system
                $fileName = 'RP-MT-CI-Quantity-EP_rev.2.1.0_' . date('Ymd_His', time()) . '.xlsx';
                $temp_file = tempnam(sys_get_temp_dir(), $fileName);

                // Create the excel file in the tmp directory of the system
                $writer->save($temp_file);

                $response = new BinaryFileResponse($temp_file);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
                break;
            default:
                throw new BadRequestHttpException("Unsupported '{$format}' format.");
        }
    }

    /**
     * @Rest\Get("/distribution/price/export.{format}")
     */
    public function costItemDistributionExpensePriceSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->getDistribution($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $this->grant($this->authorization->pdf());

                $view = $this->templating->render('@ErpReport/pdf/cost-item-distribution-ep-price-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
                break;
            case 'xlsx':
                $this->grant($this->authorization->excel());

                $costFormat = '#,##0.00_-;[Red]-#,##0.00_-;??"-"??_-;[Green]@_-';

                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->mergeCells("A1:H1");
                $sheet->mergeCells("B2:H2");
                $sheet->mergeCells("B3:H3");
                $sheet->mergeCells("B4:H4");
                $sheet->mergeCells("B5:H5");
                $sheet->mergeCells("B6:H6");
                $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:H1')->getFont()->setSize(16)->setBold(true);
                $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('C7:C8')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('D7:D8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                $sheet->setCellValue('A1', 'รายงานมูลค่าสินค้า โดย ใบจ่ายเงิน (CI-Price by EP)');
                $sheet->setCellValue('A2', 'โครงการ : ');
                $sheet->setCellValue('A3', 'งบประมาณ : ');
                $sheet->setCellValue('A4', 'ประเภท : ');
                $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
                $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
                $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
                // $sheet->setCellValue('A8', 'XXXXX : ');
                $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
                $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
                $sheet->setCellValue('E8', 'รายการสินค้า : ');
                $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
                $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
                $sheet->setCellValue('B4', (!isset($filterDetail['budgetType'])) ? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
                $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
                $sheet->setCellValue('B6', (!isset($filterDetail['vendor'])) ? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
                $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
                // $sheet->setCellValue('B8', (!isset($filterDetail['xxxXxxxx']))? 'xxxxx' : ($filterDetail['xxxXxxxx']? 'XXX' : 'XXX'));
                $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
                $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));
                $sheet->setCellValue('F8', (!isset($filterDetail['costItem'])) ? 'ทั้งหมด' : "[{$filterDetail['costItem']->getCode()}] {$filterDetail['costItem']->getName()}");

                $sheet->mergeCells('A9:A10');
                $sheet->mergeCells('B9:C9');
                $sheet->mergeCells('D9:H9');
                $sheet->getStyle('A9:H10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle('A9:H10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A9:H10')->getFill()->getStartColor()->setRGB('DCDCDC');
                $sheet->getStyle('A9:H10')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A9:H10')->getAlignment()->setVertical('center');
                $sheet->setCellValue('A9', 'ลำดับ');
                $sheet->setCellValue('B9', 'เอกสาร');
                $sheet->setCellValue('B10', 'เลขที่');
                $sheet->setCellValue('C10', 'สถานะ');
                $sheet->setCellValue('D9', 'ข้อมูลสินค้า');
                $sheet->setCellValue('D10', 'รหัส');
                $sheet->setCellValue('E10', 'ประเภท');
                $sheet->setCellValue('F10', 'รายการ');
                $sheet->setCellValue('G10', 'หน่วย');
                $sheet->setCellValue('H10', 'มูลค่า(บาท)');

                $row = 11;
                $count = 1;
                $itemStartRow = $row;
                foreach ($data as $item) {
                    $sheet->setCellValue('A' . $row, $count);
                    $sheet->setCellValue('B' . $row, $item['expenseCode']);
                    $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
                    $sheet->setCellValue('D' . $row, $item['code']);
                    $sheet->setCellValue('E' . $row, $item['type']);
                    $sheet->setCellValue('F' . $row, $item['name']);
                    $sheet->setCellValue('G' . $row, $item['unit']);
                    $sheet->setCellValue('H' . $row, $item['total']);
                    $row++;
                    $count++;
                }
                $itemEndRow = $row - 1;

                foreach (range('H', 'H') as $colName) {
                    $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
                }
                $row++;

                $tableEndRow = $row - 1;

                $sheet->getStyle("A{$itemStartRow}:E{$tableEndRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("G{$itemStartRow}:G{$tableEndRow}")->getAlignment()->setHorizontal('center');

                $sheet->getStyle("A{$itemStartRow}:H{$tableEndRow}")->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle("H{$itemStartRow}:H{$tableEndRow}")->getNumberFormat()
                    ->setFormatCode($costFormat);

                $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:H{$tableEndRow}");
                $itemFooterStyle->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('DCDCDC');
                $itemFooterStyle->getFont()
                    ->setBold(true);

                $sheet->getStyle("H{$tableEndRow}:H{$tableEndRow}")->getFont()
                    ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

                // Create your Office 2007 Excel (XLSX Format)
                $writer = new Xlsx($spreadsheet);
                $writer->setPreCalculateFormulas(false);

                // Create a Temporary file in the system
                $fileName = 'RP-MT-CI-Price-EP_rev.2.1.0_' . date('Ymd_His', time()) . '.xlsx';
                $temp_file = tempnam(sys_get_temp_dir(), $fileName);

                // Create the excel file in the tmp directory of the system
                $writer->save($temp_file);

                $response = new BinaryFileResponse($temp_file);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
                break;
            default:
                throw new BadRequestHttpException("Unsupported '{$format}' format.");
        }
    }

    /**
     * @Rest\Get("/group/quantity/export.{format}")
     */
    public function costItemGroupExpenseSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->getGroup($request->getQueryParams(), $filterDetail);

        $profile = $this->settingQuery->findOneByCode('profile')->getValue();

        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $this->grant($this->authorization->pdf());

                $view = $this->templating->render('@ErpReport/pdf/cost-item-group-ep-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                ]);

                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });

                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
            default:
                throw new BadRequestHttpException("Unsupported '{$format}' format.");
        }
    }

    /**
     * @Rest\Get("/group/price/export.{format}")
     */
    public function costItemGroupExpensePriceSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->getGroup($request->getQueryParams(), $filterDetail);

        $profile = $this->settingQuery->findOneByCode('profile')->getValue();

        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $this->grant($this->authorization->pdf());

                $view = $this->templating->render('@ErpReport/pdf/cost-item-group-ep-price-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                ]);

                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });

                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
            default:
                throw new BadRequestHttpException("Unsupported '{$format}' format.");
        }
    }
}
