<?php
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
set_time_limit(300);
ini_set("max_execution_time", "300");


class RequestData
{
    private $data = [];

    public function __construct()
    {
//        $_POST['token'] = 'wMjqF8UoQkvE';
//        $_POST['id'] = '185512052';
        if (empty($_POST['token']) or $_POST['token'] !== 'wMjqF8UoQkvE') {
            exit('Token Error');
        }
        if (empty($_POST['id'])) {
            exit('ID not found');
        }
        // exit();
        $this->loadReview_v2($_POST['id']);
        header('Content-type: application/json');
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE);
        exit();
        $reviews = $this->getReviewFromProduct($_POST['id']);
        header('Content-type: application/json');
        echo json_encode($reviews, JSON_UNESCAPED_UNICODE);
    }

    public function loadReview_v2($skuID, $nextPage = null) 
    {
        $urlParam = "/product/$skuID/?layout_container=reviewshelfpaginator";
        if (!empty($nextPage)) $urlParam = $nextPage;
        $url = "https://www.ozon.ru/api/entrypoint-api.bx/page/json/v2?url=";
        $fullURL = $url . urlencode($urlParam);
        $page = $this->loadPage($fullURL, true, true);
        $dataPage = json_decode($page, true);
        $states = $dataPage["widgetStates"];
        $nextPage = $dataPage["nextPage"] ?? null;
        if (!empty($states)) foreach ($states as $key => $value) {
            if (strpos($key, "webListReviews") !== false) {
                $dataRev = json_decode($value, true);
                if (!empty($dataRev["reviews"])) foreach ($dataRev["reviews"] as $valueRev) {
                    $this->data[] = $valueRev;
                }
            }
        }
        sleep(1);
        // var_dump($nextPage);
        if (!empty($nextPage)) return $this->loadReview_v2($skuID, $nextPage);
    }

    public function getReviewFromProduct($skuID): array
    {
        $urlViewPage = "https://www.ozon.ru/context/detail/id/" . $skuID . "/";
        $this->loadPage($urlViewPage, false, true);
        return $this->loadReviews($skuID);
    }

    private function loadReviews($skuID): array
    {
        $reviews = [];
        $this->loadReview($skuID, $reviews, 1, $dataPage);
        if (!empty($dataPage['state']['paging'])) {
            $paging = $dataPage['state']['paging'];
            if ($paging['total'] > $paging['perPage']) {
                $page = 2;
                for ($i = $paging['perPage']; $i < $paging['total']; $i = $i + $paging['perPage']) {
                    $this->loadReview($skuID, $reviews, $page);
                    $page++;
                }
            }
        }
        return $reviews;
    }

    private function loadReview($skuID, &$data, $page = 1, &$dataPage = [])
    {
        $dataPage = $this->loadReviewRequest($skuID, $page);
        if (empty($dataPage)) return;
        $this->prepareReview($dataPage, $data);
    }

    private function prepareReview($arrayData, &$data)
    {
        if (empty($arrayData['state']['reviews'])) return;
        foreach ($arrayData['state']['reviews'] as $item) $data[] = $item;
    }

    private function loadReviewRequest($skuID, $numPage = 1)
    {
        $dataRequest = $this->getDataEncodeRequest($skuID);
        $url = "https://www.ozon.ru/api/composer-api.bx/widget/json/v2";
        $loadReviewData = $this->loadPost($url, [
            'asyncData' => $dataRequest,
            'componentName' => 'listReviewsDesktop',
            'extraBody' => true,
            'url' => "/context/detail/id/" . $skuID . "/?page=" . $numPage . "&sort=usefulness_desc"
        ]);
        return json_decode($loadReviewData, true);
    }

    private function getDataEncodeRequest($skuID)
    {
        $scriptFile = $this->loadDataReview($skuID);
        $re = '/{"component":"listReviewsDesktop","params":"{(((?!\{")\S)+)"asyncData":"(((?!\")\S)+)\",/m';
        preg_match_all($re, $scriptFile, $matches, PREG_SET_ORDER);
        return $matches[0][3] ?? '';
    }

    private function loadDataReview($skuID)
    {
        $url = "https://www.ozon.ru/api/composer-api.bx/page/json/spa";
        $url .= "?" . http_build_query(['url' => '/context/detail/id/' . $skuID . '/?layout_container=pdpReviews&layout_page_index=2']);
        var_dump($url);
        return $this->loadPage($url, true);
    }

    private function loadPage($url, $readCookie = false, $writeCookie = false)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        if ($writeCookie) curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookie_ozon_review.txt');
        if ($readCookie) curl_setopt($curl, CURLOPT_COOKIEFILE, 'cookie_ozon_review.txt');
        curl_setopt($curl, CURLOPT_ENCODING , "gzip");
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
        	'Host: ' . "www.ozon.ru",
        	"Accept: */*",
        	"accept-encoding: gzip",
        	"cache-control: no-cache",
        	"pragma: no-cache",
            'Content-Type: application/json',
        	"User-Agent: PostmanRuntime/7.28.0",
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    private function loadPost($url, $postData)
    {
        // sleep(1);
        $curl = curl_init($url);
        $dataRequest = json_encode($postData);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataRequest);
        //curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookie_ozon_review.txt');
        //curl_setopt($curl, CURLOPT_COOKIEFILE, 'cookie_ozon_review.txt');
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_ENCODING , "gzip");
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Accept: */*",
            'Host: ' . "www.ozon.ru",
            "Accept-Encoding: gzip",
            'Content-Type: application/json',
        	"User-Agent: PostmanRuntime/7.28.0",
            'Content-Length: ' . strlen($dataRequest),
            "Connection: keep-alive"
        ]);
        $response = curl_exec($curl);
        if(curl_errno($curl) === 28) return $this->loadPost($url, $postData);
        curl_close($curl);
        return $response;
    }
}

new RequestData();