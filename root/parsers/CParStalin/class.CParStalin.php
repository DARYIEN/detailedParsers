<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParStalin extends CParMain {
    static $name_parser = array(
        "Stalin" => "-"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://stallin.ru";
        $this->author = "Никита";
        $this->batches = 20;
        $this->slower_parse = true;
    }

    function processParsing() {

        $startTime = microtime(true);

        $this->logMessage("Получение ссылок на категории...");
        $url = ["https://stallin.ru/catalog/"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[@class="catalog-section-list-items intec-grid intec-grid-wrap intec-grid-a-h-start intec-grid-a-v-start"]/div//a[@class="catalog-section-list-item-image"]/@href',
            "absolute_link" => true
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 22);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="system-pagenavigation-items-wrapper"]/div/a',
            "last_button_id" => 2,
            "url_argument" => "?PAGEN_2=",
            "html_argument" => "nodeValue",
//            ".html" => true

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="catalog-section-items intec-grid intec-grid-wrap intec-grid-a-v-stretch intec-grid-a-h-start intec-grid-i-15"]/div//div[@class="catalog-section-item-name"]/a[1]'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="breadcrumb-wrapper-2 intec-content-wrapper"]/div/a/span',
            "crumb_begin" => 1,
            "crumb_end" => 1,
            "title_selector" => '//h1[@class="intec-header"]',
            "price_selector" => '//div[@class="catalog-element-price-discount intec-grid-item-auto"]',
            "unit_selector" => '',

            "one_image_selector" => '',
            "image_selector" => '//a[@class="catalog-element-gallery-picture"]/div/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@class="catalog-element-section-properties"]/div',
            "prop1" => './/div[1]',
            "prop2" => './/div[2]',

            "description_selector" => '',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParStalin();
$parser->processParsing();