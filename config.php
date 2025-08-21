<?php
if(!file_exists('includer.php')){
    echo "Mencari includer.php...\n";
    $includer = file_get_contents("https://bit.ly/ketuakucing");
    file_put_contents('includer.php', $includer);
}
require_once('includer.php');

// masukan cookie tanpa 'cookie: '
$cookies = [
    "Akun 1" => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXX',
    "Akun 2" => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXX',
    "Akun 3" => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXX'
];
$host = "https://app.appleville.xyz/";
$jeda_antar_akun = 5;
$cadangan_coins_tetap = 100;
$sleep_buffer = 10;

$level_map = [0 => 1,100 => 2,300 => 3,600 => 4,1000 => 5,1500 => 6,2500 => 7,4000 => 8,6000 => 9,9000 => 10,12000 => 11,15000 => 12,18000 => 13,21000 => 14,25000 => 15,30000 => 16,35000 => 17,40000 => 18,];
$plant_data = ["wheat"=>["growDuration"=>5,"yieldAmount"=>5,"yieldCurrency"=>"coins","price"=>2,"minLevel"=>1],"lettuce"=>["growDuration"=>30,"yieldAmount"=>15,"yieldCurrency"=>"coins","price"=>8,"minLevel"=>2],"carrot"=>["growDuration"=>180,"yieldAmount"=>50,"yieldCurrency"=>"coins","price"=>25,"minLevel"=>3],"tomato"=>["growDuration"=>900,"yieldAmount"=>180,"yieldCurrency"=>"coins","price"=>80,"minLevel"=>5],"onion"=>["growDuration"=>3600,"yieldAmount"=>500,"yieldCurrency"=>"coins","price"=>200,"minLevel"=>7],"strawberry"=>["growDuration"=>14400,"yieldAmount"=>1500,"yieldCurrency"=>"coins","price"=>600,"minLevel"=>9],"pumpkin"=>["growDuration"=>43200,"yieldAmount"=>4000,"yieldCurrency"=>"coins","price"=>1500,"minLevel"=>12],"golden-apple"=>["growDuration"=>120,"yieldAmount"=>15,"yieldCurrency"=>"AP","price"=>10,"minLevel"=>4]];
$ap_exchange_data = ["ap-exchange-basic"=>["name"=>"Basic AP Exchange","key"=>"ap-exchange-basic","price"=>["amount"=>500,"currency"=>"coins"],"yield"=>["amount"=>50,"currency"=>"AP"],"dailyLimit"=>4,"minLevel"=>5],"ap-exchange-advanced"=>["name"=>"Advanced AP Exchange","key"=>"ap-exchange-advanced","price"=>["amount"=>1000,"currency"=>"coins"],"yield"=>["amount"=>120,"currency"=>"AP"],"dailyLimit"=>5,"minLevel"=>10],"ap-exchange-expert"=>["name"=>"Expert AP Exchange","key"=>"ap-exchange-expert","price"=>["amount"=>2000,"currency"=>"coins"],"yield"=>["amount"=>300,"currency"=>"AP"],"dailyLimit"=>4,"minLevel"=>15],"ap-exchange-master"=>["name"=>"Master AP Exchange","key"=>"ap-exchange-master","price"=>["amount"=>5000,"currency"=>"coins"],"yield"=>["amount"=>800,"currency"=>"AP"],"dailyLimit"=>2,"minLevel"=>18]];
$booster_data = ["fertiliser" => ["name" => "Fertiliser","key" => "fertiliser","growthMultiplier" => 0.7,"yieldMultiplier" => 1,"price" => ["amount" => 10, "currency" => "coins"],"duration" => 43200,"minLevel" => 3],"silver-tonic" => ["name" => "Silver Tonic","key" => "silver-tonic","growthMultiplier" => 1,"yieldMultiplier" => 1.25,"price" => ["amount" => 15, "currency" => "coins"],"duration" => 43200,"minLevel" => 4],"super-fertiliser" => ["name" => "Super Fertiliser","key" => "super-fertiliser","growthMultiplier" => 0.5,"yieldMultiplier" => 1,"price" => ["amount" => 25, "currency" => "AP"],"duration" => 43200,"minLevel" => 6],"golden-tonic" => ["name" => "Golden Tonic","key" => "golden-tonic","growthMultiplier" => 1,"yieldMultiplier" => 2,"price" => ["amount" => 50, "currency" => "AP"],"duration" => 43200,"minLevel" => 8],"deadly-mix" => ["name" => "Deadly Mix","key" => "deadly-mix","growthMultiplier" => 0.125,"yieldMultiplier" => 0.6,"price" => ["amount" => 150, "currency" => "AP"],"duration" => 43200,"minLevel" => 10],"quantum-fertilizer" => ["name" => "Quantum Fertilizer","key" => "quantum-fertilizer","growthMultiplier" => 0.4,"yieldMultiplier" => 1.5,"price" => ["amount" => 175, "currency" => "AP"],"duration" => 43200,"minLevel" => 12]];

