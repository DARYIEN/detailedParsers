<?php


const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParSpecOkraska extends CParMain {
    static $name_parser = array(
        "SpecOkraska" => "Спец Окраска"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://specokraska.ru";
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
            "category_selector" => '//div[@class="main-menu"]/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 12, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");

        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="_spares-items__page"]/span/a',
            "last_button_id" => 1,
            "url_argument" => "?PAGEN_2=",
            "html_argument" => "nodeValue",

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="goods-content "]/div//h4[@class="goods-content__item-name _text-elipsis clamp"]/a[1]'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="breadcrumbs-body"]/a',
            "crumb_begin" => 2,
            "crumb_end" => 2,
            "title_selector" => '//h1[@class="spares-top__title"]',
            "price_selector" => '//div[@class="card-info__col"]//div[@class="_price"]/span',
            "unit_selector" => '',

            "image_selector" => '//div[@class="swiper-wrapper"]/div/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "lit_selector" => '//div[@class="card-about__content"]/a[@class="_file"]',
            "prop_type" => "dual",
            "prop_selector" => '//div[@id="section-characteristics"]//div[@class="nj6"]/div/dl',
            "prop1" => './/span',
            "prop2" => './/dd',

            "description_selector" => '//div[@class="card-about__content"]/p',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParSpecOkraska();
$parser->processParsing();