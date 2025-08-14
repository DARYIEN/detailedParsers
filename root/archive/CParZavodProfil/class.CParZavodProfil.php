<?php


const ROOT = __DIR__;
include_once(ROOT . '/../../utility/class.CParMain.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
class CParZavodProfil extends CParMain {
    public static $name_parser = [
        "zavodprofil" => "Завод Профиль"
    ];

    function __construct() {
        $this->dual_cost = false;
        $this->decimal = false;
        $this->site_link = "https://zavod-profil.ru";
        $this->author = "Никита";
        $this->batches = 30;
    }

    public function processParsing() {
        $startTime = microtime(true);

        // --- 1. Категории ---
        $this->logMessage("Получение ссылок на категории...");
        $url = [$this->site_link . "/catalog/"];
        $data = [
            "title" => "Категории",
            "log" => "ссылок на категории",
            "category_selector" => "//li[contains(@class, 'cat-list__item')]/a/@href",
            "absolute_link" => false
        ];
        $categories = $this->gettingUrls($url, $data);
        $this->logMessage("Найдено " . count($categories) . " категорий.");

        // --- 2. Пагинация ---
        $this->logMessage("Поиск страниц пагинации...");
        $data = [
            "title" => "Пагинация",
            "log" => "страниц пагинации",
            "paginate_selector" => "//div[contains(@class, 'pagination')]/a",
            "last_button_id" => 1,
            "url_argument" => "?PAGEN_1=",
            "html_argument" => "href"
        ];
        $allPages = $this->gettingUrls($categories, $data, true);

        // --- 3. Ссылки на товары (shortparse = true) ---
        $this->logMessage("Парсинг товаров со страниц...");
        $data = [
            "title" => "Ссылки на товары",
            "log" => "товаров",
            "title_selector" => [
                "//div[contains(@class, 'product-item')]//a[@class='item-btn']"
            ],
            "big_data" => true
        ];
        $this->productCount = $this->gettingUrls($allPages, $data);

        $this->logMessage("Начало парсинга товаров...");
        $data = [
            "title" => "Товары одноразовый",
            "log" => "cсылок на товары",
            "crumb_selector" => '//ul[@class="breadcrumb container"]/li/a/span',
            "crumb_begin" => 2,
            "crumb_end" => 2,
            "title_selector" => '//h1[@class="view__title"]',
            "price_selector" => '//span[@class="account-item__num assets"]',
            "unit_selector" => '',

            "image_selector" => '//ul[@class="splide__list"]/li/img',
            "image_html_argument" => "src",
            "absolute_link" => false,

            "prop_type" => "mono",
            "prop_selector" => '//div[@data-value="properties"]//span',


            "description_selector" => '//div[@data-value="description"]',
        ];
        $this->batchSize = 4000;
        $productsData = $this->gettingUrls($this->productCount, $data, true);
        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;

        $this->parseSave($productsData);

        $endTime = microtime(true);
        $this->parse_time = $endTime - $startTime;


    }
}
$parser = new CParZavodProfil();
$parser->processParsing();