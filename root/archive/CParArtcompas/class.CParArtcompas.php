<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParArtcompas extends CParMain {
    static $name_parser = array(
        "ArtCompas" => "Арт компас"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://artcompas.ru";
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
            "category_selector" => '//ul[@class="menu dropdown"]/li/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 12);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");

        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="nums"]/a',
            "last_button_id" => 1,
            "url_argument" => "?PAGEN_1=",
            "html_argument" => "nodeValue",

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="display_list show_un_props"]/div//div[@class="item-title item-title mobile-hidden"]/a | //div[@class="catalog_block items block_list"]/div//div[@class="item-title"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@id="navigation"]/span[@itemprop="itemListElement"]/a/span',
            "crumb_begin" => 1,
            "crumb_end" => 1,
            "title_selector" => '//h1[@id="pagetitle"]',
            "price_selector" => '//div[@class="price"]/span',
            "unit_selector" => '',

            "image_selector" => '//div[@class="slides"]/ul/li/a/img',
            "image_html_argument" => "data-src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//table[@class="table-card"]//tr',
            "prop1" => './/td[1]/span',
            "prop2" => './/td[2]/span',

            "description_selector" => '//div[@class="tabs_content_text"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParArtcompas();
$parser->processParsing();