<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

ini_set('memory_limit', -1);
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);

class CsvXlsxMerger
{
    private $files = [];
    private $breadcrumbFields = ['"Крошка 1"'];
    private $logFile = null;
    private $debugMode = true;

    public function __construct($logFile = null, $debugMode = true)
    {
        $this->logFile = $logFile;
        $this->debugMode = $debugMode;
    }

    public function addFile($filePath)
    {
        if (file_exists($filePath)) {
            $this->files[] = $filePath;
            $this->log("✅ Файл добавлен: {$filePath}");
        } else {
            $this->log("❌ Файл не найден: {$filePath}");
        }
    }

    public function mergeAndExport($outputFile, $topCategoriesCount = 7)
    {
        $this->log("🔁 Начинаем обработку файлов...");
        $startTime = microtime(true);

        $allData = [];
        $allHeaders = [];
        $groupedData = [];
        $categoryStats = [];

        foreach ($this->files as $file) {
            $this->log("📥 Обработка файла: {$file}");
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $rows = [];

            $readStart = microtime(true);
            if ($ext === 'csv') {
                $rows = $this->readCsv($file);
            } elseif ($ext === 'xlsx' || $ext === 'xls') {
                $rows = $this->readXlsx($file);
            } else {
                $this->log("⚠️ Неподдерживаемый формат файла: $ext");
                continue;
            }
            $readDuration = microtime(true) - $readStart;
            $this->log("⏱ Время чтения: " . round($readDuration, 2) . " сек");

            $rowsCount = count($rows);
            if ($rowsCount === 0) {
                $this->log("⚠️ В файле нет данных: " . basename($file));
                continue;
            }

            $this->log("📄 Прочитано строк: {$rowsCount} из " . basename($file));

            // Отладочная информация о заголовках
            if (!empty($rows) && $this->debugMode) {
                $firstRow = reset($rows);
                $this->debugHeaders(array_keys($firstRow), basename($file));
            }

            foreach ($rows as $i => $row) {
                // Попытка найти категорию из крошек с приоритетом
                $categoryInfo = $this->extractCategory($row);
                $category = $categoryInfo['category'];
                $categoryLevel = $categoryInfo['level'];

                if (!isset($groupedData[$category])) {
                    $groupedData[$category] = [];
                    $categoryStats[$category] = [
                        'count' => 0,
                        'level' => $categoryLevel,
                        'field' => $categoryInfo['field']
                    ];
                }

                $groupedData[$category][] = $row;
                $categoryStats[$category]['count']++;
                $allData[] = $row;

                foreach ($row as $key => $value) {
                    $allHeaders[$key] = true;
                }
            }

            $this->log("📑 Категорий накапливается: " . count($groupedData));
        }

        if (empty($allData)) {
            $this->log("❌ Нет данных для объединения. Работа остановлена.");
            return;
        }

        $headers = array_keys($allHeaders);
        $this->log("📊 Уникальных колонок всего: " . count($headers));

        // Вывод статистики категорий
        $this->logCategoryStats($categoryStats);

        // Сортировка групп по количеству строк
        $groupCounts = [];
        foreach ($groupedData as $cat => $items) {
            $groupCounts[$cat] = count($items);
        }

        arsort($groupCounts);
        $topGroups = array_slice(array_keys($groupCounts), 0, $topCategoriesCount);
        $this->log("🏆 Топ {$topCategoriesCount} категорий:");
        foreach ($topGroups as $i => $catName) {
            $level = isset($categoryStats[$catName]) ? $categoryStats[$catName]['level'] : 0;
            $field = isset($categoryStats[$catName]) ? $categoryStats[$catName]['field'] : 'unknown';
            $this->log("   " . ($i+1) . ". '$catName' – " . $groupCounts[$catName] . " строк (уровень: $level, поле: $field)");
        }

        // Excel генерация
        $spreadsheet = new Spreadsheet();
// НЕ удаляй removeSheetByIndex(0) сразу

        // === Лист "Все товары" ===
        $this->log("📝 Создание листа «Все товары»");
        $headersAll = array_keys($allHeaders);

// Превращаем $allData в матрицу
        $dataMatrixAll = [];
        foreach ($allData as $row) {
            $line = [];
            foreach ($headersAll as $h) {
                $line[] = isset($row[$h]) ? $row[$h] : '';
            }
            $dataMatrixAll[] = $line;
        }

// === Лист "Все товары" ===
        $this->log("📝 Создание листа «Все товары»");

// Заголовки для всех товаров
        $headersAll = $this->extractHeaders($allData);

// Данные
        $dataMatrixAll = [];
        foreach ($allData as $row) {
            $line = [];
            foreach ($headersAll as $h) {
                $line[] = isset($row[$h]) ? $row[$h] : '';
            }
            $dataMatrixAll[] = $line;
        }

        $sheetAll = $spreadsheet->getActiveSheet();
        $sheetAll->setTitle('Все товары');
        $sheetAll->fromArray($headersAll, null, 'A1');
        $sheetAll->fromArray($dataMatrixAll, null, 'A2');
        unset($dataMatrixAll);

        $this->log("✅ Лист «Все товары» записан строк: " . count($allData));
        if (function_exists('gc_collect_cycles')) gc_collect_cycles();

// === Листы по категориям ===
        foreach ($topGroups as $category) {
            $this->log("📝 Создание листа для категории: '$category'");
            $rowsArray = isset($groupedData[$category]) ? $groupedData[$category] : [];

            // Индивидуальный набор колонок для этой категории
            $localHeaders = $this->extractHeaders($rowsArray);

            // Данные для этой категории
            $dataMatrix = [];
            foreach ($rowsArray as $row) {
                $line = [];
                foreach ($localHeaders as $h) {
                    $line[] = isset($row[$h]) ? $row[$h] : '';
                }
                $dataMatrix[] = $line;
            }

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($this->sanitizeTitle($category));
            $sheet->fromArray($localHeaders, null, 'A1');
            $sheet->fromArray($dataMatrix, null, 'A2');

            $this->log("✅ Лист '{$category}' записан строк: " . count($rowsArray));

            // Освобождаем память
            unset($groupedData[$category], $dataMatrix, $rowsArray);
            if (function_exists('gc_collect_cycles')) gc_collect_cycles();
        }


        $writeStart = microtime(true);
        $writer = new Xlsx($spreadsheet);
        $writer->save($outputFile);
        $writeDuration = microtime(true) - $writeStart;

        $totalTime = microtime(true) - $startTime;
        $this->log("💾 XLSX-файл успешно сохранён: {$outputFile}");
        $this->log("✅ Завершено. Время выполнения: " . round($totalTime, 2) . " сек (запись файла: " . round($writeDuration, 2) . " сек)");
    }

