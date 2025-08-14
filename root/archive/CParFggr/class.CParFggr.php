<?php


const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParFggr extends CParMain {
    static $name_parser = array(
        "Finishing group" => "-"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://fggr.ru";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);


        $this->logMessage("Получение ссылок на категории...");
        $url = [$this->site_link . "/catalog/"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[@class="main-section__list row row--stretch"]//a[@class="main-section__categorys-title"]/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 12, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");

        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="pagination main-section__pagination"]/div/a',
            "last_button_id" => 2,
            "url_argument" => "?PAGEN_1=",
            "html_argument" => "nodeValue",

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="main-section__list row row--stretch"]/div//h4[@class="product-block__title"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ul[@class="breadcrumb__ul"]/li/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 1,
            "title_selector" => '//div[@class="main-section__title title__nooon title__nooon title-h1 d-none_md d-none_sm d-none_xs"]',
            "price_selector" => '//div[@class="main-section__info-col col-6 col-lg-5 col-md-12"]//div[@class="main-section__price"]/text()',
            "unit_selector" => '',

            "image_selector" => '//div[@class="main-section__thumbs js-product-thumbs"]/div//img',
            "image_html_argument" => "data-src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@class="main-section__specifications d-none_md d-none_sm d-none_xs"]/div',
            "prop1" => './/div[1]',
            "prop2" => './/div[2]',

            "prop_selector2" => '//div[@class="card-brand-info"]/span',

            "description_selector" => '//div[@class="main-section__tabs-body tabs__body is-visible item-page"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParFggr();
$parser->processParsing();