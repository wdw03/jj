<?php
// تنظیمات پیشرفته و بهینه
$requestsPerUser = 1000;
$requestDelay = 0.02;
$cooldownTime = 1; 
$maxRetries = 5;
$blockId = 4853;
$green = "32";
$red = "31"; 
$yellow = "33";
$blue = "34";
$totalPoints = 0;
$firstRun = true;

// IP Ranges for different devices
$deviceIPs = [
    'samsung' => ['102.128.0.0/16', '103.24.116.0/22', '104.16.0.0/12'],
    'xiaomi' => ['106.11.0.0/16', '111.13.0.0/16', '114.67.0.0/16'],
    'vivo' => ['116.128.128.0/17', '117.136.0.0/13', '120.204.0.0/14'],
    'oneplus' => ['122.224.0.0/12', '123.125.0.0/16', '124.160.0.0/13'],
    'oppo' => ['125.32.0.0/12', '125.64.0.0/11', '125.96.0.0/15'],
    'google' => ['172.217.0.0/16', '172.253.0.0/16', '173.194.0.0/16'],
    'asus' => ['140.112.0.0/12', '140.113.0.0/16', '140.114.0.0/16']
];

// Enhanced device configurations
$devices = [
    [
        'model' => 'SM-G998B',
        'brand' => 'samsung',
        'android' => '13',
        'build' => 'TP1A.220624.014',
        'ip_range' => $deviceIPs['samsung']
    ],
    [
        'model' => 'M2102J20SG',
        'brand' => 'xiaomi',
        'android' => '12',
        'build' => 'SKQ1.211006.001',
        'ip_range' => $deviceIPs['xiaomi']
    ],
    [
        'model' => 'V2024',
        'brand' => 'vivo',
        'android' => '11',
        'build' => 'RP1A.200720.012',
        'ip_range' => $deviceIPs['vivo']
    ],
    [
        'model' => 'OnePlus9Pro',
        'brand' => 'oneplus',
        'android' => '12',
        'build' => 'SKQ1.210216.001',
        'ip_range' => $deviceIPs['oneplus']
    ],
    [
        'model' => 'PGEM10',
        'brand' => 'oppo',
        'android' => '13',
        'build' => 'SP1A.210812.016',
        'ip_range' => $deviceIPs['oppo']
    ],
    [
        'model' => 'Pixel 6 Pro',
        'brand' => 'google',
        'android' => '13',
        'build' => 'TQ2A.230505.002',
        'ip_range' => $deviceIPs['google']
    ],
    [
        'model' => 'ROG Phone 6',
        'brand' => 'asus',
        'android' => '12',
        'build' => 'SKQ1.220406.001',
        'ip_range' => $deviceIPs['asus']
    ]
];

function generateIP($ipRange) {
    $range = $ipRange[array_rand($ipRange)];
    list($subnet, $bits) = explode('/', $range);
    $ip = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    $start = $ip & $mask;
    $end = $start | (~$mask & 0xFFFFFFFF);
    return long2ip(rand($start, $end));
}

function generateDeviceInfo() {
    global $devices;
    $device = $devices[array_rand($devices)];
    
    return [
        'model' => $device['model'],
        'brand' => $device['brand'],
        'android' => $device['android'],
        'build' => $device['build'],
        'ip' => generateIP($device['ip_range']),
        'user_agent' => "Mozilla/5.0 (Linux; Android {$device['android']}; {$device['model']}) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/" . rand(90,120) . ".0." . rand(1000,9999) . ".". rand(100,999) . " Mobile Safari/537.36"
    ];
}

