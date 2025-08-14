<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParStynergy extends CParMain {
    static $name_parser = array(
        "Stynergy" => "-"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://stynergy.ru";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);

        $this->logMessage("Получение ссылок на категории...");
        $url = ["https://stynergy.ru/internet-magazin/"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//section[@id="_shop-grid-catalog"]//div[@class="section__body"]//div[@class="grid grid-3"]/a/@href',
            "absolute_link" => true
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 12);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//ul[@class="page-numbers"]/li/a',
            "last_button_id" => 2,
            "url_argument" => "page/",
            "html_argument" => "nodeValue",

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="product-grid grid grid-2"]/article/a'],
            "absolute_link" => true,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ul[@class="breadcrumb"]/li/a/span',
            "crumb_begin" => 1,
            "crumb_end" => 1,
            "title_selector" => '//div[@class="section__body"]/h1',
            "price_selector" => '//div[@class="single-product__price"]/div[@class="price"]/ins',
            "unit_selector" => '',

            "image_selector" => '//div[@class="swiper-wrapper"]/div/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@class="single-product__chars _chars"]//li',
            "prop1" => './/span[1]',
            "prop2" => './/span[2]',

            "description_selector" => '//div[@class="single-product__descr text-box"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParStynergy();
$parser->processParsing();