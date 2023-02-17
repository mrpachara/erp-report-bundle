<?php

namespace Erp\Bundle\ReportBundle\Controller;

use Erp\Bundle\ReportBundle\Authorization\ProjectBoqPurchaseOrderReportAuthorization;
use FOS\RestBundle\Controller\Annotations as Rest;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Project Boq Purchase Order Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/project-boq-po")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class ProjectBoqPurchaseOrderReportApiQueryController
{
    use ReportGranterTrait;

    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqPurchaseOrderReportQuery
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
     * @var ProjectBoqPurchaseOrderReportAuthorization
     */
    protected $authorization;

    /**
     * ProjectBoqPurchaseOrderReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqPurchaseOrderReportQuery $domainQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqPurchaseOrderReportQuery $domainQuery,
        \Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery $settingQuery,
        \Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery $fileQuery,
        \Twig_Environment $templating,
        \Erp\Bundle\DocumentBundle\Service\PDFService $pdfService,
        ProjectBoqPurchaseOrderReportAuthorization $authorization
    ) {
        $this->domainQuery = $domainQuery;
        $this->settingQuery = $settingQuery;
        $this->fileQuery = $fileQuery;
        $this->templating = $templating;
        $this->pdfService = $pdfService;
        $this->authorization = $authorization;

        $this->grant($this->authorization->access());
    }

    /**
     * @Rest\Get("/{id}")
     */
    public function projectBoqPOSummaryAction(ServerRequestInterface $request, $id)
    {
        return [
            'data' => $this->domainQuery->projectBoqPOSummary($id),
        ];
    }

    /**
     * @Rest\Get("/{id}/{idBoq}")
     */
    public function projectBoqPOSummaryEachAction(ServerRequestInterface $request, $id, $idBoq)
    {
        return [
            'data' => $this->domainQuery->projectBoqPOSummaryEach($id, $idBoq),
        ];
    }

    /**
     * @Rest\Get("/{id}/{idBoq}/export.{format}")
     */
    public function projectBoqPOSummaryExportAction(ServerRequestInterface $request, $id, $idBoq, string $format)
    {
        $data = $this->domainQuery->projectBoqPOSummaryEach($id, $idBoq);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $this->grant($this->authorization->pdf());

                $view = $this->templating->render('@ErpReport/pdf/project-boq-po-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4-L'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
                break;
            case 'xlsx':
                $this->grant($this->authorization->excel());

                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // START: write data

                $qs = $request->getQueryParams();
                $withFormular = empty($qs['raw']);
                $costFormat = '#,##0.00_-;[Red]-#,##0.00_-;??"-"??_-;[Green]@_-';
                $fieldMap = [
                    'budget' => 'ที่มี',
                    'cost' => 'ใช้ไป',
                    'remain' => 'คงเหลือ',
                ];
                $costStartColumn = 3;
                $row = 1;
                foreach ($data as $item) {
                    $itemStartRow = $row;
                    $sheet->setCellValue("A{$row}", 'รายงานงบประมาณโครงการ โดย ใบสั่งซื้อ (PJ-Budget by PO)');
                    $row++;
                    $labelRow = $row;
                    $sheet->setCellValue("A{$row}", 'โครงการ : ');
                    $sheet->setCellValue("B{$row}", "[{$item['projectCode']}] {$item['projectName']}");
                    $row++;
                    $sheet->setCellValue("A{$row}", 'งบประมาณ : ');
                    $sheet->setCellValue("B{$row}", $item['name']);
                    $row++;

                    $labelStyle = $sheet->getStyleByColumnAndRow(1, $labelRow, 1, $row - 1);
                    $labelStyle->getFont()
                        ->setBold(true);
                    $labelStyle->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $row += 1;

                    $itemEndColumn = null;
                    $sheet->mergeCells("A{$row}:A" . ($row + 1));
                    $sheet->setCellValue("A{$row}", 'สำดับ');
                    $sheet->mergeCells("B{$row}:B" . ($row + 1));
                    $sheet->setCellValue("B{$row}", 'รายการ');
                    $column = $costStartColumn;
                    $totalFields = [];
                    foreach (array_keys($fieldMap) as $field) {
                        $totalFields[$field] = [];
                    }

                    foreach ($item['cost']['columns'] as $costColumn) {
                        $sheet->mergeCellsByColumnAndRow($column, $row, $column + count($fieldMap) - 1, $row);
                        $sheet->setCellValueByColumnAndRow($column, $row, ($costColumn['name'] === 'total') ? 'รวม' : $costColumn['name']);
                        foreach (array_keys($fieldMap) as $field) {
                            $sheet->setCellValueByColumnAndRow($column, $row + 1, $fieldMap[$field]);
                            if ($costColumn['name'] !== 'total') {
                                $columnName = Coordinate::stringFromColumnIndex($column);
                                $totalFields[$field][] = "{$columnName}:{$columnName}";
                            }
                            $column++;
                        }
                        $itemEndColumn = $column - 1;
                    }

                    $sheet->mergeCellsByColumnAndRow(1, $itemStartRow, $itemEndColumn, $itemStartRow);
                    $titleStyle = $sheet->getStyleByColumnAndRow(1, $itemStartRow, $itemEndColumn, $itemStartRow);
                    $titleStyle->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    $titleStyle->getFont()
                        ->setSize(16)
                        ->setBold(true);

                    $headerStyle = $sheet->getStyleByColumnAndRow(1, $row, $itemEndColumn, $row + 1);
                    $headerStyle->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    $headerStyle->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);
                    $headerStyle->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('DCDCDC');
                    $row += 2;

                    $itemBoqStartRow = $row;
                    $totalBoq = null;
                    foreach ($item['cost']['data'] as $boq) {
                        if ($boq['number'] === '') {
                            $totalBoq = $boq;
                            continue;
                        }

                        $indent = substr_count($boq['number'], '.');
                        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$row}")->getAlignment()->setIndent($indent);
                        $sheet->setCellValueExplicit("A{$row}", $boq['number'], DataType::TYPE_STRING);
                        $sheet->setCellValue("B{$row}", $boq['name']);
                        $column = $costStartColumn;
                        foreach ($item['cost']['columns'] as $i => $costColumn) {
                            if (!$boq['isTotal']) {
                                foreach (array_keys($fieldMap) as $field) {
                                    if ($withFormular && $costColumn['name'] === 'total') {
                                        $sheet->setCellValueByColumnAndRow(
                                            $column,
                                            $row,
                                            "=SUM((" . implode(',', $totalFields[$field]) . ") {$row}:{$row})"
                                        );
                                    } else {
                                        $sheet->setCellValueByColumnAndRow($column, $row, $boq['costs'][$i][$field]);
                                    }
                                    $column++;
                                }
                            }
                        }
                        $row++;
                    }

                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->setCellValue("A{$row}", 'รวม');
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $column = $costStartColumn;
                    foreach ($item['cost']['columns'] as $i => $costColumn) {
                        foreach (array_keys($fieldMap) as $field) {
                            if ($withFormular) {
                                if ($costColumn['name'] === 'total') {
                                    $sheet->setCellValueByColumnAndRow(
                                        $column,
                                        $row,
                                        "=SUM((" . implode(',', $totalFields[$field]) . ") {$row}:{$row})"
                                    );
                                } else {
                                    $itemBoqEndRow = $row - 1;
                                    $columnName = Coordinate::stringFromColumnIndex($column);
                                    $sheet->setCellValueByColumnAndRow(
                                        $column,
                                        $row,
                                        "=SUM({$itemBoqStartRow}:{$itemBoqEndRow} {$columnName}:{$columnName})"
                                    );
                                }
                            } else {
                                $sheet->setCellValueByColumnAndRow($column, $row, $totalBoq['costs'][$i][$field]);
                            }
                            $column++;
                        }
                    }
                    $row++;

                    $itemDataStyle = $sheet->getStyleByColumnAndRow(1, $itemBoqStartRow, $itemEndColumn, $row - 1);
                    $itemDataStyle->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);
                    $itemBoqStyle = $sheet->getStyleByColumnAndRow($costStartColumn, $itemBoqStartRow, $itemEndColumn, $row - 1);
                    $itemBoqStyle->getNumberFormat()
                        ->setFormatCode($costFormat);
                    $itemFooterStyle = $sheet->getStyleByColumnAndRow(1, $row - 1, $itemEndColumn, $row - 1);
                    $itemFooterStyle->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('DCDCDC');
                    $itemFooterStyle->getFont()
                        ->setBold(true);
                }

                // END: write data

                $writer = new Xlsx($spreadsheet);
                $writer->setPreCalculateFormulas(false);
                $fileName = 'RP-MT-PJ-Budget-PO_' . "{$item['projectCode']}-{$item['name']}" . '_' . date('Ymd_His', time()) . '.xlsx';
                $temp_file = tempnam(sys_get_temp_dir(), $fileName);
                $writer->save($temp_file);
                $response = new BinaryFileResponse($temp_file);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
                break;
            default:
                throw new BadRequestHttpException("Unsupported '{$format}' format.");
        }
    }
}
