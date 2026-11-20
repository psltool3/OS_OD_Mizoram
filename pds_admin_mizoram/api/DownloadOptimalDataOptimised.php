<?php
require_once('../util/SessionFunction.php');
if(!SessionCheck()){
    die('Unauthorized');
}


require('../util/Connection.php');
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


// Check if format is specified in GET request
if (isset($_GET['format'])) {
    $format = $_GET['format'];
    $include_remark = ($_GET['include_remark'] ?? '') === '1'
        && ($_GET['source'] ?? '') !== 'rollout' && ($_GET['rollout'] ?? '') !== '1'
        && in_array($format, ['csv', 'pdf'], true);
    
    $columns = ["scenario","from","from_state","from_id","from_name","from_district","from_lat","from_long","to","to_state","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance"];
    if ($include_remark) {
        $columns[] = 'Remark';
    }

	
    $tablename = $_GET['tableName'];
    $tablename1 = $_GET['tableName1'];
	$tableData = array();
    array_push($tableData,$columns);

    $query = "SELECT * FROM ".$tablename." WHERE 1";
    $result = mysqli_query($con,$query);
    $numrows = mysqli_num_rows($result);
    
    if($numrows>0){
        while($row = mysqli_fetch_array($result)){
			if($row['new_id_admin']!=null or $row['new_id_admin']!=""){
				$id = $row['new_id_admin'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				$numrows_warehouse = mysqli_num_rows($result_warehouse);
				if($numrows_warehouse!=0){
					$row_warehouse = mysqli_fetch_assoc($result_warehouse);
					$row["from_lat"] = $row_warehouse['latitude'];
					$row["from_long"] = $row_warehouse['longitude'];
					$row["from_district"] = $row_warehouse['district'];
				}
				$row["from_id"] = $row['new_id_admin'];
				$row["from_name"] = $row['new_name_admin'];
				$row["distance"] = $row['new_distance_admin'];
			}
			else if(($row['new_id_district']!=null or $row['new_id_district']!="") and $row['admin_approve']=="yes"){
				$id = $row['new_id_district'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				$numrows_warehouse = mysqli_num_rows($result_warehouse);
				if($numrows_warehouse!=0){
					$row_warehouse = mysqli_fetch_assoc($result_warehouse);
					$row["from_lat"] = $row_warehouse['latitude'];
					$row["from_long"] = $row_warehouse['longitude'];
					$row["from_district"] = $row_warehouse['district'];
				}
				$row["from_id"] = $row['new_id_district'];
				$row["from_name"] = $row['new_name_district'];
				$row["distance"] = $row['new_distance_district'];
			}
            if ($include_remark) {
                $row['Remark'] = $row['reason_admin'] ?? '';
            }
            $temp = array();
            for($i=0;$i<count($columns);$i++){
               array_push($temp,$row[$columns[$i]]);
            }
            array_push($tableData,$temp);
        }
    }
	
	if($tablename!=$tablename1){
		$query = "SELECT * FROM ".$tablename1." WHERE 1";
		$result = mysqli_query($con,$query);
		$numrows = mysqli_num_rows($result);
		
		if($numrows>0){
			while($row = mysqli_fetch_array($result)){
				if($row['new_id_admin']!=null or $row['new_id_admin']!=""){
					$id = $row['new_id_admin'];
					$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id'";
					$result_warehouse = mysqli_query($con,$query_warehouse);
					$numrows_warehouse = mysqli_num_rows($result_warehouse);
					if($numrows_warehouse!=0){
						$row_warehouse = mysqli_fetch_assoc($result_warehouse);
						$row["from_lat"] = $row_warehouse['latitude'];
						$row["from_long"] = $row_warehouse['longitude'];
						$row["from_district"] = $row_warehouse['district'];
					}
					$row["from_id"] = $row['new_id_admin'];
					$row["from_name"] = $row['new_name_admin'];
					$row["distance"] = $row['new_distance_admin'];
				}
				else if(($row['new_id_district']!=null or $row['new_id_district']!="") and $row['admin_approve']=="yes"){
					$id = $row['new_id_district'];
					$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id'";
					$result_warehouse = mysqli_query($con,$query_warehouse);
					$numrows_warehouse = mysqli_num_rows($result_warehouse);
					if($numrows_warehouse!=0){
						$row_warehouse = mysqli_fetch_assoc($result_warehouse);
						$row["from_lat"] = $row_warehouse['latitude'];
						$row["from_long"] = $row_warehouse['longitude'];
						$row["from_district"] = $row_warehouse['district'];
					}
					$row["from_id"] = $row['new_id_district'];
					$row["from_name"] = $row['new_name_district'];
					$row["distance"] = $row['new_distance_district'];
				}
				if ($include_remark) {
				    $row['Remark'] = $row['reason_admin'] ?? '';
				}
				$temp = array();
				for($i=0;$i<count($columns);$i++){
				   array_push($temp,$row[$columns[$i]]);
				}
				array_push($tableData,$temp);
			}
		}
	}
    
    // Filename for the downloaded file
    $filename = 'table_data';

    // Set headers for the chosen format
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            outputCSV($tableData);
            break;

        case 'xlsx':
            // Create a new PhpSpreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set column names as the first row
            /*$columnIndex = 1;
            foreach ($columns as $columnName) {
                $sheet->setCellValueByColumnAndRow($columnIndex, 1, $columnName);
                $columnIndex++;
            }*/

            // Insert data tableData
            $rowIndex = 1;
            foreach ($tableData as $rowData) {
                $columnIndex = 1;
                foreach ($rowData as $value) {
                    $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $value);
                    $columnIndex++;
                }
                $rowIndex++;
            }


            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            break;

        case 'pdf':
            require('fpdf/fpdf.php');
            if ($include_remark) {
                $columns_pdf = $columns;
                $tableData_pdf = $tableData;
            $pdf = new FPDF('L', 'mm', 'A4');
            if ($include_remark) {
                $pdf->SetAutoPageBreak(true, 10);
            }
            $pdf->AddPage();

            $pageWidth = $pdf->GetPageWidth() - 20;
            $numCols = count($columns_pdf);
            $nameIndex = array_search('to_name', $columns_pdf, true);
            $nameIndex = $nameIndex === false ? -1 : $nameIndex;
            $remarkWidth = 35;
            $colWidth = $include_remark ? ($pageWidth - $remarkWidth) / ($numCols - 1 + ($nameIndex >= 0 ? 2 : 0)) : $pageWidth / $numCols;
            $remarkIndex = $include_remark ? $numCols - 1 : -1;

            // Wrap remarks by measured font width, preserving the full text.
            $wrapRemark = function ($text) use ($pdf, $remarkWidth) {
                $lines = [];
                foreach (explode("\n", str_replace("\r", '', (string)$text)) as $paragraph) {
                    $line = '';
                    foreach (preg_split('//u', $paragraph, -1, PREG_SPLIT_NO_EMPTY) as $character) {
                        if ($line !== '' && $pdf->GetStringWidth($line . $character) > $remarkWidth - 2) {
                            $space = strrpos($line, ' ');
                            if ($space !== false) {
                                $lines[] = substr($line, 0, $space);
                                $line = substr($line, $space + 1);
                            } else {
                                $lines[] = $line;
                                $line = '';
                            }
                        }
                        $line .= $character;
                    }
                    $lines[] = $line;
                }
                return $lines;
            };

            // Render Header
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetFillColor(200, 220, 255);
            foreach ($tableData_pdf[0] as $columnIndex => $col) {
                $width = $columnIndex === $remarkIndex ? $remarkWidth : ($columnIndex === $nameIndex ? $colWidth * 3 : $colWidth);
                if ($include_remark) {
                    $fontSize = 7;
                    $pdf->SetFont('Arial', 'B', $fontSize);
                    while ($pdf->GetStringWidth($col) > $width - 2 && $fontSize > 1) {
                        $fontSize -= 0.5;
                        $pdf->SetFont('Arial', 'B', $fontSize);
                    }
                }
                $pdf->Cell($width, 8, $col, 1, 0, 'C', true);
            }
            $pdf->Ln();

            // Render Rows
            $pdf->SetFont('Arial', '', 6);
            $pdf->SetFillColor(255, 255, 255);
            for ($i = 1; $i < count($tableData_pdf); $i++) {
                $remarkLines = $include_remark ? $wrapRemark($tableData_pdf[$i][$remarkIndex]) : [];
                $rowHeight = max(8, count($remarkLines) * 3);
                if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - 10) {
                    $pdf->AddPage();
                    $pdf->SetFont('Arial', 'B', 7);
                    $pdf->SetFillColor(200, 220, 255);
                    foreach ($tableData_pdf[0] as $columnIndex => $col) {
                        $width = $columnIndex === $remarkIndex ? $remarkWidth : ($columnIndex === $nameIndex ? $colWidth * 3 : $colWidth);
                        if ($include_remark) {
                            $fontSize = 7;
                            $pdf->SetFont('Arial', 'B', $fontSize);
                            while ($pdf->GetStringWidth($col) > $width - 2 && $fontSize > 1) {
                                $fontSize -= 0.5;
                                $pdf->SetFont('Arial', 'B', $fontSize);
                            }
                        }
                        $pdf->Cell($width, 8, $col, 1, 0, 'C', true);
                    }
                    $pdf->Ln();
                    $pdf->SetFont('Arial', '', 6);
                    $pdf->SetFillColor(255, 255, 255);
                }
                foreach ($tableData_pdf[$i] as $columnIndex => $col) {
                    $str = (string)$col;
                    if ($columnIndex === $remarkIndex) {
                        $x = $pdf->GetX();
                        $y = $pdf->GetY();
                        $pdf->Rect($x, $y, $remarkWidth, $rowHeight);
                        foreach ($remarkLines as $lineIndex => $line) {
                            $pdf->SetXY($x, $y + $lineIndex * 3);
                            $pdf->Cell($remarkWidth, 3, $line, 0, 0, 'L');
                        }
                        $pdf->SetXY($x + $remarkWidth, $y);
                        continue;
                    }
                    $width = $columnIndex === $nameIndex ? $colWidth * 3 : $colWidth;
                    $fontSize = 6;
                    $pdf->SetFont('Arial', '', $fontSize);
                    while ($pdf->GetStringWidth($str) > $width - 2 && $fontSize > 1) {
                        $fontSize -= 0.5;
                        $pdf->SetFont('Arial', '', $fontSize);
                    }
                    $pdf->Cell($width, $rowHeight, $str, 1, 0, 'C', true);
                    $pdf->SetFont('Arial', '', 6);
                }
                $pdf->Ln($rowHeight);
            }

                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                echo $pdf->Output('S');
                break;
            }


            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 0);

            // Highlight the first row as header
            $pdf->SetFillColor(200, 220, 255); // Set background color
            $pdf->SetTextColor(0); // Reset text color
            $case = 0;
            foreach ($tableData as $row) {
                foreach ($row as $col) {
                    $pdf->Cell(30, 10, $col, 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetFillColor(255, 255, 255); 
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
            echo $pdf->Output('S');
            break;

        default:
            echo 'Error : Invalid format specified.';
            break;
    }
} else {
    echo 'Error : Please specify a format in the GET request (e.g., ?format=pdf).';
}



// Function to output CSV data
function outputCSV($data) {
    $output = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}

exit();