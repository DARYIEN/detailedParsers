<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParCenterMk extends CParMain {
    static $name_parser = array(
        "CenterMk" => "-"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://moscow.centermk.ru";
        $this->author = "Никита";
        $this->batches = 30;
    }

    function processParsing() {

        $startTime = microtime(true);

        $this->logMessage("Получение ссылок на категории...");
        $url = ["https://moscow.centermk.ru/catalog/"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//ul[@class="nav nav-list side-menu"]/li/span/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 12);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="nums"]/a',
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
            "title_selector" => ['//div[@class="catalog-block"]/div/div//div[@class="catalog-block__info-title lineclamp-3 height-auto-t600 font_14"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="breadcrumbs swipeignore line-height-0"]/div/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 1,
            "title_selector" => '//h1[@class="font_24 switcher-title js-popup-title mb mb--0"]',
            "price_selector" => '//div[@class="js-popup-block-adaptive grid-list grid-list--items gap gap--24"]//span[@class="price__new-val font_24"]',
            "unit_selector" => '',

            "one_image_selector" => '//div[@itemprop="description"]/img',
            "image_selector" => '//div[@class="detail-gallery-big-slider__wrapper swiper-wrapper"]/div/a',
            "image_html_argument" => "href",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@class="properties-group__items js-offers-group__items-wrap font_15"]/div',
            "prop1" => './/div/span',
            "prop2" => './/div/div',

            "description_selector" => '//div[@itemprop="description"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParCenterMk();
$parser->processParsing();