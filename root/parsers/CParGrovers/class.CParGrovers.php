<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParGrovers extends CParMain {
    static $name_parser = array(
        "Grovers" => "Гроверс"
    );
    function __construct() {
        $this->site_link = "https://grovers.ru";
        $this->dual_cost = false;
        $this->decimal = false;
        $this->author = "Никита";
        $this->batches = 15;
        $this->slower_parse = true;
    }

    function processParsing() {

        $startTime = microtime(true);

        $this->logMessage("Получение ссылок на категории...");
        $url = [$this->site_link . "/catalog/"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//ul[@class="menu side-menu side-menu-accordion"]//a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 1, 12);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//ul[@class="pagination"]/li/a',
            "last_button_id" => 2,
            "url_argument" => "?PAGEN_2",
            "html_argument" => "nodeValue",
//            ".html" => true

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 5);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $base_selector = '//div[@class="products grid per-row-4 clearfix"]/div';
        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => [$base_selector . '//div[@class="name"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");

        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ul[@class="breadcrumbs clearfix"]/li/a',
            "crumb_begin" => 2,
            "crumb_end" => 0,
            "title_selector" => '//div[@class="h1-bl"]/h1',
            "price_selector" => '//div[@class="buy-block-item"]/div',
            "unit_selector" => '',

            "one_image_selector" => '',
            "image_selector" => '//div[@class="slider detail-thumbs-slider hidden-xs"]/div/a',
            "image_html_argument" => "href",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//table[@class="full-specs"]//tr',
            "prop1" => './/td[1]',
            "prop2" => './/td[2]',

            "description_selector" => '//div[@id="pp-description"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParGrovers();
$parser->processParsing();