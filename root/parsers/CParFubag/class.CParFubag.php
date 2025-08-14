<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParFubag extends CParMain {
    static $name_parser = array(
        "Fubag" => "-"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://fubag.ru";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);


        $url = ["https://fubag.ru/catalog/svarochnoe-oborudovanie/?SHOWALL_1=1"];

        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[contains(@class, "catalog_block") and contains(@class, "items") and contains(@class, "row")]/div//div[@class="item-title"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($url, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="breadcrumbs swipeignore"]/div/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 1,
            "title_selector" => '//h1[@id="pagetitle"]',
            "price_selector" => '//div[@class="offers"]/meta[@itemprop="price"]',
            "price_html_argument" => "content",
            "unit_selector" => '',

            "image_selector" => '//div[contains(@class, "product-detail-gallery__slider") and contains(@class, "owl-carousel")]/div//img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@id="props"]//table[@class="props_list nbg"]//tr',
            "prop1" => './/td[1]/div/span',
            "prop2" => './/td[2]/span',

            "description_selector" => '//div[@id="desc"]/div',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParFubag();
$parser->processParsing();