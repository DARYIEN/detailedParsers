<?php

const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParVpokrasku extends CParMain {
    static $name_parser = array(
        "VPokrasku" => "ВПокраску"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://vpokrasku.ru";
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
            "category_selector" => '//div[@class="categoty_items"]/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");

        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="navigation-pages"]/a',
            "last_button_id" => 2,
            "url_argument" => "?PAGEN_1=",
            "html_argument" => "href=",

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="catalog-section products_list"]/div//div[@class="item-inner item-view-simple"]/a[1]'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="breadcrumbs"]//div/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 2,
            "title_selector" => '//div[@class="text"]/h1',
            "price_selector" => '//div[@class="price"]/p',
            "unit_selector" => '',

            "image_selector" => '//div[@class="images"]/div/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "lit_selector" => '//ul[@class="LitList"]/li/a',
            "prop_type" => "dual",
            "prop_selector" => '//div[@data-value="properties"]//tr',
            "prop1" => './/td[1]',
            "prop2" => './/td[2]',

            "description_selector" => '//div[@class="text_wrap"]//div[@class="row"]/text()',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParVpokrasku();
$parser->processParsing();