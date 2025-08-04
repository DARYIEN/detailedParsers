<?php


const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParDevilBiss extends CParMain {
    static $name_parser = array(
        "DevilBiss" => "ДевилБисс"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://devilbiss-rus.ru";
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
            "category_selector" => '//ul[@id="vertical-multilevel-menu"]/li/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="wrap_product_block ourMain"]/div//div[@class="product_block_info"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="breadcrumbs"]//li/a/span',
            "crumb_begin" => 1,
            "crumb_end" => 1,
            "title_selector" => '//h1[@class="text_big_part_name name_choose_product_item"]',
            "price_selector" => '//span[@id="itemPrice"]',
            "price_html_argument" => 'content',
            "unit_selector" => '',

            "image_selector" => '//div[@class="selected_popup_mini_photo_wrap"]/div/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "lit_selector" => '//div[@class="menu_info_content_special_feature"]/a[1]',
            "prop_type" => "dual",
            "prop_selector" => '//div[@class="menu_info_content_special_feature"]//tr',
            "prop1" => './/td[1]',
            "prop2" => './/td[2]',

            "description_selector" => '//div[@class="menu_info_content_special_feature"]/p | //div[@class="menu_info_content_special_feature"]//li',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParDevilBiss();
$parser->processParsing();