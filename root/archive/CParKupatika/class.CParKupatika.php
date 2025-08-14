<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParKupatika extends CParMain {
    static $name_parser = array(
        "SortMet" => "СортМет"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://www.kupatika.ru";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);


        $this->logMessage("Получение ссылок на категории...");
        $url = ["https://www.kupatika.ru/catalog/svarochnoe-oborudovanie/"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[@class="barnd-catalog-row flex-row flex-wrap"]/div//div[@class="barnd-catalog-title"]/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);
        $this->productCount[] = "https://www.kupatika.ru/catalog/pnevmaticheskie-kraskopulty/";
        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");

        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//ul[@class="pagination flex-row"]/li/a',
            "last_button_id" => 2,
            "url_argument" => "/page/",
            "html_argument" => "nodeValue",

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="flex-row flex-wrap"]/div//div[@class="product-title"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ul[contains(@class, "breadcrumbs")]/li/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 1,
            "title_selector" => '//h1[@itemprop="name"]',
            "price_selector" => '//span[@id="main-price"]',
            "unit_selector" => '',

            "image_selector" => '//div[@class="card-slider-big-item-img"]/img | //div[@class="card-slider__item-link cryzoom"]/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@class="characteristics-block"]/ul/li',
            "prop1" => './/div[1]',
            "prop2" => './/div[3]',

            "description_selector" => '//div[@itemprop="description"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParKupatika();
$parser->processParsing();

