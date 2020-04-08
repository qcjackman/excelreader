<?php

namespace Qcjackman\Excelreader\Reader;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Reader
{
    /**
     * 允许的文件类型
     * @var array|mixed
     */
    private $allowExt = ['xlsx'];

    /**
     * 导入的文件
     * @var
     */
    private $file;

    /**
     * 模板配置
     * @var mixed
     */
    private $option;

    /**
     * 字段配置
     * @var array
     */
    private $fields = [];

    /**
     * 要读取的sheet
     * @var string
     */
    private $worksheet = 'first';

    /**
     * 表头行号
     * @var int
     */
    private $headRow = 1;

    /**
     * 读取起始行
     * @var int
     */
    private $startRow = 2;

    /**
     * 是否返回行数据
     * @var bool
     */
    private $rowsData = true;

    /**
     * 是否返回列数据
     * @var bool
     */
    private $colsData = true;

    private $readExcelHandler;

    private $workSheetHandler;

    /**
     * Reader constructor.
     * @param array $option
     * @throws \Exception
     */
    public function __construct($option = [])
    {
        if (!$option) {
            $this->option = Config::get('excelreader.templates.default');
        } else {
            $this->option = $option;
        }

        if (array_key_exists('fields', $this->option)) {
            $this->fields = $this->option['fields'];
        } else {
            throw new \Exception('unknown fields option');
        }

        if (array_key_exists('worksheet', $this->option)) {
            $this->worksheet = $this->option['worksheet'];
        }

        if (array_key_exists('headRow', $this->option)) {
            $this->headRow = $this->option['headRow'];
        }

        if (array_key_exists('startRow', $this->option)) {
            $this->startRow = $this->option['startRow'];
        }

        if (array_key_exists('rowsData', $this->option)) {
            $this->rowsData = $this->option['rowsData'];
        }

        if (array_key_exists('colsData', $this->option)) {
            $this->colsData = $this->option['colsData'];
        }
    }

    /**
     * 生成模板
     */
    public function templateFile()
    {
        $spreadsheet = new Spreadsheet();

        $worksheet = $spreadsheet->getActiveSheet();

        //设置工作表标题名称
        $worksheet->setTitle('Sheet1');

        //设置表头单元格内容
        foreach ($this->fields as $key => $field) {
            $worksheet->setCellValueByColumnAndRow($key+1, $this->headRow, $field['text']);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.md5(microtime(true)).'.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');

        return true;
    }

    public function setFile(UploadedFile $file)
    {
        $this->file = $file;

        $extension = $this->file->getClientOriginalExtension();

        if (!in_array($extension, $this->allowExt)) {
            throw new \Exception('unsupported file extension ' . $extension);
        }

        $type = ucfirst($extension);

        $reader = IOFactory::createReader($type);

        $this->readExcelHandler = $reader->load($this->file->getRealPath());

        return $this;
    }

    /**
     * 读取为数据
     * @param string $sheetIndex
     * @param int $startRow
     * @return array
     * @throws Exception
     */
    public function toArray()
    {
        if ($this->worksheet == 'active') {
            $index = $this->readExcelHandler->getActiveSheetIndex();
        } else{
            $index = $this->readExcelHandler->getFirstSheetIndex();
        }

        $this->workSheetHandler = $this->readExcel->getSheet($index);

        $maxRow = $this->workSheetHandler->getHighestRow(); // 总行数
        $maxColumn = $this->workSheetHandler->getHighestColumn(); // 总列数
        $maxColumnIndex = Coordinate::columnIndexFromString($maxColumn);

        $colFields = $this->getHeaderFields($this->fields);

        // 表头下一行为数据行
        $startRow = $this->startRow > $this->headRow ? (int)$this->startRow : $this->headRow + 1;
        $sheetData = $this->getSheetData($colFields, $maxRow, $maxColumnIndex, $this->startRow);
        $title = (string)$this->workSheetHandler->getTitle();

        $result = [
            'title'    => $title,
            'maxRow'   => count($sheetData['rowsData']),
            'maxCol'   => count($sheetData['colsData']),
            'startRow' => $startRow,
        ];

        if ($this->rowsData) {
            $result['rowsData'] = $sheetData['rowsData'];
        }

        if ($this->colsData) {
            $result['colsData'] = $sheetData['colsData'];
        }

        return $result;
    }

    /**
     * 读取表头行
     * @param array $fields
     * @return array
     * @throws Exception
     */
    public function getHeaderFields(array $fields)
    {
        // 最后一列的编号
        $maxColumn = $this->workSheetHandler->getHighestColumn();

        // 最后一列的序号
        $maxColumnIndex = Coordinate::columnIndexFromString($maxColumn);

        $headerFields = [];

        $fieldNames = array_column($fields, 'text');

        $row = $this->headRow;

        for ($col = 1; $col <= $maxColumnIndex; $col++) {
            $colStr = strtoupper(Coordinate::stringFromColumnIndex($col));

            $cell = $this->workSheetHandler->getCellByColumnAndRow($col, $row);

            $value = $this->getStringCell($cell);

            $key = array_search($value, $fieldNames);

            if (false !== $key && !isset($fields[$key]['col'])) {
                $headerFields[$colStr] = $fields[$key];
                $fields[$key]['col'] = $colStr;
            }
        }

        return $headerFields;
    }

