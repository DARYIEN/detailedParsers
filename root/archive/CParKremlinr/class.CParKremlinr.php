<?php


const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParKremlinr extends CParMain {
    static $name_parser = array(
        "Kremlinr" => "Евро Пейнтинг"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://kremlinrexson.ru";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);


        $this->logMessage("Получение ссылок на категории...");
        $url = [$this->site_link . "/product/"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[@class="col-md-6 col-sm-12 col-xs-12"]//div[@class="text childs"]/ul/li/a/@href',
            "absolute_link" => false
        ];
        $underCat = $this->gettingUrls($url, $data);

        #$underCat = array_slice($underCat, 0, 1);
        $this->logMessage("Найдено " . count($underCat) . " категорий.");


        $this->logMessage("Получение ссылок на подкатегории...");
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[@class="items row margin0 row_block flexbox nmac"]/div//div[@class="title"]/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($underCat, $data, true);

        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " подкатегорий.");


        $categories = [];
        foreach ($this->productCount as $cat => $product) {
            if (count($product) > 0) $categories = array_merge($categories, $product);
            else $categories[] = $cat;
        }
        foreach ($underCat as $ca) {
            if (!in_array($ca, array_keys($this->productCount))) {
                $categories[] = $ca;
            }
        }


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="row items flexbox"]/div//div[@class="title js-popup-title"]/a[1]'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($categories, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ul[@class="breadcrumb"]/li/a',
            "crumb_begin" => 2,
            "crumb_end" => 1,
            "title_selector" => '//h1[@id="pagetitle"]',
            "price_selector" => '',
            "unit_selector" => '',

            "image_selector" => '//ul[@class="slides items"]/li/a',
            "image_html_argument" => "href",
            "absolute_link" => false,

            "lit_selector" => '//div[@id="docs"]//div[@class="inner-wrapper"]/a',
            "prop_type" => "dual",
            "prop_selector" => '//div[@id="props"]//tr',
            "prop1" => './/td[1]/span',
            "prop2" => './/td[2]/span',

            "description_selector" => '//div[@class="content"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParKremlinr();
$parser->processParsing();