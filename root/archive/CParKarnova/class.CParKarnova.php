<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParKarnova extends CParMain {
    static $name_parser = array(
        "Karnova" => "Карнова"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://www.karnova.ru/";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);

        $url = ["https://www.karnova.ru/derevo/okrasochnoe-oborudovanie/"];
        $this->logMessage("Получение ссылок на категории...");
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[@class="col-sm-12 cat-list cat-list2"]/div/a[@class="name"]/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="row"]//div[@class="col-md-9 "]//div[@class="row"]/div/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//span[@class="B_crumbBox"]/a',
            "crumb_begin" => 1,
            "crumb_end" => 1,
            "title_selector" => '//div[@class="col-md-12"]/h1',
            "price_selector" => '//span[@class="price_value"]',
            "unit_selector" => '',

            "image_selector" => '//div[@class="pr-left"]/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

//            "prop_type" => "dual",
//            "prop_selector" => '//div[@class="catalog-element__characteristics-block characteristics-second"]/div',
//            "prop1" => './/div',
//            "prop2" => './/a',

            "description_selector" => '//div[@class="col-md-9 text"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParKarnova();
$parser->processParsing();