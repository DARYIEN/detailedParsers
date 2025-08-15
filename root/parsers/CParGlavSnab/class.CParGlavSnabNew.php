<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParGlavSnab extends CParMain {
    static $name_parser = array(
        "GlavSnab" => "-"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://glavsnab.net";
        $this->author = "Никита";
        $this->batches = 2;
        $this->slower_parse = true;
    }

    function processParsing() {

        $startTime = microtime(true);

        $this->logMessage("Получение ссылок на категории...");
        $url = ["https://glavsnab.net/krovlya-fasad-zabor.html", "https://glavsnab.net/krepezh-1.html", "https://glavsnab.net/stroymateriali.html"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//section[@class="catalog-category container"]/div/a[1]/@href',
            "absolute_link" => true
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        $this->productCount = array_slice($this->productCount, 35, 20);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//ul[@class="pagination"]/li/a',
            "last_button_id" => 2,
            "url_argument" => "?p=",
            "html_argument" => "nodeValue",
//            ".html" => true

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="result-search clearfix"]/div//div[@class="product-card__name"]/a[1]'],
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
            "crumb_end" => 0,
            "title_selector" => '//h1[@class="product-header"]',
            "price_selector" => '//div[@id="price-active"]//span[@class="nav"]',
            "unit_selector" => '',

            "one_image_selector" => '',
            "image_selector" => '//div[@class="slick-track"]/div//img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@class="product-spec-wrap row"]/div/dl',
            "prop1" => './/dt',
            "prop2" => './/dd/a',

            "description_selector" => '//div[@class="collapse-list-content--desc"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CparGlavSnab();
$parser->processParsing();