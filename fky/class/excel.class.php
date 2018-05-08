<?php
namespace fky;


class Excel
{
	protected function column_str($key)
	{
		$array = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ');
		return $array[$key];
	}
	protected function column($key, $columnnum = 1)
	{
		return $this->column_str($key) . $columnnum;
	}
	function export($list, $params = array(), $setrow = 1)
	{
		if (PHP_SAPI == 'cli') {
			die('This example should only be run from a Web Browser');
		}
		require_once __DIR__ . '/../inc/excel/PHPExcel.php';
		$excel = new \PHPExcel();
		$excel->getProperties()->setCreator("fky")->setLastModifiedBy("fky")->setTitle("Office 2007 XLSX Test Document")->setSubject("Office 2007 XLSX Test Document")->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")->setKeywords("office 2007 openxml php")->setCategory("report file");
		$sheet = $excel->setActiveSheetIndex(0);
		$rownum = $setrow ;//从第几行开始打印标题
		foreach ($params['columns'] as $key => $column) {
			$sheet->setCellValue($this->column($key, $rownum), $column['title']);
			if (!empty($column['width'])) {
				$sheet->getColumnDimension($this->column_str($key))->setWidth($column['width']);
			}
		}
		$rownum++;
		foreach ($list as $row) {
			$len = count($row);
			for ($i = 0; $i < $len; $i++) {
				$value = $row[$params['columns'][$i]['field']];
				$sheet->setCellValue($this->column($i, $rownum), $value);
			}
			$rownum++;
		}
		$excel->getActiveSheet()->setTitle($params['title']);
		$filename = urlencode($params['title'] . '-' . date('Y-m-d H:i', time()));
		ob_end_clean();
		// header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
		$writer->save('php://output');
		die;
	}
	public function reader($excefile, $setrow = 2)
	{
		require_once __DIR__ . '/../inc/excel/PHPExcel.php';
		require_once __DIR__ . '/../inc/excel/PHPExcel/IOFactory.php';
		require_once __DIR__ . '/../inc/excel/PHPExcel/Reader/Excel5.php';
		if (!is_file($excefile)) {
			die('the file is no find');
		}
		$ext = strtolower(pathinfo($excefile, PATHINFO_EXTENSION));
		if ($ext == 'xls') {
			$reader = \PHPExcel_IOFactory::createReader('Excel5');
		} elseif ($ext == 'xlsx') {
			$reader = \PHPExcel_IOFactory::createReader('Excel2007');
		} else {
			die('file format error');
		}
		$excel = $reader->load($excefile);
		$sheet = $excel->getActiveSheet();
		$highestRow = $sheet->getHighestRow();
		$highestColumn = $sheet->getHighestColumn();
		$highestColumnCount = \PHPExcel_Cell::columnIndexFromString($highestColumn);
		$values = array();
		for ($row = $setrow; $row <= $highestRow; $row++) {
			$rowValue = array();
			for ($col = 0; $col < $highestColumnCount; $col++) {
				$rowValue[] = $sheet->getCellByColumnAndRow($col, $row)->getValue();
			}
			$values[] = $rowValue;
		}
		return $values;
	}
}