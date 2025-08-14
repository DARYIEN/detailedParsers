<?php
const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParTrackMetall extends CParMain {
    static $name_parser = array(
        "TrackMetall" => "Трек металл"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://trek-metall.ru";
        $this->author = "Никита";
        $this->batches = 15;
    }

    function processParsing() {

        $startTime = microtime(true);

        $this->logMessage("Получение ссылок на категории...");
        $url = ["https://trek-metall.ru"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[@id="d_category_menu_list"]/span//span[@class="col"]//a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        $this->productCount = array_slice($this->productCount, 0, 850);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//ul[@class="pagination justify-content-center m-0"]/li/a',
            "last_button_id" => 1,
            "inc" => '',
            "url_argument" => "?page=",
            "html_argument" => "href=",


        ];
        $this->productCount = $this->gettingUrls($this->productCount, $data, true);
        #$this->productCount = array_slice($this->productCount, 700, 350);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="grid row form-row row-cols-2 row-cols-md-3 row-cols-lg-2 row-cols-xl-3 row-cols-xxl-4"]/div//div[@class="product-name"]/a'],
            "absolute_link" => true,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ol[@class="breadcrumb p-0 mb-3 bg-transparent"]/li/a/span',
            "crumb_begin" => 0,
            "crumb_end" => 0,
            "title_selector" => '//h1[@class="h2 my-4"]',
            "price_selector" => '',
            "unit_selector" => '',

            "one_image_selector" => '',
            "image_selector" => '//div[@class="item d-flex justify-content-center"]/a',
            "image_html_argument" => "href",
            "absolute_link" => false,

            "prop_type" => "dual",
            "prop_selector" => '//div[@id="tab-specification"]//tr',
            "prop1" => './/td[1]',
            "prop2" => './/td[2]',

            "description_selector" => '',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParTrackMetall();
$parser->processParsing();