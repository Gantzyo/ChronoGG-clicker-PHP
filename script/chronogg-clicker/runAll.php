<?php
require_once '../lib/Requests.php';
require_once './commons.php';

ignore_user_abort(true);

date_default_timezone_set('Europe/Madrid');

$MAIN_URL = 'https://chrono.gg';
$POST_URL = 'https://api.chrono.gg/quest/spin';
$BALANCE_URL = 'https://api.chrono.gg/account/coins';
$ALREADY_CLICKED_CODE = 420;
$UNAUTHORIZED_CODE = 401;
$USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';
$GLOBAL_HEADERS = array(
    'User-Agent' => $USER_AGENT,
    'Pragma' => 'no-cache',
    'Origin' => $MAIN_URL,
    'Accept-Encoding' => 'gzip, deflate, br',
    'Accept' => 'application/json',
    'Cache-Control' => 'no-cache',
    // 'Connection' => 'keep-alive', // This header causes some issues when run online, just remove it
    'Referer' => $MAIN_URL,
    'Authorization' => ''
);
$TIMEOUT_SECONDS = 30;
$CONNECT_TIMEOUT_SECONDS = 30;
$LOG = '';

function addLogMessage($logMessage) {
    // global $LOG;
    // $LOG .= '['.date("Y.m.d H:i:s.v").'] '.$logMessage.'<br/>';
    // file_put_contents('./log_'.date("Y.m.d").'.log', '['.date("Y.m.d H:i:s.v").'] '.$logMessage."\n", FILE_APPEND);
}

function booleanToString($booleanVar) {
    return $booleanVar ? 'Yes' : 'No';
}

function getColumnString($value, $bold) {
    if($bold) {
        return '<td><b>'.$value.'</b></td>';
    }
    return '<td>'.$value.'</td>';
}

// Init
Requests::register_autoloader();
$accounts = json_decode(file_get_contents($ACCOUNTS_FILE), true);

$options = array('timeout' => $TIMEOUT_SECONDS, 'connect_timeout' => $CONNECT_TIMEOUT_SECONDS);

$results = array(
    "lastExecution" => date("D, d M Y H:i:s O", time()),
    "errors" => 0,
    "results" => array(),
    'elapsedTime' => 0
);

$starttime = microtime(true);

// Iterate through accounts
foreach($accounts as $account) {

    addLogMessage('Account: '.$account['account']);

    $headers = $GLOBAL_HEADERS;
    $headers['Authorization'] = $account['token'];
    
    $result = array(
        'account' => $account['account'],
        'balance' => 0,
        'successful' => false,
        'execution' => date("D, d M Y H:i:s O", time()),
        'reason' => ''
    );

    // 1. Click coin
    $response = Requests::get($POST_URL, $headers, $options);
    $result['successful'] = $response->success;

    // 2. Get response code and decide if there's an error
    if(!$result['successful']) {
        $reason = 'Unknown';
        if($response->status_code == $ALREADY_CLICKED_CODE)
        {
            $reason = 'Already clicked';
        } elseif ($response->status_code == $UNAUTHORIZED_CODE) {
            $reason = 'Unauthorized (Cookie expired?)';
        } elseif ($response->is_redirect()) {
            $reason = 'Redirect (broken script?)';
        }
        $result['reason'] = $response->status_code . ' - ' . $reason;
        $results['errors']++;
        addLogMessage('Error: '.$result['reason']);
    } else {
        addLogMessage('Coin clicked');
    }
    // 3. Get balance
    $response = Requests::get($BALANCE_URL, $headers, $options);
    $result['balance'] = json_decode($response->body, true)['balance'];
    addLogMessage('Balance: '.$result['balance']);

    // Push result into $results
    $results['results'][] = $result;
}
$results['elapsedTime'] = microtime(true) - $starttime;
// Save results
file_put_contents($RESULTS_FILE, json_encode($results, JSON_PRETTY_PRINT));

// Save as RSS
$xml = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n";
$xml .= '<rss version="2.0">' . "\n";
$xml .= '<channel>' . "\n";
$xml .= '<title>Chrono.gg clicker log</title>' . "\n";
$xml .= '<link>https://chrono.gg</link>' . "\n";
$xml .= '<description>A simple RSS working as a log of Chrono.gg clicker</description>' . "\n";
$xml .= '<item>' . "\n";
$xml .= '<title>Executed at ' . $results['lastExecution'] . '</title>' . "\n";
$xml .= '<link>https://chrono.gg</link>' . "\n";
$xml .= '<description>' . "\n";
$xml .= '<![CDATA[';
$xml .= '<h1>';
if($results['errors'] == 0) {
    $xml .= 'Successful' . "\n";
} else {
    $xml .= $results['errors'] . ' Errors';
}
$xml .= '</h1>' . "\n";
$xml .= '<table border=1>' . "\n";
$xml .= '<tr style="text-align:center;"><th colspan=5>Results</th></tr>' . "\n";
$xml .= '<tr style="text-align:center;"><th>Account</th><th>Balance</th><th>Clicked</th><th>Reason</th><th>Execution</th></tr>' . "\n";
foreach($results['results'] as $result) {
    $xml .= '<tr>';
    // Add error style if anything went wrong
    $xml .= getColumnString($result['account'], !$result['successful']);
    $xml .= getColumnString($result['balance'], !$result['successful']);
    $xml .= getColumnString(booleanToString($result['successful']), !$result['successful']);
    $xml .= getColumnString($result['reason'], !$result['successful']);
    $xml .= getColumnString($result['execution'], !$result['successful']);

    $xml .= '</tr>' . "\n";
}
$xml .= '</table>' . "\n";
if(isset($results['elapsedTime'])) {
    $xml .= '<p>Elapsed time: <b>' . $results['elapsedTime'] . '</b> seconds</p>' . "\n";
}
// $xml .= '<p>'.$LOG.'</p>';
$xml .= ']]>';
$xml .= '</description>' . "\n";
$xml .= '<pubDate>' . $results['lastExecution'] . '</pubDate>' . "\n";
$xml .= '<guid>' . $results['lastExecution'] . '</guid>' . "\n";
$xml .= '</item>' . "\n";
$xml .= '</channel>' . "\n";
$xml .= '</rss>';
file_put_contents($RSS_FILE, $xml);

?>