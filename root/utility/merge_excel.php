<?php
ini_set('memory_limit', '-1');
date_default_timezone_set('Asia/Almaty');

require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

function mergeExcelFilesToTempSheets($filePaths, $finalOutput)
{
    $startTime = microtime(true);
    echo "🔄 Начинаем объединение Excel-файлов...\n";

    $loadedFiles = array();
    foreach ($filePaths as $file) {
        echo "📄 Загружаем файл: $file\n";
        $loadedFiles[$file] = IOFactory::load($file);
    }

    // Соберем все уникальные листы
    $allSheetNames = array();
    foreach ($loadedFiles as $spreadsheet) {
        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $allSheetNames[$sheetName] = true;
        }
    }
    $allSheetNames = array_keys($allSheetNames);
    echo "✅ Найдено " . count($allSheetNames) . " уникальных листов.\n\n";

    $tmpFiles = array();
    $sheetIndex = 1;

    foreach ($allSheetNames as $sheetName) {
        echo "---- Обработка #$sheetIndex из " . count($allSheetNames) . ": \"$sheetName\" ----\n";
        $sheetIndex++;

        $fullHeaders = array();
        $fileSheetCount = 0;

        foreach ($loadedFiles as $spreadsheet) {
            if ($spreadsheet->sheetNameExists($sheetName)) {
                $fileSheetCount++;
                $sheet = $spreadsheet->getSheetByName($sheetName);
                $header = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1', null, false, false);
                foreach ($header[0] as $col) {
                    if ($col !== null && $col !== '' && !in_array($col, $fullHeaders)) {
                        $fullHeaders[] = $col;
                    }
                }
                unset($sheet);
            }
        }

        echo "📌 Найден в $fileSheetCount файлах, заголовков: " . count($fullHeaders) . "\n";

        $tempSpreadsheet = new Spreadsheet();
        $tempSheet = $tempSpreadsheet->getActiveSheet();
        $tempSheet->setTitle($sheetName);
        $tempSheet->fromArray($fullHeaders, null, 'A1');

        $currentRow = 2;
        $totalRows = 0;

        foreach ($loadedFiles as $file => $spreadsheet) {
            if ($spreadsheet->sheetNameExists($sheetName)) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();
                $localHeadersRaw = $sheet->rangeToArray("A1:$highestCol" . "1", null, false, false);
                $localHeaders = $localHeadersRaw[0];

                $colIndexMap = array();
                foreach ($localHeaders as $idx => $header) {
                    $colIndexMap[$header] = $idx;
                }

                $rowsRead = 0;
                for ($r = 2; $r <= $highestRow; $r++) {
                    $rowRaw = $sheet->rangeToArray("A$r:$highestCol$r", null, false, false);
                    $rowData = isset($rowRaw[0]) ? $rowRaw[0] : array();
                    $normalized = array();

                    foreach ($fullHeaders as $head) {
                        if (isset($colIndexMap[$head])) {
                            $index = $colIndexMap[$head];
                            $normalized[] = isset($rowData[$index]) ? $rowData[$index] : null;
                        } else {
                            $normalized[] = null;
                        }
                    }

                    $tempSheet->fromArray($normalized, null, "A$currentRow");
                    $currentRow++;
                    $rowsRead++;
                    $totalRows++;
                }

                echo "📥 $file → $rowsRead строк\n";
                unset($sheet);
            }
        }

        // Сохраняем временный файл
        $tmpFilename = sys_get_temp_dir() . '/tmp_sheet_' . md5($sheetName) . '.xlsx';
        $writer = IOFactory::createWriter($tempSpreadsheet, 'Xlsx');
        $writer->save($tmpFilename);
        $tmpFiles[] = array($tmpFilename, $sheetName);

        echo "✅ Временный файл \"$sheetName\" сохранён: $totalRows строк\n\n";

        $tempSpreadsheet->disconnectWorksheets();
        unset($tempSpreadsheet);
        gc_collect_cycles();
    }

    echo "📦 Объединяем временные листы в итоговый файл...\n";

    $resultSpreadsheet = new Spreadsheet();
    $resultSpreadsheet->removeSheetByIndex(0); // удалить дефолтный

    foreach ($tmpFiles as $info) {
        $tmpPath = $info[0];
        $title = $info[1];
        $usedTitles = array();

        $spreadsheet = IOFactory::load($tmpPath);
        $sheet = $spreadsheet->getSheet(0);

        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]\\:]/u', '', $title);
        $title = mb_substr($title, 0, 31);
        $base = $title;
        $suffix = 1;

        while (in_array($title, $usedTitles)) {
            $title = mb_substr($base, 0, 28) . '_' . $suffix;
            $suffix++;
        }

        $usedTitles[] = $title;
        $sheet->setTitle($title);
        $resultSpreadsheet->addExternalSheet($sheet);

        echo "📎 Добавлен лист: $title\n";

    }

    $writer = IOFactory::createWriter($resultSpreadsheet, 'Xlsx');
    $writer->save($finalOutput);
    echo "✅ Итоговый файл создан: $finalOutput\n";

    // Очистка
    $resultSpreadsheet->disconnectWorksheets();
    unset($resultSpreadsheet);
    gc_collect_cycles();

    // Удалить временные файлы
    foreach ($tmpFiles as $info) {
        $tmpPath = $info[0];
        if (file_exists($tmpPath)) {
            @unlink($tmpPath);
        }
    }

    $duration = round(microtime(true) - $startTime, 1);
    echo "🎉 Завершено за $duration сек.\n";
}

// 📌 Вызов:
mergeExcelFilesToTempSheets(array(
    'CParProfmetall_1.xlsx',
    'CParProfmetall_2.xlsx',
    'CParProfmetall_3.xlsx',
    'CParProfmetall_4.xlsx',
    'CParProfmetall_5.xlsx',
    'CParProfmetall_6.xlsx',
    'CParProfmetall_7.xlsx'
), 'output_merged.xlsx');
