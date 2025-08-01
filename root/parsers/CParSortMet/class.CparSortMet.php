<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParSortMet extends CParMain {
    static $name_parser = array(
        "SortMet" => "СортМет"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://sortmet.ru";
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
            "category_selector" => '//div[@class="rownew"]/div//a[@class="state_category"]/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");

        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="pagination pagination_center"]/a',
            "last_button_id" => 1,
            "url_argument" => "?PAGEN_3=",
            "html_argument" => "href=",

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="catalog-section__list-inner bx-red"]/div//a[@class="card-line__name"]'],
            //"price_selector" => ['//div[@class="catalog-section__list-inner bx-red"]/div//span[@itemprop="price"]'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ul[@class="breadcrumbs"]/li/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 3,
            "title_selector" => '//h1[@class="catalog-element__content-heading"]',
            "price_selector" => '//div[@class="price-current-list"]/div/span[1]',
            "unit_selector" => '//div[@class="price-current-list"]/div/span[2]',

            "image_selector" => '//div[@class="catalog-element__top"]//div[@id="element-slider"]//div[@class="swiper-wrapper"]/div/span/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@class="catalog-element__characteristics-block characteristics-second"]/div',
            "prop1" => './/div',
            "prop2" => './/a',

            "description_selector" => '//div[@class="catalog-element__desctiption-text"]/p',
        ];
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParSortMet();
$parser->processParsing();