<?php

namespace Masuga\LinkVault\services;

use Craft;
use craft\awss3\Volume as S3;
use craft\elements\Asset;
use craft\googlecloud\Volume as GoogleCloud;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\volumes\Local;
use yii\base\Component;
use Masuga\LinkVault\LinkVault;

class ExportService extends Component
{

	/**
	 * Convert an array of data to delimited content of some sort.
	 * @param array $array
	 * @param string $delimiter
	 * @return string
	 */
	public function convertArrayToDelimitedContent($array=array(), $delimiter=",")
	{
		// Prefix the rows with a row of column names.
		$firstRow = $array[0] ?? null;
		if ( $firstRow ) {
			array_unshift($array, array_keys($firstRow));
		}
		ob_start();
		$f = fopen('php://output', 'w') or show_error("Can't open php://output");
		$n = 0;
		foreach ($array as $line) {
			$n++;
			if ( ! fputcsv($f, $line, $delimiter)) {
				show_error("Can't write line $n: $line");
			}
		}
		fclose($f) or show_error("Can't close php://output");
		$str = ob_get_contents();
		ob_end_clean();
		return $str;
	}

	/**
	 * This method generates a filename-friendly report name based on the criteria.
	 * @param array $criteria
	 * @return string
	 */
	public function generateReportFileName($criteria=[])
	{
		$currentDate = date('Ymd_Hi');
		// Set a fallback report name.
		$reportName = 'linkvault-records-'.$currentDate;
		if ( !empty($criteria) ) {
			$reportName = 'linkvault-';
			foreach($criteria as $name => &$value) {
				$reportName .= "{$name} {$value}";
			}
			$reportName = StringHelper::toKebabCase($reportName)."-{$currentDate}";
		}
		return $reportName;
	}

}
