<?php
declare(strict_types=1);

namespace Dux\Utils;

use Dux\App;
use Dux\Handlers\ExceptionBusiness;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Psr\Http\Message\ResponseInterface;

class Excel
{

    /**
     * 导入表格
     * @param string $url
     * @param int $start
     * @return array|null
     */
    public static function import(string $url, int $start = 1): ?array
    {
        $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $extArr = ['xlsx', 'xls', 'csv'];
        if (!in_array($ext, $extArr)) {
            throw new ExceptionBusiness("File type error");
        }
        $delFile = true;
        if(str_starts_with($url,'http')){
            $client = new \GuzzleHttp\Client();
            $fileTmp = $client->request('GET', $url)->getBody()->getContents();
            $tmpFile = tempnam(sys_get_temp_dir(), 'excel_');
            $tmp = fopen($tmpFile, 'w');
            fwrite($tmp, $fileTmp);
            fclose($tmp);
        }else{
            //本地文件
            $delFile = false;
            $tmpFile = public_path($url);
        }
        try {
            $objRead = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst($ext));
            $objRead->setReadDataOnly(true);
            $obj = $objRead->load($tmpFile);
            $currSheet = $obj->getSheet(0);
            $columnH = $currSheet->getHighestColumn();
            $columnCnt = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnH);
            $rowCnt = $currSheet->getHighestRow();
            $data = [];
            for ($_row = $start; $_row <= $rowCnt; $_row++) {
                $isNull = true;
                for ($_column = 1; $_column <= $columnCnt; $_column++) {
                    $cellName = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($_column);
                    $cellId = $cellName . $_row;
                    $data[$_row][$cellName] = trim($currSheet->getCell($cellId)->getFormattedValue());
                    if (!empty($data[$_row][$cellName])) {
                        $isNull = false;
                    }
                }
                if ($isNull) {
                    unset($data[$_row]);
                }
            }
            $table = [];
            foreach ($data as $vo) {
                $table[] = array_values($vo);
            }
            if($delFile){
                unlink($tmpFile);
            }
            return $table;
        } catch (Exception $e) {
            if($delFile){
                unlink($tmpFile);
            }
            throw $e;
        }
    }

    /**
     * 表格导出
     * @param string $title
     * @param string $subtitle
     * @param array $label
     * @param array $data
     * @param array $params
     */
    public static function export(string $title, string $subtitle, array $label, array $data, ResponseInterface $response,array $params = []): ResponseInterface
    {
        $count = count($label);
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $worksheet = $spreadsheet->getSheet(0);
        //标题
        $worksheet->setCellValue([1, 1], $title)->mergeCells([1, 1, $count, 1]);
        $styleCenter = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'wrapText' => true
            ],
            'font' => [
                'size' => 16,
            ],
        ];
        $worksheet->getStyle([1, 1])->applyFromArray($styleCenter);

        foreach ($label as $key => $vo) {
            $worksheet->getColumnDimensionByColumn($key + 1)->setWidth($vo['width']);
        }

        $worksheet->setCellValue([1, 2], $subtitle)->mergeCells([1, 2, $count, 2]);
        $worksheet->getStyle([1, 2])->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'wrapText' => true
            ],
        ]);

        //表头
        $styleArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'size' => 12,
            ],
        ];
        if($params['alignment']){
            $styleArray['alignment'] = array_merge($styleArray['alignment'], $params['alignment']);
        }
        $headRow = 3;
        foreach ($label as $key => $vo) {
            $worksheet->setCellValueExplicit([$key + 1, $headRow], $vo['name'], DataType::TYPE_STRING);
            $item = $worksheet->getStyle([$key + 1, $headRow])->applyFromArray($styleArray);
            if($vo['style']){
                $item->applyFromArray($vo['style']);
            }
            if($vo['name_style']){
                $item->applyFromArray($vo['name_style']);
            }
        }
        foreach ($data as $list) {
            $headRow++;
            foreach ($list as $k => $vo) {
                if (is_array($vo)) {
                    $callback = $vo['callback'];
                    $content = $vo['content'];
                } else {
                    $content = $vo;
                    $callback = '';
                }
                $dataType = $label[$k]['type'] ?? DataType::TYPE_STRING;
                $style = $label[$k]['style'] ?? null;
                $valueStyle = $label[$k]['value_style'] ?? null;
                $worksheet->setCellValueExplicit([$k + 1, $headRow], $content, $dataType);
                $item = $worksheet->getStyle([$k + 1, $headRow])->applyFromArray($styleArray);
                if($style){
                    $item->applyFromArray($style);
                }
                if($valueStyle){
                    $item->applyFromArray($valueStyle);
                }
                if (is_callable($callback)) {
                    $callback($item, $worksheet);
                }
            }
        }

        unset($worksheet);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        if(App::config('use')->get('app.debug')){
            header('Access-Control-Allow-Origin:*');
//        // 允许浏览器设置Headers中如下属性值
            header('Access-Control-Expose-Headers: *');
        }

        header('Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition:attachment; filename=' . rawurlencode($title . '-' . date('YmdHis')) . '.xlsx');
        header('Cache-Control:max-age=0');

        $output = fopen('php://output', 'w');
        $writer->save($output);
        return $response;
    }

}
