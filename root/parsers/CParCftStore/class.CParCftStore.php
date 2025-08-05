<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParCftStore extends CParMain {
    static $name_parser = array(
        "CftStore" => "-"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://cftstore.ru";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//ul[@class="page-numbers"]/li/a',
            "last_button_id" => 2,
            "url_argument" => "/page/",
            "html_argument" => "nodeValue",

        ];
        $this->productCount = $this->gettingUrls(["https://cftstore.ru/product-category/vid/kraskopulty/", "https://cftstore.ru/product-category/vid/okrasochnye-ustanovki/", "https://cftstore.ru/product-category/vid/nasosy-podachi-krasok/"], $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="product__items"]/div//div[@class="product__card-description"]/a'],
            "absolute_link" => true,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="breadcrumbs"]/span/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 2,
            "title_selector" => '//h1[@class="woocommerce-products-header__title page-title"]',
            "price_selector" => '//span[@class="woocommerce-Price-amount amount"]/bdi/text()',
            "unit_selector" => '',

            "image_selector" => '//div[@class="woocommerce__slider-thumbs"]/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//table[@class="woocommerce-product-attributes shop_attributes"]/tr',
            "prop1" => './/th',
            "prop2" => './/td/p/a',

            "description_selector" => '//div[@class="woocommerce-Tabs-panel woocommerce-Tabs-panel--description panel entry-content wc-tab"]/p | //div[@class="woocommerce-Tabs-panel woocommerce-Tabs-panel--description panel entry-content wc-tab"]/ul/li',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CparCftStore();
$parser->processParsing();

