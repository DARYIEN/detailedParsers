<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParOkraskaPro extends CParMain {
    static $name_parser = array(
        "OkraskaPro" => "Окраска про"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://okraskapro.ru";
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
            "category_selector" => '//div[@class="grid-list mobile-scrolled mobile-scrolled--items-2 mobile-offset grid-list--items-3"]/div//a[@class="sections-list__item-link"]/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");

        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="nums"]/a',
            "last_button_id" => 1,
            "url_argument" => "?PAGEN_1=",
            "html_argument" => "href=",

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="js_append ajax_load block grid-list grid-list--items-2-991 grid-list--items-4 grid-list--gap-20 grid-list--items-4"]/div//div[@class="catalog-block__info-title linecamp-4 height-auto-t600 font_16"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="breadcrumbs swipeignore"]/div/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 1,
            "title_selector" => '//h1[@class="font_36 switcher-title"]',
            "price_selector" => '//span[@class="price__new-val font_17"]/meta[@itemprop="price"]',
            "price_html_argument" => "content",
            "unit_selector" => '',

            "image_selector" => '//div[@class="catalog-detail__gallery-slider big owl-carousel owl-carousel--outer-dots owl-carousel--nav-hover-visible owl-bg-nav owl-carousel--light owl-carousel--button-wide owl-carousel--button-offset-half js-detail-img"]/div/a',
            "image_html_argument" => "href",
            "absolute_link" => false,

            "lit_selector" => '//div[@class="line-block__item font_13 color_999"]/a',
            "prop_type" => "dual",
            "prop_selector" => '//div[@class="properties__container properties js-offers-prop"]/div | //tbody[@class="block-wo-title js-offers-prop"]//tr',
            "prop1" => './/div[1] | .//span[@itemprop="name"]',
            "prop2" => './/div[3] | .//span[@itemprop="value"]',

            "description_selector" => '//div[@itemprop="description"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParOkraskaPro();
$parser->processParsing();