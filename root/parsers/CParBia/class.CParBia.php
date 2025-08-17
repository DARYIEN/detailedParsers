<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParBia extends CParMain {
    static $name_parser = array(
        "Bia" => "-"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://www.bia.su";
        $this->author = "Никита";
        $this->batches = 1;
        $this->slower_parse = true;
    }

    function processParsing() {

        $startTime = microtime(true);

        $this->productCount = ["https://www.bia.su/avtoremont_/oborudovanie_svarochnoe/", "https://www.bia.su/avtoremont_/kraskopulty_i_aerografy/", "https://www.bia.su/avtoremont_/kraskopulty_i_aerografy/"];


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//ul[@class="products-pagination fright"]/li/a',
            "last_button_id" => 2,
            "url_argument" => "?iNumPage=",
            "html_argument" => "nodeValue",

        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");

        $base_selector = '//ul[@class="products-body"]/li';
        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => [$base_selector . '//div[@class="name"]/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        #$this->productCount = array_slice($this->productCount, 0, 4);

        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//nav[@class="path"]/span[2]/span/a',
            "crumb_begin" => 1,
            "crumb_end" => 0,
            "title_selector" => '//h1[@class="product-title"]',
            "price_selector" => '//div[@class="prices"]//meta[@itemprop="price"]',
            "price_html_argument" => "content",
            "unit_selector" => '',

            "image_selector" => '//table[@class="product-gallery-big-img"]//img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//ul[@class="product-list"]/li',
            "prop1" => './/span[1] | .//span/span/em',
            "prop2" => './/span[2]',

            "description_selector" => '//div[@class="product-tab-content"] | //div[@itemprop="description"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParBia();
$parser->processParsing();