function makeApiRequest($userId, $tgId) {
    global $blockId;
    
    $deviceInfo = generateDeviceInfo();
    
    $params = [
        'blockId' => $blockId,
        'tg_id' => $tgId,
        'tg_platform' => 'android',
        'platform' => "Android {$deviceInfo['android']}",
        'language' => 'en',
        'chat_type' => 'sender',
        'chat_instance' => generateChatInstance(),
        'top_domain' => 'app.notpx.app',
        'device_id' => md5($deviceInfo['model'] . time()),
        '_' => time() . rand(100, 999),
        'unique' => uniqid('px_', true)
    ];

    $headers = [
        'Host: api.adsgram.ai',
        'Connection: keep-alive',
        'Cache-Control: no-store, must-revalidate',
        'Pragma: no-cache',
        "User-Agent: {$deviceInfo['user_agent']}",
        'Accept: application/json, text/plain, */*',
        'Origin: https://app.notpx.app',
        'X-Requested-With: org.telegram.messenger',
        'Sec-Fetch-Site: cross-site',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Dest: empty',
        'Referer: https://app.notpx.app/',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: gzip, deflate, br',
        "X-Device-Model: {$deviceInfo['model']}", 
        "X-Device-Brand: {$deviceInfo['brand']}",
        "X-Device-Android: {$deviceInfo['android']}",
        "X-Device-Build: {$deviceInfo['build']}",
        "X-Forwarded-For: {$deviceInfo['ip']}"
    ];

    $url = "https://api.adsgram.ai/adv?" . http_build_query($params);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING => '',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_TCP_FASTOPEN => 1,
        CURLOPT_TCP_NODELAY => 1,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$response, $httpCode, $headers];
}

function clearScreen() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        system('cls');
    } else {
        system('clear');
    }
}

function generateChatInstance() {
    return strval(rand(10000000000000, 99999999999999));
}

function processReward($response, $userId) {
    global $totalPoints, $userPoints, $green;
    
    $data = json_decode($response, true);
    if ($data && isset($data['points']) && $data['points'] > 0) {
        $points = $data['points'];
        $totalPoints += $points;
        $userPoints[$userId] += $points;
        echo printColored("[ SUCCESS ] ++ $userId +$points PX\n", $green);
        return true;
    }
    return false;
}

function extractReward($response) {
    $data = json_decode($response, true);
    if ($data && isset($data['banner']['trackings'])) {
        foreach ($data['banner']['trackings'] as $tracking) {
            if ($tracking['name'] === 'reward') {
                return $tracking['value'];
            }
        }
    }
    return null;
}

function printColored($text, $color) {
    return "\033[" . $color . "m" . $text . "\033[0m";
}

$usersFile = 'users.json';
if (!file_exists($usersFile)) {
    echo printColored("Error: No users found! Please add users first.\n", $red);
    exit;
}

$users = json_decode(file_get_contents($usersFile), true);
if (!$users) {
    echo printColored("Error: Could not parse users.json!\n", $red);
    exit;
}

$userPoints = array_fill_keys(array_keys($users), 0);

while (true) {
    clearScreen();
    
    if (!$firstRun) {
        foreach ($users as $userId => $userData) {
            echo printColored("---> $userId +{$userPoints[$userId]} PX\n", $green);
        }
        echo printColored("Total PX Earned [ +$totalPoints ]\n\n", $green);
    }

    foreach ($users as $userId => $userData) {
        $tgId = $userData['tg_id'];
        
        for ($requestCount = 1; $requestCount <= $requestsPerUser; $requestCount++) {
            echo printColored("[ INFO ] Request $requestCount/$requestsPerUser\n", $yellow);
            echo printColored("[ PROCESS ] Processing TG ID: $userId\n", $blue);
            
            $retryCount = 0;
            $success = false;
            
            while ($retryCount < $maxRetries && !$success) {
                list($response, $httpCode, $reqHeaders) = makeApiRequest($userId, $tgId);
                
                if ($httpCode === 200) {
                    if (processReward($response, $userId)) {
                        $success = true;
                    } else {
                        $reward = extractReward($response);
                        if ($reward) {
                            echo printColored("[ SUCCESS ] ++ Injected to $userId\n", $green);
                            
                            $ch = curl_init();
                            curl_setopt_array($ch, [
                                CURLOPT_URL => $reward,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_HTTPHEADER => $reqHeaders,
                                CURLOPT_SSL_VERIFYPEER => false,
                                CURLOPT_TIMEOUT => 5,
                                CURLOPT_TCP_FASTOPEN => 1
                            ]);
                            
                            $response = curl_exec($ch);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                            if ($httpCode === 200) {
                                $totalPoints += 16;
                                $userPoints[$userId] += 16;
                                echo printColored("[ SUCCESS ] ++ $userId +16 PX\n", $green);
                                $success = true;
                            }
                        }
                    }
                }
                
                if (!$success) {
                    $retryCount++;
                    usleep(100000);
                }
            }
            
            usleep($requestDelay * 1000000);
        }
    }

    for ($i = $cooldownTime; $i > 0; $i--) {
        echo "\r-----> Next round in $i seconds...";
        sleep(1);
    }
    echo "\n";
    
    $firstRun = false;
}
?>
