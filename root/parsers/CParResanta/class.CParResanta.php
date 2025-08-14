<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParResanta extends CParMain {
    static $name_parser = array(
        "Resanta" => "Ресанта"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://resanta.ru";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);

        $url = ["https://resanta.ru/category/svarochnoe-oborudovanie-resanta/svarochnye-apparaty/?page=1",
            "https://resanta.ru/category/svarochnoe-oborudovanie-resanta/svarochnye-apparaty/?page=2",
            "https://resanta.ru/category/svarochnoe-oborudovanie-resanta/svarochnye-apparaty/?page=3"
        ];
        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="jb-product-section col-sm-12"]/div/div//h2[@class="product-title"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($url, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ul[@class="breadcrumb"]/li/a/span',
            "crumb_begin" => 1,
            "crumb_end" => 1,
            "title_selector" => '//h1[@class="product-title"]',
            "price_selector" => '//div[@class="purchase"]//span[@class="price nowrap"]',
            "unit_selector" => '',

            "image_selector" => '//div[@class="galleryplus-content"]/i[@class="galleryplus-lazyload"]',
            "image_html_argument" => "data-img",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//table[@class="features"]//tr',
            "prop1" => './/td[1]',
            "prop2" => './/td[2]',

            "description_selector" => '//div[@id="product-description"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParResanta();
$parser->processParsing();

