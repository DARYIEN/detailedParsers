<?php

const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParAutoequip extends CParMain {
    static $name_parser = array(
        "Autoequip" => "Авто эквип"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://autoequip.ru";
        $this->author = "Никита";
        $this->batches = 8;
        $this->slower_parse = true;
    }

    function processParsing() {

        $startTime = microtime(true);


        $this->logMessage("Получение ссылок на категории...");
        $url = ["https://autoequip.ru/sitemap/"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[@class="link-tree link-tree_default link-tree_level_2"]//a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 175, 2);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="goods goods_simple goods_big goods_no-firm-info stickers-top-left"]/div/a[@class="lnk goods__name"]'],
            "absolute_link" => true,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="crumbs crumbs_6"]/div//meta[@itemprop="name"]',
            "crumb_html_argument" => 'content',
            "crumb_begin" => 2,
            "crumb_end" => 3,
            "title_selector" => '//div[@class="flex-row flex-x-space-between flex-y-center gap-20"]/h1',
            "price_selector" => '//span[@class="price price_existing notranslate break-word"]',
            "unit_selector" => '',

            "image_selector" => '//div[@class="goods-card__one-img-wrap"]/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@class="row row-10"]/div//div[@class="info-table__table"]/div',
            "prop1" => './/div[@class="info-table__title"]',
            "prop2" => './/div[@class="info-table__value"]',

            "description_selector" => '//div[@class="mb10-not-last overtext text"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParAutoequip();
$parser->processParsing();