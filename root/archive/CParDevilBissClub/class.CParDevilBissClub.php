<?php


const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParDevilBissClub extends CParMain {
    static $name_parser = array(
        "DevilBissClub" => "ДевилБиссКлаб"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://devilbiss.club";
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
            "category_selector" => '//ul[@class="ty-menu__items cm-responsive-menu"]/li/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="grid-list"]/div//div[@class="ty-grid-list__item-name"]/a'],
            "absolute_link" => true,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="ty-breadcrumbs clearfix"]/a',
            "crumb_begin" => 1,
            "crumb_end" => 1,
            "title_selector" => '//h1[@class="ty-product-block-title"]',
            "price_selector" => '-',
            "price_html_argument" => 'content',
            "unit_selector" => '',

            "image_selector" => '//div[@class="ty-product-thumbnails ty-center cm-image-gallery"]/a/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "lit_selector" => '//div[@id="content_attachments"]//a',
            "prop_comp_selector" => '//div[@class="cm-picker-product-options ty-product-options"]/div',
            "prop_comp1" => './/label',
            "prop_comp2" => './/ul/li/label/text()',
            "prop_type" => "dual",
            "prop_selector" => '//div[@id="content_features"]/div',
            "prop1" => './/span',
            "prop2" => './/div',

            "description_selector" => '//div[@id="content_description"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParDevilBissClub();
$parser->processParsing();