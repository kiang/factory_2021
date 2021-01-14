<?php
$basePath = dirname(__DIR__);

// read the list inside zip
$zipFile = $basePath . '/raw/raw.zip';
$zip = new ZipArchive;
if(true !== $zip->open($zipFile)) {
    die('file extracing failed');
}
$fh = fopen('zip://' . $zipFile . '#' . $zip->getNameIndex(0), 'r');
$header = fgetcsv($fh, 2048);
$header[0] = '工廠名稱';
/*
    [0] => 工廠名稱
    [1] => 工廠登記編號
    以工廠登記編號第一碼辨識工廠資料類別
    數字：一般工廠
    T：臨時工廠
    P：納管工廠
    S：特定工廠
    [2] => 工廠設立許可案號
    [3] => 工廠地址
    [4] => 工廠市鎮鄉村里
    [5] => 工廠負責人姓名
    [6] => 統一編號
    [7] => 工廠組織型態
    [8] => 工廠設立核准日期
    [9] => 工廠登記核准日期
    [10] => 工廠登記狀態
    [11] => 產業類別
    [12] => 主要產品

    factory data: https://data.gov.tw/dataset/6569
    egis opendata api: https://egis.moea.gov.tw/OpenData/

*/
$counter = 0;
$fhPool = [];
while($line = fgetcsv($fh, 2048)) {
    $data = array_combine($header, $line);
    $data['Material'] = $data['ProductName'] = $data['WebURL'] = '';
    $data['Latitude'] = $data['Longitude'] = false;
    $pos = strpos($data['工廠名稱'], '/');
    if(false !== $pos) {
        $keyword = substr($data['工廠名稱'], 0, $pos);
    } else {
        $keyword = $data['工廠名稱'];
    }
    $targetFile = $basePath . '/egis/' . $keyword . '.json';
    
    if(!file_exists($targetFile)) {
        file_put_contents($targetFile, file_get_contents('https://egis.moea.gov.tw/MoeaEGFxData_WebAPI_Inside/InnoServe/Factory?resptype=GeoJson&keyword=' . urlencode($keyword)));
        error_log(++$counter . ':' . $keyword);
    }
    $json = json_decode(file_get_contents($targetFile), true);
    if(isset($json['features'])) {
        foreach($json['features'] AS $feature) {
            if($feature['properties']['FactoryID'] == $data['工廠登記編號']) {
                $data['Material'] = $feature['properties']['Material'];
                $data['ProductName'] = $feature['properties']['ProductName'];
                $data['WebURL'] = $feature['properties']['WebURL'];
                $data['Longitude'] = $feature['geometry']['coordinates'][0];
                $data['Latitude'] = $feature['geometry']['coordinates'][1];
                if(!empty($data['WebURL'])) {
                    if(substr($data['WebURL'], 0, 4) !== 'http') {
                        $data['WebURL'] = 'http://' . $data['WebURL'];
                    }
                }
            }
        }
    }
    $city = mb_substr($data['工廠市鎮鄉村里'], 0, 3, 'utf-8');
    if(!isset($fhPool[$city])) {
        $fhPool[$city] = fopen($basePath . '/data/' . $city . '.csv', 'w');
        fputcsv($fhPool[$city], array_keys($data));
    }
    fputcsv($fhPool[$city], $data);
}