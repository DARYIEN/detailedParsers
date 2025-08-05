<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);

class CParKraskopult extends CParMain {
    static $name_parser = array(
        "Kraskopult" => "Краскопульт"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://pro-kraskopult.ru/";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="catalog-pagination"]/a',
            "last_button_id" => 2,
            "url_argument" => "/page/",
            "html_argument" => "nodeValue",

        ];
        $this->productCount = $this->gettingUrls(["https://pro-kraskopult.ru/catalog"], $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="catalog__list"]/div//div[@class="slider__item_footer"]/a'],
            "absolute_link" => true,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="kama_breadcrumbs"]/span[@itemprop="itemListElement"]',
            "crumb_begin" => 2,
            "crumb_end" => 1,
            "title_selector" => '//h1[@class="single__title"]',
            "price_selector" => '//span[@class="slider__item_price"]/text()',
            "unit_selector" => '',

            "image_selector" => '//div[@class="single__card_slider"]/div/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//table[@class="slider__item_tth single__table-tth"]//tr',
            "prop1" => './/td[1]',
            "prop2" => './/td[2]',

            "description_selector" => '//div[@class="single__description"]/div[@class="single__text"]/p/text()',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParKraskopult();
$parser->processParsing();