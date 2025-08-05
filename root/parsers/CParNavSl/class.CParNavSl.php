<?php

const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParNavSl extends CParMain {
    static $name_parser = array(
        "NavSl" => "-"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://navsl.ru";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//ul[@class="pagination"]/li/a',
            "last_button_id" => 1,
            "url_argument" => "?page=",
            "html_argument" => "href=",

        ];
        $this->productCount = $this->gettingUrls(['https://navsl.ru/katalog/okrasochnoe-oborudovanie/'], $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="row"]/div//a[@class="products__item-title"]'],
            "absolute_link" => true,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ul[@class="breadcrumbs__menu"]/li/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 1,
            "title_selector" => '//div[@class="sku__heading"]/h1',
            "price_selector" => '//p[@class="sku__price"]',
            "unit_selector" => '',

            "image_selector" => '//div[@class="sku__slides js-gallery-slides"]/a',
            "image_html_argument" => "href",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@data-tabs-content="tab_attribute"]//div[@class="row"]/div/dl',
            "prop1" => './/dt',
            "prop2" => './/dd',

            "description_selector" => '//div[@data-tabs-content="tab_description"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParNavSl();
$parser->processParsing();