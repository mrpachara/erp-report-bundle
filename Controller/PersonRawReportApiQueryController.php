<?php

namespace Erp\Bundle\ReportBundle\Controller;

use Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery;
use Erp\Bundle\MasterBundle\Domain\CQRS\PersonQuery;
use Erp\Bundle\MasterBundle\Entity\Person;
use Erp\Bundle\MasterBundle\Entity\PersonCorporate;
use Erp\Bundle\MasterBundle\Entity\PersonIndividual;
use Erp\Bundle\ObjectValueBundle\Entity\Citizen;
use Erp\Bundle\ObjectValueBundle\Entity\Corporate;
use Erp\Bundle\ReportBundle\Authorization\PersonRawReportAuthorization;
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
 * @Rest\Route("/api/report/person")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class PersonRawReportApiQueryController
{
    use ReportGranterTrait;

    private PersonQuery $domainQuery;

    private SettingQuery $settingQuery;

    private TempFileItemQuery $fileQuery;

    /**
     * @var PersonRawReportAuthorization
     */
    protected $authorization;

    function __construct(
        PersonQuery $domainQuery,
        SettingQuery $settingQuery,
        TempFileItemQuery $fileQuery,
        PersonRawReportAuthorization $authorization
    ) {
        $this->domainQuery = $domainQuery;
        $this->settingQuery = $settingQuery;
        $this->fileQuery = $fileQuery;
        $this->authorization = $authorization;

        $this->grant($this->authorization->access());
    }

    /**
     * @Rest\Get("/individual/export.{format}")
     */
    public function individualExportAction(ServerRequestInterface $request, string $format)
    {
        /** @var Person[] $data */
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
                $sheet->setCellValue('AD10', 'ประเภท');
                $sheet->setCellValue('AE10', 'ธนาคาร');
                $sheet->setCellValue('AF10', 'สาขา');
                $sheet->setCellValue('AG9', 'บัญชีธนาคาร2');
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
                    if (!($item instanceof PersonIndividual)) {
                        continue;
                    }

                    $personData = $item->getPersonData();

                    if ($personData instanceof Citizen) {
                        $sheet->setCellValue('A' . $row, $count);
                        $sheet->setCellValue('B' . $row, $item->getCode());
                        $sheet->setCellValueExplicit('C' . $row, $item->getPersonData()->getCode(), DataType::TYPE_STRING2);
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

                        $sheet->setCellValue('P' . $row, $item->getAddress()->getAddress());
                        $sheet->setCellValue('Q' . $row, $item->getAddress()->getSubdistrict());
                        $sheet->setCellValue('R' . $row, $item->getAddress()->getDistrict());
                        $sheet->setCellValue('S' . $row, $item->getAddress()->getProvince());
                        $sheet->setCellValue('T' . $row, $item->getAddress()->getPostalcode());
                        $sheet->setCellValue('U' . $row, $item->getContact()->getAlias());
                        $sheet->setCellValue('V' . $row, $item->getContact()->getPosition());
                        $sheet->setCellValue('W' . $row, $item->getContact()->getEmail());
                        $sheet->setCellValue('X' . $row, $item->getContact()->getLineId());
                        $sheet->setCellValue('Y' . $row, empty($item->getContact()->getPhones()[0]) ? null : $item->getContact()->getPhones()[0]->getPhone());
                        $sheet->setCellValue('Z' . $row, empty($item->getContact()->getPhones()[1]) ? null : $item->getContact()->getPhones()[1]->getPhone());
                        $sheet->setCellValue('AA' . $row, empty($item->getContact()->getPhones()[2]) ? null : $item->getContact()->getPhones()[2]->getPhone());
                        //$sheet->setCellValue('AB'.$row, $item->getPhone());
                        //$sheet->setCellValue('AC'.$row, $item->getPhone());

                        $bankAccount0 = $item->getBankAccounts()[0] ?? null;
                        if ($bankAccount0 !== null) {
                            $sheet->setCellValue('AB' . $row, $bankAccount0->getCode());
                            $sheet->setCellValue('AC' . $row, $bankAccount0->getName());
                            $sheet->setCellValue('AD' . $row, $bankAccount0->getCategory());
                            $sheet->setCellValue('AE' . $row, $bankAccount0->getBank());
                            $sheet->setCellValue('AF' . $row, $bankAccount0->getBranch());
                        }

                        $bankAccount1 = $item->getBankAccounts()[1] ?? null;
                        if ($bankAccount1 !== null) {
                            $sheet->setCellValue('AG' . $row, $bankAccount1->getCode());
                            $sheet->setCellValue('AH' . $row, $bankAccount1->getName());
                            $sheet->setCellValue('AI' . $row, $bankAccount1->getCategory());
                            $sheet->setCellValue('AJ' . $row, $bankAccount1->getBank());
                            $sheet->setCellValue('AK' . $row, $bankAccount1->getBranch());
                        }

                        $bankAccount2 = $item->getBankAccounts()[2] ?? null;
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

    /**
     * @Rest\Get("/corporate/export.{format}")
     */
    public function corporateExportAction(ServerRequestInterface $request, string $format)
    {
        /** @var Person[] $data */
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
                $sheet->mergeCells("A1:BC1");
                $sheet->mergeCells("A8:A10");
                $sheet->mergeCells("B8:B10");
                $sheet->mergeCells("C8:L8");
                $sheet->mergeCells("C9:C10");
                $sheet->mergeCells("D9:D10");
                $sheet->mergeCells("E9:E10");
                $sheet->mergeCells("F9:F10");
                $sheet->mergeCells("G9:G10");
                $sheet->mergeCells("H9:L9");
                $sheet->mergeCells("M8:AN8");
                $sheet->mergeCells("M9:Q9");
                $sheet->mergeCells("R9:R10");
                $sheet->mergeCells("S9:S10");
                $sheet->mergeCells("T9:Z9");
                $sheet->mergeCells("AA9:AG9");
                $sheet->mergeCells("AH9:AN9");
                $sheet->mergeCells("AO8:BC8");
                $sheet->mergeCells("AO9:AS9");
                $sheet->mergeCells("AT9:AX9");
                $sheet->mergeCells("AY9:BC9");

                $sheet->getStyle('A1:BC1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:BC1')->getFont()->setSize(16)->setBold(true);
                $sheet->getStyle('A8:BC10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle('A8:BC10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A8:BC10')->getFill()->getStartColor()->setRGB('DCDCDC');
                $sheet->getStyle('A8:BC10')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A8:BC10')->getAlignment()->setVertical('center');
                //หัวเอกสาร
                $sheet->setCellValue('A1', 'รายงานข้อมูลนิติบุคคล (CORPORATE PERSON REPORT)');
                //เริ่มเอกสาร
                $sheet->setCellValue('A8', 'ลำดับ');
                $sheet->setCellValue('B8', 'รหัส');
                //ข้อมูลทะเบียนนิติบุคคล
                $sheet->setCellValue('C8', 'ข้อมูลทะเบียนนิติบุคคล');
                $sheet->setCellValue('C9', 'เลขทะเบียน');
                $sheet->setCellValue('D9', 'วันจดทะเบียน');
                $sheet->setCellValue('E9', 'ประเภท');
                $sheet->setCellValue('F9', 'ชื่อ');
                $sheet->setCellValue('G9', 'สำนักงานใหญ่/สาขา');
                $sheet->setCellValue('H9', 'ที่อยู่ตามทะเบียน');
                $sheet->setCellValue('H10', 'ที่อยู่');
                $sheet->setCellValue('I10', 'ตำบล/แขวง');
                $sheet->setCellValue('J10', 'อำเภอ/เขต');
                $sheet->setCellValue('K10', 'จังหวัด');
                $sheet->setCellValue('L10', 'รหัสไปรษณีย์');
                //ข้อมูลติดต่อ
                $sheet->setCellValue('M8', 'ข้อมูลติดต่อ');
                $sheet->setCellValue('M9', 'ที่อยู่ติดต่อ');
                $sheet->setCellValue('M10', 'ที่อยู่');
                $sheet->setCellValue('N10', 'ตำบล/แขวง');
                $sheet->setCellValue('O10', 'อำเภอ/เขต');
                $sheet->setCellValue('P10', 'จังหวัด');
                $sheet->setCellValue('Q10', 'รหัสไปรษณีย์');
                $sheet->setCellValue('R9', 'โทรศัพท์');
                $sheet->setCellValue('S9', 'แฟ็กซ์');
                $sheet->setCellValue('T9', 'ผู้ติดต่อ1');
                $sheet->setCellValue('T10', 'ชื่อเรียก');
                $sheet->setCellValue('U10', 'ตำแหน่ง');
                $sheet->setCellValue('V10', 'E-mail');
                $sheet->setCellValue('W10', 'Line ID');
                $sheet->setCellValue('X10', 'เบอร์1');
                $sheet->setCellValue('Y10', 'เบอร์2');
                $sheet->setCellValue('Z10', 'เบอร์3');
                $sheet->setCellValue('AA9', 'ผู้ติดต่อ2');
                $sheet->setCellValue('AA10', 'ชื่อเรียก');
                $sheet->setCellValue('AB10', 'ตำแหน่ง');
                $sheet->setCellValue('AC10', 'E-mail');
                $sheet->setCellValue('AD10', 'Line ID');
                $sheet->setCellValue('AE10', 'เบอร์1');
                $sheet->setCellValue('AF10', 'เบอร์2');
                $sheet->setCellValue('AG10', 'เบอร์3');
                $sheet->setCellValue('AH9', 'ผู้ติดต่อ3');
                $sheet->setCellValue('AH10', 'ชื่อเรียก');
                $sheet->setCellValue('AI10', 'ตำแหน่ง');
                $sheet->setCellValue('AJ10', 'E-mail');
                $sheet->setCellValue('AK10', 'Line ID');
                $sheet->setCellValue('AL10', 'เบอร์1');
                $sheet->setCellValue('AM10', 'เบอร์2');
                $sheet->setCellValue('AN10', 'เบอร์3');
                //ข้อมูลบัญชีธนาคาร
                $sheet->setCellValue('AO8', 'ข้อมูลบัญชีธนาคาร');
                $sheet->setCellValue('AO9', 'บัญชีธนาคาร1');
                $sheet->setCellValue('AO10', 'เลขที่');
                $sheet->setCellValue('AP10', 'ชื่อ');
                $sheet->setCellValue('AQ10', 'ประเภท');
                $sheet->setCellValue('AR10', 'ธนาคาร');
                $sheet->setCellValue('AS10', 'สาขา');
                $sheet->setCellValue('AT9', 'บัญชีธนาคาร2');
                $sheet->setCellValue('AT10', 'เลขที่');
                $sheet->setCellValue('AU10', 'ชื่อ');
                $sheet->setCellValue('AV10', 'ประเภท');
                $sheet->setCellValue('AW10', 'ธนาคาร');
                $sheet->setCellValue('AX10', 'สาขา');
                $sheet->setCellValue('AY9', 'บัญชีธนาคาร3');
                $sheet->setCellValue('AY10', 'เลขที่');
                $sheet->setCellValue('AZ10', 'ชื่อ');
                $sheet->setCellValue('BA10', 'ประเภท');
                $sheet->setCellValue('BB10', 'ธนาคาร');
                $sheet->setCellValue('BC10', 'สาขา');

                $row = 11;
                $count = 1;
                $itemStartRow = $row;
                foreach ($data as $item) {
                    if (!($item instanceof PersonCorporate)) {
                        continue;
                    }

                    $personData = $item->getPersonData();

                    if ($personData instanceof Corporate) {
                        $sheet->setCellValue('A' . $row, $count);
                        $sheet->setCellValue('B' . $row, $item->getCode());
                        $sheet->setCellValueExplicit('C' . $row, $item->getPersonData()->getCode(), DataType::TYPE_STRING2);
                        $sheet->setCellValue('D' . $row, empty($personData->getRegistrationDate()) ? '' : Date::PHPToExcel($personData->getRegistrationDate()));
                        $sheet->setCellValue('E' . $row, $this->getCategoryName($personData->getCategory()));
                        $sheet->setCellValue('F' . $row, $personData->getCorporateName());
                        $sheet->setCellValue('G' . $row, empty($personData->getBranch()) ? 'สำนักงานใหญ่' : $personData->getBranch());
                        $sheet->setCellValue('H' . $row, $personData->getAddress()->getAddress());
                        $sheet->setCellValue('I' . $row, $personData->getAddress()->getSubdistrict());
                        $sheet->setCellValue('J' . $row, $personData->getAddress()->getDistrict());
                        $sheet->setCellValue('K' . $row, $personData->getAddress()->getProvince());
                        $sheet->setCellValue('L' . $row, $personData->getAddress()->getPostalcode());
                        $sheet->setCellValue('M' . $row, $item->getAddress()->getAddress());
                        $sheet->setCellValue('N' . $row, $item->getAddress()->getSubdistrict());
                        $sheet->setCellValue('O' . $row, $item->getAddress()->getDistrict());
                        $sheet->setCellValue('P' . $row, $item->getAddress()->getProvince());
                        $sheet->setCellValue('Q' . $row, $item->getAddress()->getPostalcode());
                        $sheet->setCellValue('R' . $row, $item->getPhone());
                        $sheet->setCellValue('S' . $row, $item->getFax());

                        $contact0 = $item->getContacts()[0] ?? null;
                        if ($contact0 !== null) {
                            $sheet->setCellValue('T' . $row, $contact0->getAlias());
                            $sheet->setCellValue('U' . $row, $contact0->getPosition());
                            $sheet->setCellValue('V' . $row, $contact0->getEmail());
                            $sheet->setCellValue('W' . $row, $contact0->getLineId());
                            $sheet->setCellValue('X' . $row, empty($contact0->getPhones()[0]) ? null : $contact0->getPhones()[0]->getPhone());
                            $sheet->setCellValue('Y' . $row, empty($contact0->getPhones()[1]) ? null : $contact0->getPhones()[1]->getPhone());
                            $sheet->setCellValue('Z' . $row, empty($contact0->getPhones()[2]) ? null : $contact0->getPhones()[2]->getPhone());
                        }

                        $contact1 = $item->getContacts()[1] ?? null;
                        if ($contact1 !== null) {
                            $sheet->setCellValue('AA' . $row, $contact1->getAlias());
                            $sheet->setCellValue('AB' . $row, $contact1->getPosition());
                            $sheet->setCellValue('AC' . $row, $contact1->getEmail());
                            $sheet->setCellValue('AD' . $row, $contact1->getLineId());
                            $sheet->setCellValue('AE' . $row, empty($contact1->getPhones()[0]) ? null : $contact1->getPhones()[0]->getPhone());
                            $sheet->setCellValue('AF' . $row, empty($contact1->getPhones()[1]) ? null : $contact1->getPhones()[1]->getPhone());
                            $sheet->setCellValue('AG' . $row, empty($contact1->getPhones()[2]) ? null : $contact1->getPhones()[2]->getPhone());
                        }

                        $contact2 = $item->getContacts()[2] ?? null;
                        if ($contact2 !== null) {
                            $sheet->setCellValue('AH' . $row, $contact2->getAlias());
                            $sheet->setCellValue('AI' . $row, $contact2->getPosition());
                            $sheet->setCellValue('AJ' . $row, $contact2->getEmail());
                            $sheet->setCellValue('AK' . $row, $contact2->getLineId());
                            $sheet->setCellValue('AL' . $row, empty($contact2->getPhones()[0]) ? null : $contact2->getPhones()[0]->getPhone());
                            $sheet->setCellValue('AM' . $row, empty($contact2->getPhones()[1]) ? null : $contact2->getPhones()[1]->getPhone());
                            $sheet->setCellValue('AN' . $row, empty($contact2->getPhones()[2]) ? null : $contact2->getPhones()[2]->getPhone());
                        }

                        $bankAccount0 = $item->getBankAccounts()[0] ?? null;
                        if ($bankAccount0 !== null) {
                            $sheet->setCellValue('AO' . $row, $bankAccount0->getCode());
                            $sheet->setCellValue('AP' . $row, $bankAccount0->getName());
                            $sheet->setCellValue('AQ' . $row, $bankAccount0->getCategory());
                            $sheet->setCellValue('AR' . $row, $bankAccount0->getBank());
                            $sheet->setCellValue('AS' . $row, $bankAccount0->getBranch());
                        }

                        $bankAccount1 = $item->getBankAccounts()[1] ?? null;
                        if ($bankAccount1 !== null) {
                            $sheet->setCellValue('AT' . $row, $bankAccount1->getCode());
                            $sheet->setCellValue('AU' . $row, $bankAccount1->getName());
                            $sheet->setCellValue('AV' . $row, $bankAccount1->getCategory());
                            $sheet->setCellValue('AW' . $row, $bankAccount1->getBank());
                            $sheet->setCellValue('AX' . $row, $bankAccount1->getBranch());
                        }

                        $bankAccount2 = $item->getBankAccounts()[2] ?? null;
                        if ($bankAccount2 !== null) {
                            $sheet->setCellValue('AY' . $row, $bankAccount2->getCode());
                            $sheet->setCellValue('AZ' . $row, $bankAccount2->getName());
                            $sheet->setCellValue('BA' . $row, $bankAccount2->getCategory());
                            $sheet->setCellValue('BB' . $row, $bankAccount2->getBank());
                            $sheet->setCellValue('BC' . $row, $bankAccount2->getBranch());
                        }
                    }

                    $row++;
                    $count++;
                }
                $itemEndRow = $row - 1;

                $tableEndRow = $itemEndRow;

                $sheet->getStyle("A{$itemStartRow}:BC{$tableEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$itemStartRow}:E{$tableEndRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("F{$itemStartRow}:F{$tableEndRow}")->getAlignment()->setHorizontal('left');
                $sheet->getStyle("G{$itemStartRow}:G{$tableEndRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("H{$itemStartRow}:H{$tableEndRow}")->getAlignment()->setHorizontal('left');
                $sheet->getStyle("I{$itemStartRow}:L{$tableEndRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("M{$itemStartRow}:M{$tableEndRow}")->getAlignment()->setHorizontal('left');
                $sheet->getStyle("N{$itemStartRow}:BC{$tableEndRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("D{$itemStartRow}:D{$tableEndRow}")->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                //$sheet->getStyle("F{$itemStartRow}:F{$tableEndRow}")->getNumberFormat()->setFormatCode($costFormat);
                //$sheet->getStyle("C{$itemStartRow}:C{$tableEndRow}")->getNumberFormat()->setFormatCode('@');


                $writer = new Xlsx($spreadsheet);
                $writer->setPreCalculateFormulas(false);
                $fileName = 'RP-MT-PS-Corporate_rev.2.1.0_' . date('Ymd_His', time()) . '.xlsx';
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

    private function getCategoryName(?string $category): ?string
    {
        switch ($category) {
            case 'หสน.':
                return 'ห้างหุ่นส่วนสามัญนิติบุคคล';
            case 'หจก.':
                return 'ห้างหุ่นส่วนจำกัด';
            case 'บจ.':
                return 'บริษัทจำกัด';
            case 'บมจ.':
                return 'บริษัทจำกัด (มหาชน)';
            default:
                return $category;
        }
    }
}
