<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
const ROOT = __DIR__;

include_once(ROOT . '/class.CParMain.php');

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class csvToExcel extends CParMain  {


    function regenerateXlsxFromCsv(
        $csvFilePath,
        $outputDir,
        array $propMap,
        callable $normalizeKeyFunc,
        callable $flattenProductRowFunc,
        callable $sanitizeSheetTitleFunc
    ) {
        $this->logMessage("🛠 Начинаем регенерацию XLSX из CSV: $csvFilePath");

        if (!file_exists($csvFilePath)) {
            $this->logMessage("❌ CSV файл не найден: $csvFilePath");
            throw new Exception("CSV файл не найден: $csvFilePath");
        }

        // Чтение CSV в массив
        $handle = fopen($csvFilePath, 'r');
        if (!$handle) {
            $this->logMessage("❌ Ошибка открытия файла CSV для чтения: $csvFilePath");
            throw new Exception("Ошибка открытия файла CSV для чтения: $csvFilePath");
        }

        $headers = fgetcsv($handle, 0, ';');
        if ($headers === false) {
            fclose($handle);
            $this->logMessage("❌ CSV файл пуст или повреждён: $csvFilePath");
            throw new Exception("CSV файл пуст или повреждён");
        }
        $this->logMessage("✅ Заголовки CSV успешно прочитаны: " . implode(', ', $headers));

        $productsData = [];
        $lineCount = 1;
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $lineCount++;
            $item = array_combine($headers, $row);
            if ($item === false) {
                $this->logMessage("⚠️ Строка $lineCount не совпадает с заголовками, пропущена");
                continue;
            }
            if (isset($item['props'])) {
                $item['props'] = json_decode($item['props'], true) ?: [];
            }
            $productsData[] = $item;

            if ($lineCount % 1000 === 0) {
                $this->logMessage("⏳ Прочитано строк: $lineCount");
            }
        }
        fclose($handle);
        $this->logMessage("✅ Всего прочитано товаров: " . count($productsData));

        if (empty($productsData)) {
            $this->logMessage("❌ Нет данных для создания XLSX");
            throw new Exception("Нет данных для создания XLSX");
        }

        // Группировка по первой "крошке"
        $groups = [];
        $maxCrumbsCount = 0;
        $maxImagesCount = 0;
        $allPropKeys = [];
        $itemIndex = 0;
        foreach ($productsData as $item) {
            $itemIndex++;
            $crumbs = [];
            foreach ($headers as $h) {
                if (strpos($h, 'Крошка') === 0) {
                    $crumbs[] = isset($item[$h]) ? $item[$h] : '';
                }
            }
            $crumbKey = !empty($crumbs[0]) ? $crumbs[0] : 'Без категории';

            if (!isset($groups[$crumbKey])) {
                $groups[$crumbKey] = [];
                $this->logMessage("➕ Создана новая категория: \"$crumbKey\"");
            }

            $item['crumbs'] = $crumbs;

            $images = [];
            foreach ($headers as $h) {
                if (strpos($h, 'Изображение') === 0) {
                    $images[] = isset($item[$h]) ? $item[$h] : '';
                }
            }
            $item['images'] = $images;

            $props = [];
            foreach ($item as $key => $val) {
                if (!in_array($key, $headers) || in_array($key, ['Ссылка', 'Название', 'Цена', 'Ед. изм.', 'Описание']) ||
                    strpos($key, 'Крошка') === 0 || strpos($key, 'Изображение') === 0) {
                    continue;
                }
                if (!in_array($key, ['crumbs', 'images', 'props'])) {
                    $props[$key] = $val;
                }
            }
            $item['props'] = $props;

            $groups[$crumbKey][] = $item;

            $maxCrumbsCount = max($maxCrumbsCount, count($crumbs));
            $maxImagesCount = max($maxImagesCount, count($images));

            foreach ($props as $key => $val) {
                $keyNorm = $normalizeKeyFunc($key);
                $normKey = isset($propMap[$keyNorm]) ? $propMap[$keyNorm] : ucfirst($keyNorm);
                if (!in_array($normKey, ['Габариты','Длина','Ширина','Высота'])) {
                    $allPropKeys[$normKey] = true;
                }
            }
            $allPropKeys['Габариты'] = true;

            if ($itemIndex % 1000 === 0) {
                $this->logMessage("⏳ Группировка товаров: обработано $itemIndex строк");
            }
        }
        $this->logMessage("✅ Категорий после группировки: " . count($groups));

        $propKeys = array_keys($allPropKeys);
        sort($propKeys);

        $headersFinal = [];
        for ($i=1; $i<=$maxCrumbsCount; $i++) $headersFinal[] = "Крошка {$i}";
        $headersFinal = array_merge($headersFinal, ['Ссылка', 'Название', 'Цена', 'Ед. изм.']);
        for ($i=1; $i<=$maxImagesCount; $i++) $headersFinal[] = "Изображение {$i}";
        $headersFinal = array_merge($headersFinal, $propKeys);
        $headersFinal[] = 'Описание';

        $this->logMessage("ℹ️ Итоговые заголовки для XLSX сформированы: " . implode(', ', $headersFinal));

        $spreadsheet = new Spreadsheet();

        // Лист "Все товары"
        $sheetAll = $spreadsheet->getActiveSheet();
        $sheetAll->setTitle('Все товары');
        $this->logMessage("📋 Создан лист «Все товары»");

        $col = 1;
        foreach ($headersFinal as $h) {
            $sheetAll->setCellValueByColumnAndRow($col++, 1, $h);
        }

        $rowNum = 2;
        foreach ($productsData as $item) {
            $row = $flattenProductRowFunc($item, $headersFinal, $propMap, $maxCrumbsCount, $maxImagesCount);
            $col = 1;
            foreach ($row as $val) {
                $sheetAll->setCellValueByColumnAndRow($col++, $rowNum, $val);
            }
            if (($rowNum - 1) % 1000 === 0) {
                $this->logMessage("⏳ Записано строк на лист «Все товары»: " . ($rowNum - 1));
            }
            $rowNum++;
        }
        $this->logMessage("✅ Лист «Все товары» записан строк: " . ($rowNum - 2));

        // Листы по категориям
        $sheetCount = 0;
        foreach ($groups as $cat => $items) {
            $sheetCount++;
            $safeTitle = $sanitizeSheetTitleFunc($cat);
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($safeTitle);
            $this->logMessage("📋 Создан лист категории \"$cat\" с названием \"$safeTitle\"");

            $groupPropKeys = [];
            $maxGroupCrumbs = 0;
            $maxGroupImages = 0;
            foreach ($items as $idx => $item) {
                $maxGroupCrumbs = max($maxGroupCrumbs, isset($item['crumbs']) ? count($item['crumbs']) : 0);
                $maxGroupImages = max($maxGroupImages, isset($item['images']) ? count($item['images']) : 0);
                if (isset($item['props']) && is_array($item['props'])) {
                    foreach ($item['props'] as $key => $val) {
                        $keyNorm = $normalizeKeyFunc($key);
                        $normKey = isset($propMap[$keyNorm]) ? $propMap[$keyNorm] : ucfirst($keyNorm);
                        if (!in_array($normKey, ['Габариты','Длина','Ширина','Высота'])) {
                            $groupPropKeys[$normKey] = true;
                        }
                    }
                }
                $groupPropKeys['Габариты'] = true;
            }
            $propKeysLocal = array_keys($groupPropKeys);
            sort($propKeysLocal);

            $headersLocal = [];
            for ($i=1; $i<=$maxGroupCrumbs; $i++) $headersLocal[] = "Крошка {$i}";
            $headersLocal = array_merge($headersLocal, ['Ссылка', 'Название', 'Цена', 'Ед. изм.']);
            for ($i=1; $i<=$maxGroupImages; $i++) $headersLocal[] = "Изображение {$i}";
            $headersLocal = array_merge($headersLocal, $propKeysLocal);
            $headersLocal[] = 'Описание';

            $col = 1;
            foreach ($headersLocal as $h) {
                $sheet->setCellValueByColumnAndRow($col++, 1, $h);
            }

            $rowNum = 2;
            foreach ($items as $item) {
                $row = $flattenProductRowFunc($item, $headersLocal, $propMap, $maxGroupCrumbs, $maxGroupImages);
                $col = 1;
                foreach ($row as $val) {
                    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $val);
                }
                if (($rowNum - 1) % 1000 === 0) {
                    $this->logMessage("⏳ Лист категории \"$cat\": записано строк: " . ($rowNum - 1));
                }
                $rowNum++;
            }
            $this->logMessage("✅ Лист категории \"$cat\" записан строк: " . ($rowNum - 2));
        }
        $this->logMessage("📊 Создано всего листов категорий: $sheetCount");

        // Сохраняем XLSX
        $outputFile = $outputDir . DIRECTORY_SEPARATOR . 'Regenerated_' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($outputFile);
        $this->logMessage("💾 XLSX сохранён: $outputFile");

        return $outputFile;
    }

    function start() {
        $outputXlsx = $this->regenerateXlsxFromCsv(
            'CParKupatika_summary.csv',
            __DIR__,
            include __DIR__.'/prop_dictionary.php',
            [$this, 'normalizeKey'],
            [$this, 'flattenProductRow'],
            [$this, 'sanitizeSheetTitle']
        );
        $this->logMessage("XLSX файл успешно пересоздан: $outputXlsx");
    }
}
$c = new csvToExcel();
$c->start();


