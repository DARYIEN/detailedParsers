<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParBlockStock extends CParMain {

    static $name_parser = array(
        "BlockStock" => "Блоксток"
    );

    function __construct() {
        $this->author = "Никита";
        $this->site_link = "https://blockstock.ru";
        $this->batches = 40;
    }

    function processParsing()
    {

        $startTime = microtime(true);


        $this->logMessage("Получение ссылок на категории...");
        $url = [$this->site_link . "/catalog/"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[contains(@class, "section_item item")]//td[contains(@class, "image")]/a/@href'
        ];
        $categories = $this->gettingUrls($url, $data);
        #$categories = array_slice($categories, 0, 1);
        $this->logMessage("Найдено " . count($categories) . " категорий.");


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[contains(@class, "nums")]//a',
            "last_button_id" => 1,
            "url_argument" => "?PAGEN_1=",
            "html_argument" => "href="
        ];
        $pageLinks = $this->gettingUrls($categories, $data, true);
        $this->logMessage("Получено " . count($pageLinks) . " ссылок на страницы с товарами.");


        $baseSelector = '//div[contains(@class, "container")]' .
            '//div[contains(@class, "js_wrapper_items") and contains(@class, "has_mobile_filter_compact")]' .
            '//div[contains(@class, "right_block1") and contains(@class, "clearfix") and contains(@class, "catalog") and contains(@class, "vertical")]' .
            '//div[contains(@class, "inner_wrapper")]' .
            '//div[contains(@class, "ajax_load") and contains(@class, "block")]' .
            '//div[contains(@class, "top_wrapper margin0") and contains(@class, "show_un_props")]' .
            '//div[contains(@class, "catalog_item_wrapp ") and contains(@class, "item")]';

        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => [$baseSelector . '//div[contains(@class, "item_info--top_block")]//div[contains(@class, "item-title")]/a'],
            "price_selector" => [$baseSelector . '//div[contains(@class, "item_info--bottom_block")]//div[contains(@class, "price_matrix_block")]//div[@class="price"]//span[contains(@class, "price_value")]'],
            "price_ban_list" => ["0.00"],
            "title_html_argument" => "href",
        ];
        #$pageLinks = array_slice($pageLinks, 0, 1);
        $this->productCount = $this->gettingUrls($pageLinks, $data, false);


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "price_selector" => '//div[contains(@class, "middle_info") and contains(@class, "main_item_wrapper")]//div[@class="price"]',
            "title_selector" => '//h1[contains(@id, "pagetitle")]',
            "image_selector" => '//div[@class="slides"]/ul/li/a/img',
            "image_html_argument" => "data-src",
            "absolute_link" => false,
            "crumb_begin" => 2,
            "crumb_end" => 1,
            "crumb_selector" => '//div[@class="breadcrumbs"]/div/a/span[@itemprop="name"]',
            "description_selector" => '//div[@class="detail_text"]/p',
            "unit_selector" => '//span[@class="price_measure"]',
            "prop_type" => "dual",
            "prop_selector" => '//table[@class="props_list colored_char"]//tr',
            "prop1" => './/span[@itemprop="name"]',
            "prop2" => './/span[@itemprop="value"]',
            "price_order" => 0,
        ];
        #array_slice($this->productCount, 0, 11);
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}

$parser = new CParBlockStock();
$parser->processParsing();