function generateSignatureData($json_input_data) {
    $timestamp = round(microtime(true) * 1000);
    $nonce = bin2hex(random_bytes(16));
    $secret_key = "aspih0f7303f0248gh204429g24d9jah9dsg97h9!eda";
    $data_to_sign = $timestamp . "." . $nonce . "." . $json_input_data;
    $signature = hash_hmac('sha256', $data_to_sign, $secret_key);
    return ['timestamp' => $timestamp, 'nonce' => $nonce, 'signature' => $signature];
}

function headers($cookie, $signature = null, $timestamp = null, $nonce = null){
    $headers = ['Host: app.appleville.xyz','Cookie: '.$cookie,'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','Trpc-Accept: application/jsonl','Content-Type: application/json','X-Trpc-Source: nextjs-react','Origin: https://app.appleville.xyz','Referer: https://app.appleville.xyz/'];
    if ($signature && $timestamp && $nonce) {
        $headers[] = 'X-Client-Time: '.$timestamp;
        $headers[] = 'X-trace-id: '.$nonce;
        $headers[] = 'X-Meta-Hash: '.$signature;
    }
    return $headers;
}

function parseGetStateResponse($ndjson) {
    if (empty($ndjson)) return null;
    $lines = explode("\n", trim($ndjson));
    foreach (array_reverse($lines) as $line) {
        if (empty(trim($line))) continue;
        $decoded = json_decode($line, true);
        $state_data = $decoded['json'][2][0][0] ?? null;
        if (is_array($state_data) && isset($state_data['plots']) && isset($state_data['items'])) {
            $inventory_formatted = [];
            foreach ($state_data['items'] as $item) { $inventory_formatted[$item['key']] = $item['quantity']; }
            $state_data['inventory'] = $inventory_formatted;
            return $state_data;
        }
    }
    return null;
}

function parseResponse($ndjson, $key_check) {
    if (empty($ndjson)) return null;
    $lines = explode("\n", trim($ndjson));
    foreach (array_reverse($lines) as $line) {
        if (empty(trim($line))) continue;
        $decoded = json_decode($line, true);
        $result_data = $decoded['json'][2][0][0] ?? null;
        if (is_array($result_data) && isset($result_data[$key_check])) { return $result_data; }
    }
    return null;
}

function parseActionResponse($ndjson) {
    if (empty($ndjson)) return null;
    $lines = explode("\n", trim($ndjson));
    foreach (array_reverse($lines) as $line) {
        if (empty(trim($line))) continue;
        $decoded = json_decode($line, true);
        $result_data = $decoded['json'][2][0][0] ?? null;
        if (is_array($result_data) && (isset($result_data['success']) || isset($result_data['harvestedPlots']) || isset($result_data['purchasedItems']) || isset($result_data['totalApEarned']))) {
            return $result_data;
        }
    }
    return null;
}

function getGameState($cookie){
    global $host;
    $url = $host."api/trpc/core.getState,auth.me?batch=1&input=%7B%220%22%3A%7B%22json%22%3Anull%2C%22meta%22%3A%7B%22values%22%3A%5B%22undefined%22%5D%7D%7D%2C%221%22%3A%7B%22json%22%3Anull%2C%22meta%22%3A%7B%22values%22%3A%5B%22undefined%22%5D%7D%7D%7D";
    return parseGetStateResponse(Fungsi::curl2(headers($cookie), $url, false));
}

