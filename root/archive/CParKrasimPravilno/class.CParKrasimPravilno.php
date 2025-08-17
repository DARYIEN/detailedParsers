<?php


const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);

class CparKrasimPravilno extends CParMain {
    static $name_parser = array(
        "KrasimPravilno" => "Красим Правильно"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://krasimpravilno.ru";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);


        $this->logMessage("Получение ссылок на категории...");
        $url = [$this->site_link];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[@class="row no-gutters"]/div/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        $this->productCount = array_slice($this->productCount, 1, count($this->productCount) - 1);
        #$this->productCount = array_slice($this->productCount, 0, 1);

        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//ul[@class="pagination"]/li/a',
            "last_button_id" => 1,
            "url_argument" => "?page=",
            "html_argument" => "href=",

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="row no-gutters rm-category-products"]/div//div[@class="rm-module-title"]/a'],
            "absolute_link" => true,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");




        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ul[@class="breadcrumb rm-breadcrumb"]/li/a',
            "crumb_begin" => 1,
            "crumb_end" => 1,
            "title_selector" => '//div[@class="rm-product-title order-1 order-md-0"]/h1',
            "price_selector" => '//div[@class="rm-product-center-price"]/span',
            "unit_selector" => '',

            "image_selector" => '//div[@class="rm-product-slide"]/a',
            "image_html_argument" => "href",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@class="rm-product-center-info"]/div[1] | //div[@class="rm-product-center-info"]/div[2]',
            "prop1" => './/span[1]',
            "prop2" => './/span[2]',
            "prop_comp_selector" => '//div[@class="form-group"]/div[1]',
            "prop_comp1" => './/label[@class="rm-control-label"]',
            "prop_comp2" => './/div/div/label',

            "description_selector" => '//div[@id="product_description"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParKrasimPravilno();
$parser->processParsing();