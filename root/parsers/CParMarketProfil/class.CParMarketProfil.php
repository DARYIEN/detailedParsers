<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParMarketProfil extends CParMain {
    static $name_parser = array(
        "MarketProfil" => "-"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://www.marketprofil.ru";
        $this->author = "Никита";
        $this->batches = 15;
    }

    function processParsing() {

        $startTime = microtime(true);

        $this->logMessage("Получение ссылок на категории...");
        $url = ["https://www.marketprofil.ru"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[@class="mod-menu mod-list nav menu_catalog"]/div/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="vm-pagination vm-pagination-bottom"]//li/a',
            "inc" => 30,
            "pagination_type" => "virtue",
            "last_button_id" => 1,
            "url_argument" => "&start=",
            "html_argument" => "nodeValue",


        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="browse-view"]/div//div[@class="vm-product-descr-container-0"]//a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ol[@class="mod-breadcrumbs breadcrumb px-3 py-2"]/li/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 0,
            "title_selector" => '//h1[@class="h1-desktop"]',
            "price_selector" => '//div[@class="base_price"]/text()',
            "unit_selector" => '',

            "one_image_selector" => '',
            "image_selector" => '//div[@class="main-image"]/a',
            "image_html_argument" => "href",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@class="product-fields"]/div',
            "prop1" => './/span//strong',
            "prop2" => './/div/a',

            "description_selector" => '//div[@class="product-description"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CparMarketProfil();
$parser->processParsing();