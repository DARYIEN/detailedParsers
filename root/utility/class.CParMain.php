<?php
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class CParMain
{
    public $city_id;
    public $site_link;
    public $cityId;
    public $tempFilePath;
    public $parse_time;
    public $batches;
    public $productCount;
    public $slower_parse;
    public $city_name;
    public $batchSize = 4000;
    private $tempFilePrefix = 'temp_data_';
    private $tempFiles = [];
    private $itemCounter = 0;

    /*
    ----------------------------------
    -------Постраничный парсинг-------
    ----------------------------------
    */

    # Основные ф-ции
    function gettingUrls($links, $data, $dualData = false, $shortparse = false)
    {
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        if ($data["title"] === "Товары" || ($shortparse && $data["title"] = "Ссылки на товары")) $productsData = 0;
        else $productsData = [];


        foreach ($links as $index => $link) {
            $link = $this->encodeUrl($link);
            $this->logMessage($link);
            $ch = curl_init($link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            #curl_setopt($ch, CURLOPT_CAINFO, ROOT . DIRECTORY_SEPARATOR . "curl_crt" . DIRECTORY_SEPARATOR . "cacert.pem");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            if ($this->cityId) curl_setopt($ch, CURLOPT_COOKIE, "city_id=" . $this->cityId);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_USERAGENT, $this->getRandomUserAgent());
            curl_multi_add_handle($multiHandle, $ch);
            if ($dualData === true) $curlHandles[] = ["category" => $link, "handle" => $ch];
            else $curlHandles[] = $ch;


            if (($index + 1) % $this->batches === 0) {
                $this->executeCurlRequests($data, $multiHandle, $curlHandles, $productsData, $dualData, $shortparse);

                if ($data["title"] === "Товары" || ($shortparse && $data["title"] = "Ссылки на товары")) $this->logMessage("На данный момент при обработке {$data['log']} найдено объектов: " . $productsData);
                else $this->logMessage("На данный момент при обработке {$data['log']} найдено объектов: " . count($productsData));
                if ($this->slower_parse) {
                    sleep(1);
                } else {
                    sleep(0.3);
                }
            }
        }
        if (!empty($curlHandles)) $this->executeCurlRequests($data, $multiHandle, $curlHandles, $productsData, $dualData, $shortparse);
        curl_multi_close($multiHandle);

        if ($data["title"] === "Товары" || ($shortparse && $data["title"] = "Ссылки на товары")) $this->logMessage("Парсинг {$data['log']} завершен. Всего обработано: " . $productsData);
        else $this->logMessage("Парсинг {$data['log']} завершен. Всего обработано: " . count($productsData));

        return $productsData;
    }

    private function executeCurlRequests($data, $multiHandle, &$curlHandles, &$productsData, $dualData, $shortparse)
    {
        $keys = [];
        $htmlData = [];
        $running = null;

        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        foreach ($curlHandles as $curlData) {
            if ($dualData === true) {
                $ch = $curlData["handle"];
                $category = $curlData["category"];
                $html = curl_multi_getcontent($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode === 200) {
                    $keys[] = $category;
                    $htmlData[$category] = $html;
                } elseif ($httpCode === 429) {
                    $this->logMessage("Слишком много запросов для: " . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) . ". Ожидание 60 секунд.");
                    sleep(60);
                } else $this->logMessage("Ошибка при запросе: $httpCode для ссылки: " . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
            } else {
                $ch = $curlData;
                $html = curl_multi_getcontent($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode === 200) {
                    $htmlData[] = $html;
                } elseif ($httpCode === 429) {
                    $this->logMessage("Слишком много запросов для: " . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) . ". Ожидание 60 секунд.");
                    sleep(60);
                } else $this->logMessage("Ошибка при запросе: $httpCode для ссылки: " . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));

            }
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        $this->queryElements($htmlData, $data, $productsData, $keys, $dualData, $shortparse);
        $curlHandles = [];
    }

    private function queryElements($productsHtml, $data, &$productsData, $keys = null, $dualData, $shortparse) {

        #-----------------------------------------------------------------------------------------------------------------------------------------
        #
        # Основная ф-ция обработки резов разделена на 4 модуля :
        # 1. Категории - сбор ссылок на категории
        # 2. Пагинация - сбор ссылок на каждую страницу с товарами в категории
        # 3. Ссылок на товары - при коротком парсинге : Сбор инфы про товары , при длинном : сбор ссылок на товары по каждой ссылке из пагинации
        # 4. Товары(При использовании детального парсинга) Собирает инфу о товаре с его страницы
        #
        #-----------------------------------------------------------------------------------------------------------------------------------------
        #
        # Памятка по массиву $data
        # "log" - Что будет написано в логах при выполнении этого этапа
        # "title" - Наименование категории
        #
        # 1."title" = Категории:
        #   "category_selector" - Селектор для категории
        #
        # 2."title" = Пагинация:
        #   "paginate_selector" - Селектор на навигацию на странице
        #   "last_button_id" - Порядок с конца кнопки в навигации, которая отвечает за последнюю страницу
        #   "url_argument" - Аргумент под которым передается номер страницы в url
        #   "html_argument" - Аргумент, с помощью которого извлекаем из элемента номер последней странциы
        #
        # 3."title" = Ссылки на товары:
        #   "title_selector" - Массив селекторов, где может храниться ссылка на товар(берется первый, корректно отработавший)
        #   "title_html_argument" - аргумент для ссылки
        #   "price_html_argument" - аргумент для цены
        #   "price_selector" - Массив селекторов, где может быть цена на товар
        #   "price_ban_list" - Массив с недопустимыми ценами
        #   "big_data" - если хотим сохранять данные в промежуточный файл во время парсинга
        #
        # 4."title" = Товары:
        #   "price_selector" - Массив селекторов, где может быть цена на товар
        #   "title_selector" - Массив селекторов, где может быть цена на товар
        #   "title_html_argument" - аргумент для ссылки
        #   "price_html_argument" - аргумент для цены
        #   "price_order" - При наличии нескольких цен на товар, порядок какую брать(с начала)
        #   "big_data" - если хотим сохранять данные в промежуточный файл во время парсинга
        #
        # ----------------------------------------------------------------------------------------------------------------------------------------
        # Очистка цены
        $clean_price = function ($price) {
            $price = preg_replace('/\/.*/', '', $price);
            $price = preg_replace('/[^\d,.-]/', '', $price);
            $price = str_replace(',', '.', $price);
            $price = (float)$price;
            $price = ceil($price);
            $price = number_format($price, 2, '.', '');
            if ($price === "0.00") {
                $price = "12345678.00";
            }
            return $price;
        };
        $price_valid = function ($price, $ban_list) {

            if ($price === "0.00") $valid = false;
            else $valid = true;
            return $valid;
//            if ($price === "0.00") $valid = false;
//            else $valid = true;
//            return $valid;

//            if (count($ban_list) > 0) {
//                // Если бан-прайсов больше одного
//                $pattern = '/' . implode('|', array_map(function ($item) {
//                        return preg_quote($item, '/');
//                    }, $ban_list)) . '/';
//                if (!(preg_match($pattern, $price)) && (!empty($price))) $valid = true;
//
//            } else {
//                # Бан-прайс 1
//                switch ($ban_list[0]) {
//                    case "0.00":
//                        if (!($price === $ban_list[0])) $valid = true;
//                        break;
//                    default:
//                        $pattern = '/' . preg_quote($ban_list[0], "/") . "/";
//                        if (!(preg_match($pattern, $price)) and (!empty($price))) $valid = true;
//                        break;
//                }
//            }
        };
        $choose_selector = function ($xpath, $productsHtml) use ($data) {
            if (count($data["title_selector"]) > 0) {
                if ($data["title_selector"] == "link") {
                    $titleNodes = array_keys($productsHtml);
                } else {
                    for ($i = 0; $i < count($data["title_selector"]); $i++) {
                        $titleNodes = $xpath->query($data["title_selector"][$i]);
                        if ($titleNodes->length > 0) break;
                    }
                }
                if (count($data["price_selector"]) > 0) {
                    for ($i = 0; $i < count($data["price_selector"]); $i++) {
                        $priceNodes = $xpath->query($data["price_selector"][$i]);
                        if ($priceNodes->length > 0) break;
                    }
                }

                if (count($data["description_selector"]) > 0) {
                    for ($i = 0; $i < count($data["description_selector"]); $i++) {
                        $descriptionNodes[] = $xpath->query($data["description_selector"][$i]);
                    }
                }

                if (isset($data["image_selector"]) && count($data["image_selector"]) > 0) {
                    $imageNodes = null;
                    for ($i = 0; $i < count($data["image_selector"]); $i++) {
                        $imageNodes = $xpath->query($data["image_selector"][$i]);
                        if ($imageNodes->length > 0) break;
                    }
                }

                if (isset($data["dimension_selector"]) && count($data["dimension_selector"]) > 0) {
                    for ($i = 0; $i < count($data["dimension_selector"]); $i++) {
                        $dimensionNodes[] = $xpath->query($data["dimension_selector"][$i]);
                    }
                }
                return [
                    isset($titleNodes) ? $titleNodes : null,
                    isset($priceNodes) ? $priceNodes : null,
                    isset($descriptionNodes) ? $descriptionNodes : null,
                    isset($imageNodes) ? $imageNodes : null,
                    isset($dimensionNodes) ? $dimensionNodes : null
                ];
            }
        };
        $price_title_clear = function ($titleNode, $priceNode, $descriptionNode, $dimensionNode, $imageNode, $file) use ($clean_price, $price_valid, $shortparse, $data, &$productsData) {
            # Извлекаем наименование
            if (!is_string($titleNode)) {
                if ($data["title_type"] === "crumbs") {
                    $titles = [];
                    foreach ($titleNode as $titleN) {
                        if ($data["title_html_argument"]) {
                            $titles[] = trim($titleN->getAttribute($data["title_html_argument"]));
                        } else {
                            $titles[] = trim($titleN->nodeValue);
                        }
                    }
                    if ($data["title_cut"]) $titles = array_slice($titles, $data["title_cut"]);
                    else $titles = array_slice($titles, 2);
                    $title = "";
                    foreach ($titles as $titlee) {
                        $title = $title . " " . trim($titlee);
                    }
                } else {
                    if ($data["title_html_argument"] === "content" || $data["title_html_argument"] === "aria-label") {
                        $title = trim($titleNode->getAttribute($data["title_html_argument"]));
                    } else if ($data["title_html_argument"]) {
                        if (!$data["absolute_link"]) $title = $this->site_link . trim($titleNode->getAttribute($data["title_html_argument"]));
                        else $title = trim($titleNode->getAttribute($data["title_html_argument"]));
                    } else {
                        $title = trim($titleNode->nodeValue);
                    }
                }
            } else
                $title = $titleNode;

            if ($data["price_html_argument"]) $price = trim($priceNode->getAttribute($data["price_html_argument"]));
            else $price = trim($priceNode->nodeValue);

            if ($descriptionNode) {
                for ($i = 0; $i < count($descriptionNode); $i++) {
                    if (count($data["description_html_argument"]) > $i) {
                        if ($data["title_html_argument"][$i]) $title = $title . " " . $descriptionNode->getAttribute($data["title_html_argument"]);
                        else $title = $title . " " . trim($descriptionNode[$i]->nodeValue);
                    } else {
                        $title = $title . " " . trim($descriptionNode[$i]->nodeValue);
                    }
                }
            }

            $dimension = "";
            if ($dimensionNode) {
                foreach ($dimensionNode as $dimensionN) {
                    if ($data["dimension_html_argument"]) {
                        if ($data["dimension_coef"]) $dimension = $dimension . "X" . floatval(trim($dimensionN->getAttribute($data["dimension_html_argument"]))) * $data["dimension_coef"];
                        else $dimension = $dimension . "X" . trim($dimensionN->getAttribute($data["dimension_html_argument"]));
                    } else {
                        if ($data["dimension_coef"]) $dimension = $dimension . "X" . floatval(($dimensionN->nodeValue)) * $data["dimension_coef"];
                        else $dimension = $dimension . "X" . trim($dimensionN->nodeValue);
                    }
                }
                if (mb_substr($dimension, 0, 1) === 'X') {
                    $dimension = mb_substr($dimension, 1);
                }
                $title = $title . " " . $dimension;
            }
            $image = "";
            if ($imageNode) {
                if ($data["image_html_argument"]) {

                    $image = trim($imageNode->getAttribute($data["image_html_argument"]));
                    if ((!$data["absolute_link"] || !$data["image_absolute_link"]) && strpos($image, "http") !== 0) {
                        $image = $this->site_link . $image;
                        $image = urldecode($image);
                    } else if ($data["image_base"]) {
                        $image = "https:" . $image;
                        $image = urldecode($image);
                    } else {
                        $image = urldecode($image);
                    }
                } else {
                    $image = trim($imageNode->nodeValue);
                    $image = urldecode($image);
                }
            }

            # Работаем с ценой
            $price = $clean_price($price);
            $valid = $price_valid($price, $data["price_ban_list"]);

            // Сохранение данных с картинкой
            if ($valid) {
                if (!$shortparse && $data["title"] === "Ссылки на товары") {
                    $productsData[] = ['title' => $title, 'price' => $price, 'picture' => $image];
                } else {
                    if ($data["big_data"]) {
                        fputcsv($file, [$title, $price, $image]);
                    } else {
                        $this->items[] = array("name" => $title, "cost" => $price, "picture" => $image);
                    }
                    $productsData++;
                }
                $this->itemCounter++;
                if ($this->itemCounter >= $this->batchSize) {
                    $this->saveBatchData($productsData);
                    if (!$shortparse && $data["title"] === "Ссылки на товары") {
                        $productsData = [];
                    }
                    $this->itemCounter = 0;
                }
            }
        };

        foreach ($productsHtml as $key => $html) {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $encoding = mb_detect_encoding($html, ['UTF-8', 'Windows-1251', 'ISO-8859-1', 'CP1252'], true);
            if ($encoding == "Windows-1251" || strpos($this->site_link, "stenovoy.ru") || strpos($this->site_link, "i-gbi.ru") || strpos($this->site_link, "gbi6.ru") || strpos($this->site_link, "invest-gbi.ru")) { // ||  strpos($this->site_link, "gbi13.ru")
                $html = mb_convert_encoding($html, 'UTF-8', 'Windows-1251');
            }
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'); // Конвертация с учетом спецсимволов
            $dom->loadHTML($html);

            libxml_clear_errors();
            $xpath = new DOMXPath($dom);

            if ($data["big_data"]) {
                $size = filesize($this->tempFilePath);
                $file = fopen($this->tempFilePath, 'a');
                if ($size === false) fputcsv($file, ['Title', 'Price']);

            }

            if ($data["title"] === "Категории") {
                # Категории
                $categoryLinks = $xpath->query($data["category_selector"]);
                foreach ($categoryLinks as $link) {
                    if ($dualData) {
                        $link = $link->nodeValue;
                        if (strpos($link, "https://") !== false) $productsData[$key][] = $link;
                        elseif (strpos($link, "http://") !== false) $productsData[$key][] = $link;
                        else $productsData[$key][] = $this->site_link . $link;
                    } else {
                        $link = $link->nodeValue;
                        if (strpos($link, "https://") !== false) $productsData[] = $link;
                        elseif (strpos($link, "http://") !== false) $productsData[] = $link;
                        else $productsData[] = $this->site_link . $link;
                    }
                }

            }
            elseif ($data["title"] === "Пагинация") {
                # Nodes
                $paginationItems = $xpath->query($data["paginate_selector"]);
                $paginationLinks = ["$key"];

                # Нахождение последней страницы
                if ($paginationItems->length >= 2) {
                    $penultimateItem = $paginationItems->item($paginationItems->length - $data["last_button_id"]);
                    if ($data["html_argument"] === "nodeValue") $last_page = $penultimateItem->nodeValue;
                    if ($data["html_argument"] === "href/") {
                        $url = $penultimateItem->getAttribute("href");
                        preg_match('/\/page\/(\d+)\//', $url, $matches);
                        $last_page = $matches[1];
                    }
                    if ($data["html_argument"] === "href=") {
                        $url = $penultimateItem->getAttribute("href");
                        $page_arg = preg_quote($data["url_argument"], "/");
                        preg_match("/{$page_arg}(\d+)/", $url, $matches);
                        $last_page = $matches[1];
                    }
                } else {
                    $this->logMessage("Найдено " . count($paginationLinks) . " ссылок на пагинацию для категории: " . $key);
                    $productsData = array_merge($productsData, $paginationLinks);
                    continue;
                }
                if ($data[".html"]) {
                    $key = substr($key, 0, strlen($key) - 5);
                }

                # Создание ссылок на все страницы
                for ($i = 2; $i <= (int)$last_page; $i++) {
                    if ($data[".html"]) {
                        $current_page = "$key{$data['url_argument']}$i.html";
                    } else {
                        if ($data["/}"]) $current_page = "$key/{$data['url_argument']}$i/";
                        else  $current_page = "$key{$data['url_argument']}$i";
                    }

                    if (!in_array($current_page, $paginationLinks)) $paginationLinks[] = $current_page;

                }
                $this->logMessage("Найдено " . count($paginationLinks) . " ссылок на пагинацию для категории: " . $key);
                $productsData = array_merge($productsData, $paginationLinks);
            }
            elseif ($data["title"] === "Ссылки на товары") {
                if ($shortparse === true) {
                    $nodes = $choose_selector($xpath, $productsHtml);
                    $titleNodes = $nodes[0];
                    $priceNodes = $nodes[1];
                    $descriptionNodes = $nodes[2];
                    $imageNodes = $nodes[3];
                    $dimensionNodes = $nodes[4];

                    if ($titleNodes->length > 0) $length = $titleNodes->length;
                    else $length = count($titleNodes);
                    if (($titleNodes->length > 0 || count($titleNodes) > 0) && $priceNodes->length > 0) {
                        for ($i = 0; $i < $length; $i++) {
                            $priceNode = $priceNodes->item($i);
                            $titleNode = $titleNodes[$i];
                            if ($descriptionNodes) {
                                $descriptionNodes = [];
                                foreach ($descriptionNodes as $description) {
                                    $descriptionNode[] = $description->item($i);
                                }
                            }
                            if ($imageNodes->length > 0) $imageNode = $imageNodes->item($i);
                            if ($dimensionNodes) {
                                $dimensionNode = [];
                                foreach ($dimensionNodes as $dimension) {
                                    $dimensionNode[] = $dimension->item($i);
                                }
                            }
                            if ($titleNode && $priceNode) $price_title_clear($titleNode, $priceNode, $descriptionNode, $dimensionNode, $imageNode, $file);
                        }
                    }
                } else {
                    # Парсинг ссылок
                    if (count($data["price_selector"]) > 0) {
                        $nodes = $choose_selector($xpath, null);
                        $titleNodes = $nodes[0];
                        $priceNodes = $nodes[1];
                        for ($i = 0; $i < $titleNodes->length; $i++) {
                            $titleNode = $titleNodes->item($i);
                            $priceNode = $priceNodes->item($i);
                            if ($titleNode and $priceNode) {
                                if ($data["absolute_link"]) $title = $titleNode->getAttribute("href");
                                else $title = $this->site_link . $titleNode->getAttribute("href");
                                $productsData[] = $title;
                            }
                        }
                    } else {
                        # Парсинг ссылок без проверки цены
                        # Подбираем нужный селектор для сайтов с разной структурой html
                        $nodes = $choose_selector($xpath, null);
                        $titleNodes = $nodes[0];
                        for ($i = 0; $i < $titleNodes->length; $i++) {
                            $titleNode = $titleNodes->item($i);
                            if ($titleNode) {
                                if ($data["absolute_link"]) $title = $titleNode->getAttribute("href");
                                else $title = $this->site_link . $titleNode->getAttribute("href");
                                $productsData[] = $title;
                            }
                        }
                    }
                }
            }
            elseif ($data["title"] === "Товары одноразовый") {

                if ($data["crumb_selector"]) {
                    $crumbs = [];
                    $crumbNodes = $xpath->query($data["crumb_selector"]);
                    foreach ($crumbNodes as $crumbNode) {
                        $crumb = $crumbNode->nodeValue;
                        $crumbs[] = trim($crumb);
                    }
                    $crumbs = array_slice($crumbs, $data["crumb_begin"], count($crumbs) - $data["crumb_end"]);
                }

                if ($data["title_selector"]) {
                    $titleNode = $xpath->query($data["title_selector"]);
                    if ($titleNode->length > 0) {
                        if ($data["title_html_argument"]) {
                            $title = trim($titleNode->item(0)->getAttribute($data["title_html_argument"]));
                        } else {
                            $title = trim($titleNode->item(0)->nodeValue);
                        }
                    }
                }

                if ($data["price_selector"]) {
                    $priceNode = $xpath->query($data["price_selector"]);
                    if ($priceNode->length > 0) {
                        if ($data["price_html_argument"]) {
                            $price = $clean_price($priceNode->item(0)->getAttribute($data["price_html_argument"]));
                        } else {
                            $price = $clean_price($priceNode->item(0)->nodeValue);
                        }
                    }
                }

                if ($data["unit_selector"]) {
                    if (!$data["unit_item"]) $data["unit_item"] = 0;
                    $unitNode = $xpath->query($data["unit_selector"]);
                    if ($unitNode->length > 0) {
                        if ($data["unit_html_argument"]) {
                            $unit = $unitNode->item($data["unit_item"])->getAttribute($data["unit_html_argument"]);
                        } else {
                            $unit = $unitNode->item($data["unit_item"])->nodeValue;
                        }
                        if ($unit === "/шт" | $unit === "шт" |$unit === ".шт" | $unit === "штука" | $unit === "за штуку") $unit = "руб/шт";
                    }
                } else {
                    $unit = "Руб/шт";
                }


                $images = [];
                if ($data["one_image_selector"]) {
                    $imageNode = $xpath->query($data["one_image_selector"]);
                    if ($imageNode->length > 0) {
                        if ($data["image_html_argument"]) {
                            $image = $imageNode->item(0)->getAttribute($data["image_html_argument"]);
                        } else {
                            $image = $imageNode->item(0)->nodeValue;
                        }
                        if ($data["absolute_link"]) {
                            $images[] = $image;
                        } else {
                            $images[] = $this->site_link . $image;
                        }
                        }
                }

                if ($data["image_selector"]) {
                    $imageNodes = $xpath->query($data["image_selector"]);
                    foreach ($imageNodes as $imageNode) {
                        if ($data["image_html_argument"]) {
                            $image = $imageNode->getAttribute($data["image_html_argument"]);
                        } else {
                            $image = $imageNode->getAttribute("data-src");
                        }
                        if ($data["absolute_link"]) {
                            $images[] = $image;
                        } else {
                            $images[] = $this->site_link . $image;
                        }
                    }
                }

                if ($data["prop_selector"]) {
                    $props = [];
                    $propNodes = $xpath->query($data["prop_selector"]);
                    if ($data["lit_selector"]) {
                        $litNodes = $xpath->query($data["lit_selector"]);
                        for ($i = 0; $i < $litNodes->length; $i++) {
                            $id = $i + 1;
                            if ($data["absolute_link"]) {
                                $props["Инструкция $id"] = $litNodes->item($i)->getAttribute("href");
                            } else {
                                $props["Инструкция $id"] = $this->site_link . $litNodes->item($i)->getAttribute("href");
                            }
                        }
                    }
                    if ($data["prop_type"] === "mono") {
                        for ($i = 0; $i < $propNodes->length; $i += 2) {
                            $prop_name = $propNodes->item($i);
                            $prop_value = $propNodes->item($i + 1);
                            $props[$prop_name] = $prop_value;
                        }
                    } elseif ($data["prop_type"] === "dual") {
                        foreach ($propNodes as $propNode) {
                            $prop_name = $xpath->query($data["prop1"], $propNode);
                            $prop_value = $xpath->query($data["prop2"], $propNode);
                            $props[trim($prop_name->item(0)->nodeValue)] = trim($prop_value->item(0)->nodeValue);

                        }
                    }
                }
                $description = '';
                if ($data["description_selector"]) {
                    $descriptionNodes = $xpath->query($data["description_selector"]);
                    if ($descriptionNodes->length > 0) {
                        foreach ($descriptionNodes as $descriptionNode) {
                            if ($data["title_html_argument"]) {
                                $description = $description . trim($descriptionNode->getAttribute($data["description_html_argument"]));
                            } else {
                                $description = $description . trim($descriptionNode->nodeValue);
                            }
                        }
                    }
                }

                $productsData[] = [
                    "name" => $title,
                    "price" => $price,
                    "description" => $description,
                    "images" => $images,
                    "props" => $props,
                    "crumbs" => $crumbs,
                    "link" => $key,
                    "unit" => $unit
                ];

                $this->itemCounter++;
                if ($this->itemCounter >= $this->batchSize) {
                    $this->saveBatchData($productsData);
                    $productsData = [];
                    $this->itemCounter = 0;
                }
            }

            if (isset($data["big_data"]) ? $data["big_data"] : false) {
                fclose($file);
            }

        }
    }

    public function parseSave($productsData) {
        $this->logMessage("🔄 Запуск обработки товаров...");

        // 1. Объединение батчей из JSON
        $productsData = $this->mergeTemporaryFiles($productsData);
        $this->cleanupTemporaryFiles();
        $this->logMessage("📦 Всего товаров после объединения: " . count($productsData));

        // 2. Проверь использование памяти
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        if ($memoryUsage > ($memoryLimit * 0.8)) {
            $this->logMessage("⚠️ Память превышает 80% лимита (" . round($memoryUsage / 1024 / 1024) . "MB)");
            if (function_exists('gc_collect_cycles')) gc_collect_cycles();
        }

        // 3. Подготовка структуры, группировка по первой крошке
        $propMap = include __DIR__ . '/prop_dictionary.php';
        $groups = array();
        $allPropKeys = array();
        $maxCrumbsCount = 0;
        $maxImagesCount = 0;

        foreach ($productsData as $item) {
            $crumbKey = isset($item['crumbs'][0]) ? $item['crumbs'][0] : 'Без категории';
            $groups[$crumbKey][] = $item;
            $maxCrumbsCount = max($maxCrumbsCount, isset($item['crumbs']) ? count($item['crumbs']) : 0);
            $maxImagesCount = max($maxImagesCount, isset($item['images']) ? count($item['images']) : 0);
            if (isset($item['props']) && is_array($item['props'])) {
                foreach ($item['props'] as $key => $val) {
                    $keyNorm = $this->normalizeKey($key);
                    $normKey = isset($propMap[$keyNorm]) ? $propMap[$keyNorm] : ucfirst($keyNorm);
                    if (!in_array($normKey, array('Габариты','Длина','Ширина','Высота'))) {
                        $allPropKeys[$normKey] = true;
                    }
                }
            }
            $allPropKeys['Габариты'] = true;
        }
        $propKeys = array_keys($allPropKeys);
        sort($propKeys);

        // 4. Формируем headers
        $headers = array();
        for ($i = 1; $i <= $maxCrumbsCount; $i++) $headers[] = "Крошка {$i}";
        $headers = array_merge($headers, ['Ссылка', 'Название', 'Цена', 'Ед. изм.']);
        for ($i = 1; $i <= $maxImagesCount; $i++) $headers[] = "Изображение {$i}";
        $headers = array_merge($headers, $propKeys);
        $headers[] = 'Описание';


        $dir = dirname((new \ReflectionClass($this))->getFileName());
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $csvFile = $dir . DIRECTORY_SEPARATOR . get_class($this) . '_' . date('Ymd_His') . '.csv';
        $this->saveProductsToCsv($csvFile, $productsData, $headers, $propMap, $maxCrumbsCount, $maxImagesCount);

        // 5. Лист «Все товары»
        $sheetAll = $spreadsheet->getActiveSheet();
        $sheetAll->setTitle('Все товары');

        // Заголовки
        $col = 1;
        foreach ($headers as $h) {
            $sheetAll->setCellValueByColumnAndRow($col++, 1, $h);
        }

        // Запись всех товаров с логами каждые 1000 строк
        $rowNum = 2;
        foreach ($productsData as $item) {
            $row = $this->flattenProductRow($item, $headers, $propMap, $maxCrumbsCount, $maxImagesCount);
            $col = 1;
            foreach ($row as $val) {
                $sheetAll->setCellValueByColumnAndRow($col++, $rowNum, $val);
            }
            if (($rowNum - 1) % 1000 == 0) {
                $this->logMessage("Все товары: записано " . ($rowNum - 1) . " строк...");
            }
            $rowNum++;
        }
        $this->logMessage("✅ Лист «Все товары» записан строк: " . ($rowNum - 2));

        // 6. Листы по категориям
        foreach ($groups as $cat => $items) {
            $safeTitle = $this->sanitizeSheetTitle($cat);
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($safeTitle);

            // Локальные пропсы
            $groupPropKeys = [];
            $maxGroupCrumbs = 0;
            $maxGroupImages = 0;
            foreach ($items as $item) {
                $maxGroupCrumbs = max($maxGroupCrumbs, isset($item['crumbs']) ? count($item['crumbs']) : 0);
                $maxGroupImages = max($maxGroupImages, isset($item['images']) ? count($item['images']) : 0);
                if (isset($item['props']) && is_array($item['props'])) {
                    foreach ($item['props'] as $key => $val) {
                        $keyNorm = $this->normalizeKey($key);
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
            for ($i = 1; $i <= $maxGroupCrumbs; $i++) $headersLocal[] = "Крошка {$i}";
            $headersLocal = array_merge($headersLocal, ['Ссылка', 'Название', 'Цена', 'Ед. изм.']);
            for ($i = 1; $i <= $maxGroupImages; $i++) $headersLocal[] = "Изображение {$i}";
            $headersLocal = array_merge($headersLocal, $propKeysLocal);
            $headersLocal[] = 'Описание';

            // Записать заголовки
            $col = 1;
            foreach ($headersLocal as $h) {
                $sheet->setCellValueByColumnAndRow($col++, 1, $h);
            }

            // Записать товары с логами по 1000 строк
            $rowNum = 2;
            foreach ($items as $item) {
                $row = $this->flattenProductRow($item, $headersLocal, $propMap, $maxGroupCrumbs, $maxGroupImages);
                $col = 1;
                foreach ($row as $val) {
                    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $val);
                }
                if (($rowNum - 1) % 1000 == 0) {
                    $this->logMessage("Категория '{$safeTitle}': записано " . ($rowNum - 1) . " строк...");
                }
                $rowNum++;
            }
            $this->logMessage("✅ Лист «{$safeTitle}» записан строк: " . ($rowNum - 2));
        }

        // 7. Сохраняем XLSX в папку класса
        $fileName = $dir . DIRECTORY_SEPARATOR . get_class($this) . '_' . date('Ymd_His') . '_full.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($fileName);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $this->logMessage("💾 Итоговый XLSX сохранён: $fileName");
    }

    private function saveProductsToCsv($filePath, $productsData, $headers, $propMap, $maxCrumbsCount, $maxImagesCount)
    {
        $fp = fopen($filePath, 'w');
        fwrite($fp, "\xEF\xBB\xBF"); // BOM UTF-8

        fputcsv($fp, $headers, ';');

        $rowNum = 1;
        foreach ($productsData as $item) {
            $row = $this->flattenProductRow($item, $headers, $propMap, $maxCrumbsCount, $maxImagesCount);
            fputcsv($fp, $row, ';');
            if (($rowNum) % 1000 === 0) {
                $this->logMessage("CSV: записано {$rowNum} строк...");
            }
            $rowNum++;
        }
        fclose($fp);
        $this->logMessage("✅ CSV сохранён: $filePath. Всего строк: " . ($rowNum - 1));
    }

    private function flattenProductRow($item, $headers, $propMap, $maxCrumbs, $maxImages) {
        // Продвинутая логика габаритов + остальные свойства
        $length = $width = $height = '';
        $unit = '';
        $dimensionsCombined = '';
        $normalizedProps = [];

        if (!empty($item['props']) && is_array($item['props'])) {
            foreach ($item['props'] as $key => $value) {
                $keyNorm = $this->normalizeKey($key);
                $normKey = isset($propMap[$keyNorm]) ? $propMap[$keyNorm] : ucfirst($keyNorm);

                if (mb_strtolower($normKey) === 'габариты') {
                    $clean = preg_replace('/\s*/u', '', str_replace(['×', 'х', '*', 'X'], 'x', $value));
                    if (preg_match('/^(\d+x\d+x\d+)([^\dx]+)?$/u', $clean, $m)) {
                        $dimensionsCombined = $m[1] . (isset($m[2]) ? ' ' . trim($m[2]) : '');
                        continue;
                    }
                }
                if ($normKey === 'длина') {
                    list($length, $unit) = $this->parseNumberAndUnit($value);
                } elseif ($normKey === 'ширина') {
                    list($width, $unit) = $this->parseNumberAndUnit($value);
                } elseif ($normKey === 'высота') {
                    list($height, $unit) = $this->parseNumberAndUnit($value);
                } else {
                    $normalizedProps[$normKey] = isset($normalizedProps[$normKey])
                        ? $normalizedProps[$normKey] . '; ' . $value
                        : $value;
                }
            }
        }

        if (!empty($dimensionsCombined)) {
            $normalizedProps['Габариты'] = $dimensionsCombined;
        } else {
            $l = $length !== '' ? $length : '-';
            $w = $width !== '' ? $width : '-';
            $h = $height !== '' ? $height : '-';
            $unitSuffix = $unit !== '' ? " $unit" : '';
            $normalizedProps['Габариты'] = "{$l}x{$w}x{$h}{$unitSuffix}";
        }

        // Формируем строку для CSV/XLSX
        $row = [];
        for ($i = 0; $i < $maxCrumbs; $i++) {
            $row[] = isset($item['crumbs'][$i]) ? $item['crumbs'][$i] : '';
        }
        $row[] = isset($item['link']) ? $item['link'] : '';
        $row[] = isset($item['name']) ? $item['name'] : '';
        $row[] = isset($item['price']) ? $item['price'] : '';
        $row[] = isset($item['unit']) ? $item['unit'] : '';
        for ($i = 0; $i < $maxImages; $i++) {
            $row[] = isset($item['images'][$i]) ? $item['images'][$i] : '';
        }

        foreach ($headers as $header) {
            if (!in_array($header, ['Ссылка', 'Название', 'Цена', 'Ед. изм.']) &&
                strpos($header, 'Крошка') === false &&
                strpos($header, 'Изображение') === false &&
                $header !== 'Описание') {
                $row[] = isset($normalizedProps[$header]) ? $normalizedProps[$header] : '';
            }
        }
        $row[] = isset($item['description']) ? $item['description'] : '';

        return $row;
    }

// Дополнительные вспомогательные методы

    private function sanitizeSheetTitle($title) {
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]\\:]/u', '', $title);
        return mb_substr($title, 0, 31);
    }

    private function parseMemoryLimit($val) {
        $val = trim($val);
        if ($val === '' || $val == -1) {
            return PHP_INT_MAX; // Безлимит
        }
        $last = strtolower($val[strlen($val)-1]);
        $num = (int)$val;
        switch ($last) {
            case 'g':
                $num *= 1024;
            // no break
            case 'm':
                $num *= 1024;
            // no break
            case 'k':
                $num *= 1024;
        }
        return $num;
    }

    private function saveBatchData($data) {
        if (empty($data)) {
            $this->logMessage("⏭️ Пропущен пустой пакет.");
            return;
        }

        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->tempFilePrefix . uniqid() . '.json';
        $this->tempFiles[] = $tempFile;

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $this->logMessage("❌ JSON ошибка: " . json_last_error_msg());
            return;
        }

        if (file_put_contents($tempFile, $json) === false) {
            $this->logMessage("❌ Ошибка записи: " . basename($tempFile));
            return;
        }

        $this->logMessage("✅ Сохраняю пакет из " . count($data) . " позиций в " . basename($tempFile));

        unset($data, $json);
        if (function_exists('gc_collect_cycles')) gc_collect_cycles();
    }

    private function mergeTemporaryFiles($finalData) {
        if (empty($this->tempFiles)) {
            $this->logMessage("ℹ️ Нет временных файлов для объединения.");
            return $finalData;
        }

        $this->logMessage("📦 Объединяем " . count($this->tempFiles) . " временных файла(ов)...");
        $merged = 0;

        foreach ($this->tempFiles as $tempFile) {
            if (!file_exists($tempFile)) continue;

            $json = file_get_contents($tempFile);
            if ($json === false) {
                $this->logMessage("⚠️ Не могу прочесть файл " . basename($tempFile));
                continue;
            }

            $data = json_decode($json, true);
            if (!is_array($data)) {
                $this->logMessage("⚠️ Ошибка JSON декодирования: " . basename($tempFile));
                continue;
            }

            $merged += count($data);
            $finalData = array_merge($finalData, $data);
            $this->logMessage("➕ Объединено " . count($data) . " из " . basename($tempFile));

            unlink($tempFile);
        }

        $this->tempFiles = [];
        $this->logMessage("✅ Итоговое количество объединённых записей: $merged");

        return $finalData;
    }

    private function cleanupTemporaryFiles() {
        $deleted = 0;
        foreach ($this->tempFiles as $tempFile) {
            if (is_file($tempFile)) {
                @unlink($tempFile);
                $deleted++;
            }
        }
        $this->tempFiles = [];
        $this->logMessage("🧹 Удалено временных файлов: $deleted");
    }

    protected function normalizeKey($key) {
        $key = str_replace(["\xC2\xA0", "\xE2\x80\x89", "\xE2\x80\xAF", ' '], ' ', $key);
        $key = preg_replace('/\s+/u', ' ', $key);
        $key = str_replace([':', ';', '–', '.', ','], '', $key);
        return trim(mb_strtolower($key));
    }
    protected function parseNumberAndUnit($val) {
        $val = str_replace(',', '.', mb_strtolower(trim($val)));
        if (preg_match('/^([\d.]+)\s*([^\d\s]*)/u', $val, $m)) {
            return [$m[1], isset($m[2]) ? trim($m[2]) : ''];
        }
        return ['', ''];
    }
    function encodeUrl($url)
    {
        $parts = parse_url($url);
        if (!$parts) return false;

        // Кодируем домен в punycode
        if (isset($parts['host'])) {
            $parts['host'] = idn_to_ascii($parts['host'], IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        }

        // Кодируем путь (каждый сегмент отдельно)
        if (isset($parts['path'])) {
            $segments = explode('/', $parts['path']);
            foreach ($segments as &$segment) {
                $segment = rawurlencode($segment);
            }
            $parts['path'] = implode('/', $segments);
        }

        $encodedUrl = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) $encodedUrl .= ':' . $parts['port'];
        if (isset($parts['path'])) $encodedUrl .= $parts['path'];
        if (isset($parts['query'])) $encodedUrl .= '?' . $parts['query'];
        if (isset($parts['fragment'])) $encodedUrl .= '#' . $parts['fragment'];

        return $encodedUrl;
    }
    public function logMessage($message)
    {
        echo date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
    }

    public function getRandomUserAgent()
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.5481.178 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.5359.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.5249.119 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 12_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.64 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:101.0) Gecko/20100101 Firefox/101.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:98.0) Gecko/20100101 Firefox/98.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:92.0) Gecko/20100101 Firefox/92.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:91.0) Gecko/20100101 Firefox/91.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 11.0; rv:83.0) Gecko/20100101 Firefox/83.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_0) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.2 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.818.42 Safari/537.36 Edg/90.0.818.42',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.705.50 Safari/537.36 Edg/88.0.705.50',
            'Mozilla/5.0 (Linux; Android 10; SM-G973F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.152 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 11; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Mobile Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPad; CPU OS 14_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36 OPR/81.0.4196.31',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36 OPR/78.0.4093.147',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36 OPR/78.0.4093.147',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; Media Center PC 6.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; InfoPath.3; .NET4.0C; .NET4.0E; Zune 4.7)',
        ];
        return $userAgents[array_rand($userAgents)];
    }
}

    /*
   ----------------------------------
   -------Постраничный парсинг-------
   ----------------------------------
   */