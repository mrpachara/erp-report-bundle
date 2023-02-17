<?php

namespace Erp\Bundle\ReportBundle\Controller;

use Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery;
use Erp\Bundle\MasterBundle\Domain\CQRS\EmployeeQuery;
use Erp\Bundle\MasterBundle\Entity\Employee;
use Erp\Bundle\ObjectValueBundle\Entity\Citizen;
use Erp\Bundle\ReportBundle\Authorization\EmployeeRawReportAuthorization;
use Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery;
use FOS\RestBundle\Controller\Annotations as Rest;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Person Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/employee")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class EmployeeRawReportApiQueryController
{
    use ReportGranterTrait;

    private EmployeeQuery $domainQuery;

    private SettingQuery $settingQuery;

    private TempFileItemQuery $fileQuery;

    /**
     * @var EmployeeRawReportAuthorization
     */
    protected $authorization;

    function __construct(
        EmployeeQuery $domainQuery,
        SettingQuery $settingQuery,
        TempFileItemQuery $fileQuery,
        EmployeeRawReportAuthorization $authorization
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
    public function exportAction(ServerRequestInterface $request, string $format)
    {
        /** @var Employee[] $data */
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

                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->mergeCells("A1:AP1");
                $sheet->mergeCells("A8:A10");
                $sheet->mergeCells("B8:B10");
                $sheet->mergeCells("C8:O8");
                $sheet->mergeCells("C9:C10");
                $sheet->mergeCells("D9:D10");
                $sheet->mergeCells("E9:E10");
                $sheet->mergeCells("F9:F10");
                $sheet->mergeCells("G9:G10");
                $sheet->mergeCells("H9:H10");
                $sheet->mergeCells("I9:I10");
                $sheet->mergeCells("J9:J10");
                $sheet->mergeCells("K9:K10");
                $sheet->mergeCells("L9:L10");
                $sheet->mergeCells("M9:M10");
                $sheet->mergeCells("N9:N10");
                $sheet->mergeCells("O9:O10");
                $sheet->mergeCells("P8:AA8");
                $sheet->mergeCells("P9:P10");
                $sheet->mergeCells("Q9:Q10");
                $sheet->mergeCells("R9:R10");
                $sheet->mergeCells("S9:S10");
                $sheet->mergeCells("T9:T10");
                $sheet->mergeCells("U9:U10");
                $sheet->mergeCells("V9:V10");
                $sheet->mergeCells("W9:W10");
                $sheet->mergeCells("X9:X10");
                $sheet->mergeCells("Y9:AA9");
                $sheet->mergeCells("AB8:AP8");
                $sheet->mergeCells("AB9:AF9");
                $sheet->mergeCells("AG9:AK9");
                $sheet->mergeCells("AL9:AP9");

                $sheet->getStyle('A1:AP1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:AP1')->getFont()->setSize(16)->setBold(true);
                $sheet->getStyle('A8:AP10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle('A8:AP10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A8:AP10')->getFill()->getStartColor()->setRGB('DCDCDC');
                $sheet->getStyle('A8:AP10')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A8:AP10')->getAlignment()->setVertical('center');
                //หัวเอกสาร
                $sheet->setCellValue('A1', 'รายงานข้อมูลบุคคลธรรมดา (INDIVIDUAL PERSON REPORT)');
                //เริ่มเอกสาร
                $sheet->setCellValue('A8', 'ลำดับ');
                $sheet->setCellValue('B8', 'รหัส');
                //ข้อมูลบัตรประชาชน
                $sheet->setCellValue('C8', 'ข้อมูลบัตรประชาชน');
                $sheet->setCellValue('C9', 'เลขประจำตัวประชาชน');
                $sheet->setCellValue('D9', 'คำนำหน้าชื่อ');
                $sheet->setCellValue('E9', 'ชื่อ');
                $sheet->setCellValue('F9', 'นามสกุล');
                $sheet->setCellValue('G9', 'ที่อยู่');
                $sheet->setCellValue('H9', 'ตำบล/แขวง');
                $sheet->setCellValue('I9', 'อำเภอ/เขต');
                $sheet->setCellValue('J9', 'จังหวัด');
                $sheet->setCellValue('K9', 'รหัสไปรษณีย์');
                $sheet->setCellValue('L9', 'วันเกิด');
                $sheet->setCellValue('M9', 'ศาสนา');
                $sheet->setCellValue('N9', 'วันออกบัตร');
                $sheet->setCellValue('O9', 'วันบัตรหมดอายุ');
                //ข้อมูลติดต่อ
                $sheet->setCellValue('P8', 'ข้อมูลติดต่อ');
                $sheet->setCellValue('P9', 'ที่อยู่');
                $sheet->setCellValue('Q9', 'ตำบล/แขวง');
                $sheet->setCellValue('R9', 'อำเภอ/เขต');
                $sheet->setCellValue('S9', 'จังหวัด');
                $sheet->setCellValue('T9', 'รหัสไปรษณีย์');
                $sheet->setCellValue('U9', 'ชื่อเรียก');
                $sheet->setCellValue('V9', 'ตำแหน่ง');
                $sheet->setCellValue('W9', 'E-mail');
                $sheet->setCellValue('X9', 'Line ID');
                $sheet->setCellValue('Y9', 'เบอร์โทรศัพท์');
                $sheet->setCellValue('Y10', 'เบอร์1');
                $sheet->setCellValue('Z10', 'เบอร์2');
                $sheet->setCellValue('AA10', 'เบอร์3');
                //ข้อมูลบัญชีธนาคาร
                $sheet->setCellValue('AB8', 'ข้อมูลบัญชีธนาคาร');
                $sheet->setCellValue('AB9', 'บัญชีธนาคาร1');
                $sheet->setCellValue('AB10', 'เลขที่');
                $sheet->setCellValue('AC10', 'ชื่อ');
                $sheet->setCellValue('AC10', 'ประเภท');
                $sheet->setCellValue('AD10', 'ธนาคาร');
                $sheet->setCellValue('AE10', 'สาขา');
                $sheet->setCellValue('AF9', 'บัญชีธนาคาร2');
                $sheet->setCellValue('AG10', 'เลขที่');
                $sheet->setCellValue('AH10', 'ชื่อ');
                $sheet->setCellValue('AI10', 'ประเภท');
                $sheet->setCellValue('AJ10', 'ธนาคาร');
                $sheet->setCellValue('AK10', 'สาขา');
                $sheet->setCellValue('AL9', 'บัญชีธนาคาร3');
                $sheet->setCellValue('AL10', 'เลขที่');
                $sheet->setCellValue('AM10', 'ชื่อ');
                $sheet->setCellValue('AN10', 'ประเภท');
                $sheet->setCellValue('AO10', 'ธนาคาร');
                $sheet->setCellValue('AP10', 'สาขา');



                $row = 11;
                $count = 1;
                $itemStartRow = $row;
                foreach ($data as $item) {
                    $individual = $item->getIndividual();

                    $personData = $individual->getPersonData();

                    if ($personData instanceof Citizen) {
                        $sheet->setCellValue('A' . $row, $count);
                        $sheet->setCellValue('B' . $row, $item->getCode());
                        $sheet->setCellValueExplicit('C' . $row, $personData->getCode(), DataType::TYPE_STRING2);
                        $sheet->setCellValue('D' . $row, $personData->getInitname());
                        $sheet->setCellValue('E' . $row, $personData->getFirstname());
                        $sheet->setCellValue('F' . $row, $personData->getLastname());
                        $sheet->setCellValue('G' . $row, $personData->getAddress()->getAddress());
                        $sheet->setCellValue('H' . $row, $personData->getAddress()->getSubdistrict());
                        $sheet->setCellValue('I' . $row, $personData->getAddress()->getDistrict());
                        $sheet->setCellValue('J' . $row, $personData->getAddress()->getProvince());
                        $sheet->setCellValue('K' . $row, $personData->getAddress()->getPostalcode());
                        $sheet->setCellValue('L' . $row, empty($personData->getBirthDate()) ? '' : Date::PHPToExcel($personData->getBirthDate()));
                        $sheet->setCellValue('M' . $row, $personData->getReligious());
                        $sheet->setCellValue('N' . $row, empty($personData->getIssueDate()) ? '' : Date::PHPToExcel($personData->getIssueDate()));
                        $sheet->setCellValue('O' . $row, empty($personData->getExpiredDate()) ? '' : Date::PHPToExcel($personData->getExpiredDate()));

                        $sheet->setCellValue('P' . $row, $individual->getAddress()->getAddress());
                        $sheet->setCellValue('Q' . $row, $individual->getAddress()->getSubdistrict());
                        $sheet->setCellValue('R' . $row, $individual->getAddress()->getDistrict());
                        $sheet->setCellValue('S' . $row, $individual->getAddress()->getProvince());
                        $sheet->setCellValue('T' . $row, $individual->getAddress()->getPostalcode());
                        $sheet->setCellValue('U' . $row, $individual->getContact()->getAlias());
                        $sheet->setCellValue('V' . $row, $individual->getContact()->getPosition());
                        $sheet->setCellValue('W' . $row, $individual->getContact()->getEmail());
                        $sheet->setCellValue('X' . $row, $individual->getContact()->getLineId());
                        $sheet->setCellValue('Y' . $row, empty($individual->getContact()->getPhones()[0]) ? null : $individual->getContact()->getPhones()[0]->getPhone());
                        $sheet->setCellValue('Z' . $row, empty($individual->getContact()->getPhones()[1]) ? null : $individual->getContact()->getPhones()[1]->getPhone());
                        $sheet->setCellValue('AA' . $row, empty($individual->getContact()->getPhones()[2]) ? null : $individual->getContact()->getPhones()[2]->getPhone());
                        //$sheet->setCellValue('AB'.$row, $item->getPhone());
                        //$sheet->setCellValue('AC'.$row, $item->getPhone());

                        $bankAccount0 = $individual->getBankAccounts()[0] ?? null;
                        if ($bankAccount0 !== null) {
                            $sheet->setCellValue('AB' . $row, $bankAccount0->getCode());
                            $sheet->setCellValue('AC' . $row, $bankAccount0->getName());
                            $sheet->setCellValue('AD' . $row, $bankAccount0->getCategory());
                            $sheet->setCellValue('AE' . $row, $bankAccount0->getBank());
                            $sheet->setCellValue('AF' . $row, $bankAccount0->getBranch());
                        }

                        $bankAccount1 = $individual->getBankAccounts()[1] ?? null;
                        if ($bankAccount1 !== null) {
                            $sheet->setCellValue('AG' . $row, $bankAccount1->getCode());
                            $sheet->setCellValue('AH' . $row, $bankAccount1->getName());
                            $sheet->setCellValue('AI' . $row, $bankAccount1->getCategory());
                            $sheet->setCellValue('AJ' . $row, $bankAccount1->getBank());
                            $sheet->setCellValue('AK' . $row, $bankAccount1->getBranch());
                        }

                        $bankAccount2 = $individual->getBankAccounts()[2] ?? null;
                        if ($bankAccount2 !== null) {
                            $sheet->setCellValue('AL' . $row, $bankAccount2->getCode());
                            $sheet->setCellValue('AM' . $row, $bankAccount2->getName());
                            $sheet->setCellValue('AN' . $row, $bankAccount2->getCategory());
                            $sheet->setCellValue('AO' . $row, $bankAccount2->getBank());
                            $sheet->setCellValue('AP' . $row, $bankAccount2->getBranch());
                        }
                    }

                    $row++;
                    $count++;
                }
                $itemEndRow = $row - 1;

                $tableEndRow = $itemEndRow;

                $sheet->getStyle("A{$itemStartRow}:AP{$tableEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$itemStartRow}:F{$tableEndRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("H{$itemStartRow}:O{$tableEndRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("Q{$itemStartRow}:Z{$tableEndRow}")->getAlignment()->setHorizontal('center');
                // $sheet->getStyle("F{$itemStartRow}:F{$tableEndRow}")->getNumberFormat()->setFormatCode($costFormat);
                $sheet->getStyle("L{$itemStartRow}:L{$tableEndRow}")->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                $sheet->getStyle("N{$itemStartRow}:O{$tableEndRow}")->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                //$sheet->getStyle("C{$itemStartRow}:C{$tableEndRow}")->getNumberFormat()->setFormatCode('@');

                $writer = new Xlsx($spreadsheet);
                $writer->setPreCalculateFormulas(false);
                $fileName = 'RP-MT-PS-Individual_rev.2.1.0_' . date('Ymd_His', time()) . '.xlsx';
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
