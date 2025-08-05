<?php


const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParAtomss extends CParMain {
    static $name_parser = array(
        "Atomss" => "АтомСпецСплав"
    );
    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://atomss.ru/";
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
            "category_selector" => '//ul[@id="left-menu"]/li/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($url, $data);

        #$this->productCount = array_slice($this->productCount, 0, 1);
        $this->logMessage("Найдено " . count($this->productCount) . " категорий.");


        $this->logMessage("Получение ссылок на подкатегории...");
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[@class="catalog"]/div/a/@href',
            "absolute_link" => false
        ];
        $underCat = $this->gettingUrls($this->productCount, $data);

        #$underCat = array_slice($underCat, 0, 1);
        $this->logMessage("Найдено " . count($underCat) . " подкатегорий.");


        $this->logMessage("Получение ссылок на подподкатегории...");
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => '//div[@class="catalog"]/div/a/@href',
            "absolute_link" => false
        ];
        $this->productCount = $this->gettingUrls($underCat, $data, true);

        #$this->productCount = array_slice($this->productCount, 1, count($this->productCount)-1);
        $this->logMessage("Найдено " . count($this->productCount) . " подподкатегорий.");

        $categories = [];
        foreach ($this->productCount as $cat => $product) {
            if (count($product) > 0) $categories = array_merge($categories, $product);
            else $categories[] = $cat;
        }
        foreach ($underCat as $ca) {
            if (!in_array($ca, array_keys($this->productCount))) {
                $categories[] = $ca;
            }
        }

        $this->logMessage("Пагинация ссылок...");
        $data = [
            "title" => "Пагинация",
            "log" => "пагинации",
            "paginate_selector" => '//div[@class="pages"]/div/a',
            "last_button_id" => 1,
            "url_argument" => "/page/",
            "html_argument" => "nodeValue",

        ];
        $this->productCount = $this->gettingUrls($categories, $data, true);
        #$this->productCount = array_slice($this->productCount, 0, 2);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы с товарами.");


        $data = [
            "title" => "Ссылки на товары",
            "log" => "ссылок на товары",
            "title_selector" => ['//div[@class="specification"]/div/div/a'],
            "absolute_link" => false,
            "big_data" => true
        ];

        $this->productCount = $this->gettingUrls($this->productCount, $data);
        $this->logMessage("Получено " . count($this->productCount) . " ссылок на страницы товаров.");


        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//div[@class="bread-crumb"]/a',
            "crumb_begin" => 2,
            "crumb_end" => 2,
            "title_selector" => '//h1',
            "price_selector" => '',
            "unit_selector" => '',

            "image_selector" => '//div[@class="img"]/img',
            "image_html_argument" => "src",
            "absolute_link" => false,
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);
    }
}
$parser = new CParAtomss();
$parser->processParsing();