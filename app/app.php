<?php

$data = null;
foreach (explode("\n", file_get_contents($argv[1])) as $row) {

    if (empty($row)) break;

    preg_match_all('/:"([^"]+)"/', $row, $matches);

    $value = array_map(function($match) {
        return trim($match, '"');
    }, $matches[1]);

    try {
        $isEu = checkIfBinIsEu($value[0]);
        $isEuFlag = ($isEu == 'yes');
        $finalAmount = convertAmount(floatval($value[1]), $value[2], $isEuFlag, $data);
        echo $finalAmount;
        echo "\n";
    } catch (Exception $e) {
        die($e->getMessage());
    }
}

function checkIfBinIsEu(string $bin): bool {
    $url = 'https://lookup.binlist.net/' . $bin;
    $binResults = file_get_contents($url);

    if ($binResults === false) {
        throw new Exception('Error fetching BIN details from the API.');
    }

    $data = json_decode($binResults);

    if ($data === null || !isset($data->country->alpha2)) {
        throw new Exception('Invalid API response.');
    }

    return isEu($data->country->alpha2);
}

function convertAmount(float $amount, string $currency, bool $isEu, $data): float {
    if ($currency == 'EUR') {
        $amntFixed = $amount;
    } else {
        $rate = getExchangeRate($currency, $data);
        if ($rate == 0) {
            throw new Exception('Invalid exchange rate.');
        }
        $amntFixed = $amount / $rate;
    }

    $feeRate = $isEu ? 0.01 : 0.02;
    return $amntFixed * $feeRate;
}

function getExchangeRate(string $currency, $data): float {
    if ($data === null) {
        $apiUrl = 'http://api.exchangerate.host/latest?access_key=162e9be5cf4dbd74de78334c32b9d770';
        $data = @json_decode(file_get_contents($apiUrl), true);
    }

    if ($data === null || !isset($data['rates'][$currency])) {
        throw new Exception('Error fetching or parsing exchange rate data.');
    }

    return $data['rates'][$currency];
}

function isEu($c) {
    $result = false;
    switch($c) {
        case 'AT':
        case 'BE':
        case 'BG':
        case 'CY':
        case 'CZ':
        case 'DE':
        case 'DK':
        case 'EE':
        case 'ES':
        case 'FI':
        case 'FR':
        case 'GR':
        case 'HR':
        case 'HU':
        case 'IE':
        case 'IT':
        case 'LT':
        case 'LU':
        case 'LV':
        case 'MT':
        case 'NL':
        case 'PO':
        case 'PT':
        case 'RO':
        case 'SE':
        case 'SI':
        case 'SK':
            $result = 'yes';
            return $result;
        default:
            $result = 'no';
    }
    return $result;
}
