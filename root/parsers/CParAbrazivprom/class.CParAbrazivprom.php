<?php

const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParAbrazivprom extends CParMain {
    static $name_parser = array(
        "Abrazivprom" => "Абразивпром"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://abrazivprom.ru";
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
            "category_selector" => "//ul[@class='yo-fm fm-one']/li/ul//li/a/@href | //ul[@class='yo-fm fm-one']//li[not(.//li)]/a/@href",
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

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
            "title_selector" => ['//div[@class="row products-category"]/div//h4/a'],
            "absolute_link" => true,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ul[@class="breadcrumb"]/li/a',
            "crumb_begin" => 1,
            "crumb_end" => 2,
            "title_selector" => '//h1[@itemprop="name"]',
            "price_selector" => '//li[@class="price"]/span[@class="real"]',
            "unit_selector" => '',

            "image_selector" => '//div[@class="image-additional"]/a/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@id="tab-description"]//table[1]//tr',
            "prop1" => './/td[1]',
            "prop2" => './/td[2]',

            "description_selector" => '//div[@id="tab-description"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParAbrazivprom();
$parser->processParsing();