    private function extractCategory($row)
    {
        // Проходим по всем возможным полям крошек от самого важного к менее важному
        foreach ($this->breadcrumbFields as $level => $field) {
            $foundKey = $this->findKeyIgnoreCase($row, $field);
            if ($foundKey !== null) {
                $value = trim($row[$foundKey]);
                if (!empty($value)) {
                    return [
                        'category' => $value,
                        'level' => $level + 1,
                        'field' => $field
                    ];
                }
            }
        }

        return [
            'category' => 'Без категории',
            'level' => 0,
            'field' => 'none'
        ];
    }

    private function findKeyIgnoreCase($array, $searchKey)
    {
        $searchKeyLower = mb_strtolower($searchKey);
        foreach (array_keys($array) as $key) {
            if (mb_strtolower($key) === $searchKeyLower) {
                return $key;
            }
        }
        return null;
    }

    private function debugHeaders($headers, $filename)
    {
        $this->log("🔍 Отладка заголовков для файла: $filename");
        $this->log("📋 Найденные колонки (" . count($headers) . " шт.):");

        foreach ($headers as $i => $header) {
            $this->log("   $i: '$header'");
        }

        // Проверяем наличие крошек
        $this->log("🍞 Проверка полей крошек:");
        foreach ($this->breadcrumbFields as $breadcrumb) {
            $found = $this->findKeyIgnoreCase(array_flip($headers), $breadcrumb);
            if ($found !== null) {
                $this->log("   ✅ Найдено: '$breadcrumb' (точное совпадение: '$found')");
            } else {
                $this->log("   ❌ Не найдено: '$breadcrumb'");

                // Поиск похожих полей
                $similar = $this->findSimilarHeaders($headers, $breadcrumb);
                if (!empty($similar)) {
                    $this->log("     🔍 Похожие поля: " . implode(', ', array_map(function($h) { return "'$h'"; }, $similar)));
                }
            }
        }
    }

    private function findSimilarHeaders($headers, $searchField)
    {
        $similar = [];
        $searchLower = mb_strtolower($searchField);

        foreach ($headers as $header) {
            $headerLower = mb_strtolower($header);

            // Поиск частичного совпадения
            if (strpos($headerLower, 'крошк') !== false ||
                strpos($searchLower, mb_strtolower($header)) !== false ||
                strpos($headerLower, mb_strtolower($searchField)) !== false) {
                $similar[] = $header;
            }
        }

        return $similar;
    }

    private function logCategoryStats($categoryStats)
    {
        $this->log("📊 Статистика категорий:");

        // Группируем по уровням
        $levels = [];
        foreach ($categoryStats as $category => $stats) {
            $level = $stats['level'];
            if (!isset($levels[$level])) {
                $levels[$level] = [];
            }
            $levels[$level][$category] = $stats;
        }

        foreach ($levels as $level => $categories) {
            $levelName = $level === 0 ? 'Без категории' : "Уровень $level";
            $this->log("   📁 $levelName:");

            foreach ($categories as $category => $stats) {
                $this->log("     - '$category': {$stats['count']} строк (поле: {$stats['field']})");
            }
        }
    }

