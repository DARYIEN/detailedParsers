<?php

const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);

class CParProfmetall extends CParMain {
    function start() {
        $this->processParsing();
    }

    static $name_parser = array(
        "Profmetall" => "Профметалл"
    );

    function __construct()
    {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://profmetall50.ru";
        $this->author = "Никита";
        $this->batches = 40;
    }

    function processParsing()
    {
        $startTime = microtime(true);


        $this->logMessage("Получение ссылок на подкатегории...");
        $url = [$this->site_link];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на подкатегории",
            "category_selector" => "//li/a[contains(@href, '/catalog/')]/following-sibling::ul//li/a[not(following-sibling::ul)]/@href",
            "absolute_link" => false
        ];
        $categories = $this->gettingUrls($url, $data);
        $categories = array_slice($categories, 84, 12);

        $this->logMessage("Найдено " . count($categories) . " подкатегорий.");

        $data = [
            "title" => "Пагинация",
            "log" => "страниц пагинации",
            "paginate_selector" => "//div[@class='pagination-list']/a",
            "last_button_id" => 1,
            "url_argument" => "?PAGEN_1=",
            "html_argument" => "href="
        ];
        $this->productCount = $this->gettingUrls($categories, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 1);

        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");

        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ["//ul[@class='list layout-products -col-4 js-catalog-items']/li//div[@class='product-title']/a"],
            #"price_selector" => ["//ul[@class='list layout-products -col-4']/li//div[@class='product-price']"],
            "title_html_argument" => "href",
            #"price_ban_list" => ["0.00", "Цена по запросу"],
        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, false);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",

            "crumb_selector" => '//ul[@class="list breadcrumbs"]/li/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 1,

            "title_selector" => '//div[@class="layout-group"]/h1',
            "price_selector" => '//div[@class="item-section"]//div[@class="item-price"]',
            "price_html_argument" => "data-price",
            "unit_selector" => '//div[@class="item-section"]//div[@class="item-price"]/text()',
            "unit_item" => 1,
            "image_selector" => '//div[@class="item-gallery"]/a',
            "image_html_argument" => "href",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@class="item-description"]//tr/td',
            "prop1" => './/b/text()',
            "prop2" => './/b/following-sibling::text()[1]',

            "description_selector" => '//div[@class="tabs-item -active"]/p',
        ];
        #array_slice($this->productCount, 0, 11);
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}

$parser = new CParProfMetall();
$parser->start();