<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParPartnerSt extends CParMain {
    static $name_parser = array(
        "ParSt" => "-"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://par-st.ru";
        $this->author = "Никита";
        $this->batches = 12;
    }

    function processParsing() {

        $startTime = microtime(true);

        $this->logMessage("Получение ссылок на категории...");
        $url = ["https://par-st.ru"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//ul[@class="menu dropdown"]/li/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="nums"]/a',
            "last_button_id" => 1,
            "inc" => '',
            "url_argument" => "?PAGEN_1=",
            "html_argument" => "nodeValue",


        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        $this->productCount = array_slice($this->productCount, 1050, 450);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="catalog_block items block_list grid-list grid-list--compact grid-list--no-gap grid-list--items-4-1200 grid-list--items-3-992 grid-list--items-2-768 grid-list--items-2-601"]/div//div[@class="item-title"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="breadcrumbs"]/div/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 1,
            "title_selector" => '//h1[@id="pagetitle"]',
            "price_selector" => '//div[@class="prices_block"]//span[@class="price_value"]',
            "unit_selector" => '',

            "one_image_selector" => '',
            "image_selector" => '//div[@class="slides"]/ul/li/a',
            "image_html_argument" => "href",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//table[@class="props_list nbg"]//tr',
            "prop1" => './/td[1]/div/span',
            "prop2" => './/td[2]/span',

            "description_selector" => '//div[@class="detail_text"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CparPartnerSt();
$parser->processParsing();