    private function extractHeaders($rows)
    {
        $headers = array();
        foreach ($rows as $row) {
            foreach ($row as $key => $val) {
                $headers[$key] = true;
            }
        }
        return array_keys($headers);
    }

    private function writeSheet($spreadsheet, $title, $headers, $rows)
    {
        $sheet = $spreadsheet->createSheet();
        $safeTitle = $this->sanitizeTitle($title);
        $sheet->setTitle($safeTitle);

        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col++, 1, $header);
        }

        $rowNum = 2;
        foreach ($rows as $row) {
            $col = 1;
            foreach ($headers as $header) {
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, isset($row[$header]) ? $row[$header] : '');
            }
            $rowNum++;
        }

        $this->log("✅ Лист '{$safeTitle}' записан строк: ".count($rows));
    }

    private function readCsv($filePath)
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) return [];

        // Читаем заголовок
        $headers = fgetcsv($handle, 0, ';');

        // Убираем BOM из первого заголовка
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }

        while (($line = fgetcsv($handle, 0, ';')) !== false) {
            $row = array();
            foreach ($headers as $i => $key) {
                $row[trim($key)] = isset($line[$i]) ? $line[$i] : '';
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function getBreadcrumb($row)
    {
        $breadcrumbs = ['Крошка 1', 'Крошка 2', 'Крошка 3', 'Крошка 4', 'Крошка 5', 'Крошка 6'];
        foreach ($breadcrumbs as $b) {
            foreach ($row as $key => $val) {
                $cleanKey = preg_replace('/^\xEF\xBB\xBF/', '', $key);
                if (mb_strtolower(trim($cleanKey)) === mb_strtolower($b)) {
                    return trim($val) ?: 'Без категории';
                }
            }
        }
        return 'Без категории';
    }

    private function detectEncoding($content)
    {
        // Список наиболее вероятных кодировок для CSV файлов
        $encodings = ['UTF-8', 'Windows-1251', 'Windows-1252', 'ISO-8859-1', 'CP1251'];

        foreach ($encodings as $encoding) {
            if (mb_check_encoding($content, $encoding)) {
                return $encoding;
            }
        }

        return 'UTF-8'; // По умолчанию
    }

    private function removeBOM($content)
    {
        // Удаляем UTF-8 BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            return substr($content, 3);
        }

        // Удаляем UTF-16 BOM
        if (substr($content, 0, 2) === "\xFF\xFE" || substr($content, 0, 2) === "\xFE\xFF") {
            return substr($content, 2);
        }

        return $content;
    }

    private function detectDelimiter($content)
    {
        // Берем первые несколько строк для анализа
        $lines = array_slice(explode("\n", $content), 0, 5);
        $delimiters = [';', ',', "\t", '|'];
        $counts = [];

        foreach ($delimiters as $delimiter) {
            $count = 0;
            foreach ($lines as $line) {
                $count += substr_count($line, $delimiter);
            }
            $counts[$delimiter] = $count;
        }

        // Возвращаем разделитель с наибольшим количеством вхождений
        arsort($counts);
        $detected = array_keys($counts)[0];

        return $detected === "\t" ? "TAB" : $detected; // Для лучшего отображения в логах
    }

    private function readXlsx($filePath)
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        $headers = array_shift($data);
        $result = [];

        foreach ($data as $row) {
            $item = array();
            foreach ($headers as $i => $header) {
                $item[$header] = isset($row[$i]) ? $row[$i] : '';
            }
            $result[] = $item;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        return $result;
    }

    private function sanitizeTitle($title)
    {
        $title = preg_replace('/[\\\\\/\*\?\[\]:]/u', '', $title);
        return mb_substr($title, 0, 31);
    }

    private function log($msg)
    {
        $time = date('H:i:s');
        $line = "[$time] $msg\n";
        echo $line;
        if ($this->logFile) {
            file_put_contents($this->logFile, $line, FILE_APPEND);
        }
    }
}

// Основной код
const ROOT = __DIR__;
require __DIR__ . '/../../vendor/autoload.php';

$files = array(
    "CParGlavSnab_20250814_054033.csv",
    "CParGlavSnab_20250814_115249.csv",
    "CParGlavSnab_20250815_122804.csv",
    "CParGlavSnab_20250815_133357.csv",
);

$merger = new CsvXlsxMerger(null, true); // Включаем отладочный режим
foreach ($files as $file) {
    $merger->addFile($file);
}

$outputFile = __DIR__ . '/merged_output_' . date('Ymd_His') . '.xlsx';
$merger->mergeAndExport($outputFile);