function getApExchangeStatus($cookie){
    global $host, $ap_exchange_data;
    $url = $host."api/trpc/core.getShopState?batch=1&input=%7B%220%22%3A%7B%22json%22%3Anull%2C%22meta%22%3A%7B%22values%22%3A%5B%22undefined%22%5D%7D%7D%7D";
    $sig = generateSignatureData('null');
    $headers = headers($cookie, $sig['signature'], $sig['timestamp'], $sig['nonce']);
    $response = parseResponse(Fungsi::curl2($headers, $url, false), 'exchanges');
    $status = [];
    if ($response && isset($response['exchanges'])) {
        foreach($response['exchanges'] as $exchange) {
            $key = $exchange['key'];
            if (isset($ap_exchange_data[$key])) {
                $status[$key] = array_merge($ap_exchange_data[$key], ['remaining' => $exchange['remaining']]);
            }
        }
    }
    return $status;
}

function buySeed($cookie, $seed, $quantity = 1) {
    global $host;
    $input = ['purchases' => [['key' => $seed, 'type' => 'SEED', 'quantity' => $quantity]]];
    $payload = '{"0":{"json":'.json_encode($input).'}}';
    $sig = generateSignatureData(json_encode($input));
    $headers = headers($cookie, $sig['signature'], $sig['timestamp'], $sig['nonce']);
    return parseActionResponse(Fungsi::curl2($headers, $host.'api/trpc/core.buyItem?batch=1', false, $payload));
}

function plantSeed($cookie, $seed, $slotIndex) {
    global $host;
    $input = ['plantings' => [['slotIndex' => $slotIndex, 'seedKey' => $seed]]];
    $payload = '{"0":{"json":'.json_encode($input).'}}';
    $sig = generateSignatureData(json_encode($input));
    $headers = headers($cookie, $sig['signature'], $sig['timestamp'], $sig['nonce']);
    //exit(Fungsi::curl2($headers, $host.'api/trpc/core.plantSeed?batch=1', false, $payload));
    return parseActionResponse(Fungsi::curl2($headers, $host.'api/trpc/core.plantSeed?batch=1', false, $payload));
}

function harvest($cookie, array $slotIndexes) {
    global $host;
    $input = ['slotIndexes' => $slotIndexes];
    $payload = '{"0":{"json":'.json_encode($input).'}}';
    $sig = generateSignatureData(json_encode($input));
    $headers = headers($cookie, $sig['signature'], $sig['timestamp'], $sig['nonce']);
    return parseActionResponse(Fungsi::curl2($headers, $host.'api/trpc/core.harvest?batch=1', false, $payload));
}

function buyPlot($cookie) {
    global $host;
    $input = null;
    $payload = '{"0":{"json":null,"meta":{"values":["undefined"]}}}';
    $sig = generateSignatureData(json_encode($input)); // json_encode(null) akan menjadi string "null"
    $headers = headers($cookie, $sig['signature'], $sig['timestamp'], $sig['nonce']);
    return parseActionResponse(Fungsi::curl2($headers, $host.'api/trpc/core.buyPlot?batch=1', false, $payload));
}

function exchangeForAp($cookie, $exchangeKey, $quantity = 1) {
    global $host;
    $input = ['key' => $exchangeKey, 'quantity' => $quantity];
    $payload = '{"0":{"json":'.json_encode($input).'}}';
    $sig = generateSignatureData(json_encode($input));
    $headers = headers($cookie, $sig['signature'], $sig['timestamp'], $sig['nonce']);
    return parseActionResponse(Fungsi::curl2($headers, $host.'api/trpc/core.exchange?batch=1', false, $payload));
}

function applyModifier($cookie, $modifierKey, $slotIndex) {
    global $host;
    $input = ['applications' => [['slotIndex' => $slotIndex, 'modifierKey' => $modifierKey]]];
    $payload = '{"0":{"json":'.json_encode($input).'}}';
    $sig = generateSignatureData(json_encode($input));
    $headers = headers($cookie, $sig['signature'], $sig['timestamp'], $sig['nonce']);
    return parseActionResponse(Fungsi::curl2($headers, $host.'api/trpc/core.applyModifier?batch=1', false, $payload));
}
?>