    /**
     * 读取表格数据
     * @param $colFields
     * @param int $maxRow
     * @param int $maxColumnIndex
     * @param int $startRow
     * @return array
     * @throws Exception
     */
    private function getSheetData($colFields, $maxRow = 1, $maxColumnIndex = 1, int $startRow = 2)
    {
        $rowsData = [];// 按行展示
        $colsData = [];// 按列展示
        $uniqueRows = [];// 重复

        // 画板,照片等
        $drawings = $this->workSheetHandler->getDrawingCollection();
        $images = [];
        foreach ($drawings as $drawing) {
            $coord = $drawing->getCoordinates();
            $path = $drawing->getPath();
            if (!$coord || !$path) {
                continue;
            }
            $images[$coord] = $path;
        }

        for ($row = $startRow; $row <= $maxRow; $row++) {
            for ($col = 1; $col <= $maxColumnIndex; $col++) {
                $colStr = strtoupper(Coordinate::stringFromColumnIndex($col));
                $field = isset($colFields[$colStr]) ? $colFields[$colStr] : NULL;
                $name = isset($field['name']) ? $field['name'] : '';
                if (!$field || !$name) {
                    continue;
                }
                if (isset($uniqueRows[$row]) && $uniqueRows[$row]) {
                    continue;
                }
                $cell = $this->workSheetHandler->getCellByColumnAndRow($col, $row);

                $type = isset($field['type']) ? $field['type'] : 'string';
                $unique = isset($field['unique']) ? (bool)$field['unique'] : FALSE;

                // 处理图片类型
                if ($type == 'image') {
                    $value = $this->getImageCell($cell, $images, $field);
                } // 数值型
                elseif ($type == 'number') {
                    $value = $this->getNumberCell($cell, $field);
                } // 日期格式
                elseif ($type == 'date') {
                    $value = $this->getDateTimeCell($cell, $field);
                } // 时间格式
                elseif ($type == 'time') {
                    $value = $this->getDateTimeCell($cell, $field);
                } // 字符串类型
                else {
                    $value = $this->getStringCell($cell, $field);
                }
                // 唯一值,只取第1条数据
                if ($unique && isset($colsData[$name]) && in_array($value, $colsData[$name])) {
                    $uniqueRows[$row] = TRUE;
                    $rowsData[$row]=isset($rowsData[$row])?(array)$rowsData[$row]:[];
                    foreach ($rowsData[$row] as $key => $v) {
                        unset($colsData[$key][$row]);
                    }
                    unset($rowsData[$row]);// 删除行
                    continue;
                }

                if (!empty($value)) {
                    $colsData[$name][$row] = $rowsData[$row][$name] = $value;
                } else {
                    $colsData[$name][$row] = $rowsData[$row][$name] = '';
                }
            }
        }

        return [
            'rowsData' => $rowsData,
            'colsData' => $colsData,
        ];
    }

    /**
     * 获取字符串类型
     * @param $cell Cell
     * @param $field
     * @return string
     * @throws Exception
     */
    private function getStringCell(Cell $cell, array $field = [])
    {
        // 转换数字,科学计数法,注意excel的精度是15位,大于15位就转成0了.
        if ($cell->getDataType() == DataType::TYPE_NUMERIC) {
            $cell_style_format = $cell->getStyle($cell->getCoordinate())->getNumberFormat();
            $cell_style_format->setFormatCode('0');
            $value = $cell->getFormattedValue();
        } else {
            // 计算后的值
            $value = $cell->getCalculatedValue();
            //富文本转换字符串
            if ($value instanceof RichText) {
                $value = $value->__toString();
            }
        }
        $value = Util::String2Utf8((string)$value);
        $value = Util::String2ASC($value);
        $value = trim($value);

        return $value;
    }

    /**
     * 获取日期类型
     * @param $cell Cell
     * @param $field
     * @return string
     * @throws \Exception
     */
    private function getDateTimeCell(Cell $cell, $field = [])
    {
        $format = isset($field['format']) ? $field['format'] : 'Y-m-d';
        $value = $cell->getValue();
        $date = '';
        if ($value && is_numeric($value)) {
            $date = Date::excelToDateTimeObject($value)->format($format);
        }
        $value = $date && Util::validateDate($date, $format) ? $date : '';

        return $value;
    }

    /**
     * 获取图片类型
     * @param $cell
     * @param $field
     * @param array $images
     * @return string|null
     */
    private function getImageCell(Cell $cell, array $images, array $field = [])
    {
        $coord = $cell->getCoordinate();
        $file = isset($images[$coord]) ? $images[$coord] : NULL;
        if (!$file) {
            return '';
        }
        $base64 = isset($field['base64']) ? (bool)isset($field['base64']) : TRUE;
        // 转换成base64
        if ($base64) {
            return base64_encode(file_get_contents($file));
        }

        return $file;
    }

    /**
     * 获取数字类型
     * @param Cell $cell
     * @param array $field
     * @return float|null
     * @throws Exception
     */
    private function getNumberCell(Cell $cell, array $field = [])
    {
        if ($cell->getDataType() == DataType::TYPE_NUMERIC) {
            $cell_style_format = $cell->getStyle($cell->getCoordinate())->getNumberFormat();
            $cell_style_format->setFormatCode('0');
            $value = $cell->getFormattedValue();
        } else {
            // 计算后的值
            $value = $cell->getCalculatedValue();
            //富文本转换字符串
            if ($value instanceof RichText) {
                $value = $value->__toString();
            }
        }

        return !empty($value) ? (float)$value : NULL;
    }
}