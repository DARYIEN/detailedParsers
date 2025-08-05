<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);

class CParIndustry extends CParMain {
    static $name_parser = array(
        "Industry" => "Индастри"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://ids3.ru";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="bx-pagination-container"]//li/a',
            "last_button_id" => 2,
            "url_argument" => "?PAGEN_1=",
            "html_argument" => "href=",

        ];
        $this->productCount = $this->gettingUrls(["https://ids3.ru/catalog/svarochnoe-oborudovanie/", "https://ids3.ru/catalog/okrasochnoe-oborudovanie/"], $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="catalog type-column"]/div//div[@class="catalog-title"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="bx-breadcrumb"]/div/a',
            "crumb_begin" => 2,
            "crumb_end" => 3,
            "title_selector" => '//div[@class="wp"]//h1',
            "price_selector" => '//div[@class="current-price"]/meta[@itemprop="price"]',
            "price_html_argument" => "content",
            "unit_selector" => '',

            "image_selector" => '//ul[@class="slides"]/a/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@class="product-features"]/div',
            "prop1" => './/div[1]',
            "prop2" => './/div[2]',

            "description_selector" => '//div[@class="card-left"]/div[@class="text"]/p/text() | //div[@class="card-left"]//div[@class="card-tags-info act"]/text()',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParIndustry();
$parser->processParsing();