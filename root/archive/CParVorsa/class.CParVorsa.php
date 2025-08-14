<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParVorsa extends CParMain {
    static $name_parser = array(
        "Vorsa" => "Ворса Урал"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://vorsa.ru/";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);

//"https://vorsa.ru/catalog/svarochnoe_oborudovanie/"
        $url = ["https://vorsa.ru/catalog/oborudovanie_okrasochnoe/"];


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="navigation-pages"]/a',
            "last_button_id" => 1,
            "url_argument" => "?PAGEN_1=",
            "html_argument" => "href=",

        ];
        $this->productCount = $this->gettingUrls($url, $data, true);
        #$this->productCount = array_slice($this->productCount, 4, 1);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");

        $baselink = "catalog_block items block_list grid-list grid-list--compact grid-list--no-gap grid-list--items-4-1200 grid-list--items-3-992 grid-list--items-2-768 grid-list--items-2-601";
        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="'.$baselink.'"]/div//div[@class="item-title"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="breadcrumbs"]/div/a/span[1]',
            "crumb_begin" => 2,
            "crumb_end" => 1,
            "title_selector" => '//h1[@id="pagetitle"]',
            "price_selector" => '//span[@class="price_value"]',
            "unit_selector" => '',

            "image_selector" => '//div[@class="slides"]/ul/li//img',
            "image_html_argument" => "src",
            "absolute_link" => false,

//            "prop_type" => "dual",
//            "prop_selector" => '//div[@class="catalog-element__characteristics-block characteristics-second"]/div',
//            "prop1" => './/div',
//            "prop2" => './/a',

            "description_selector" => '//div[@class="tabs_section"]/div/div',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParVorsa();
$parser->processParsing();