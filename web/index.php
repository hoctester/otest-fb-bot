<?php

require('../vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;

$app = new Silex\Application();

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));

$app->before(function (Request $request) use($bot) {
    // TODO validation
});

$app->get('/callback', function (Request $request) use ($app) {
    $response = "";
    if ($request->query->get('hub_verify_token') === getenv('FACEBOOK_PAGE_VERIFY_TOKEN')) {
        $response = $request->query->get('hub_challenge');
    }

    return $response;
});

$app->post('/callback', function (Request $request) use ($app) {
    // Let's hack from here!
    $body = json_decode($request->getContent(), true);
    $client = new Client(['base_uri' => 'https://graph.facebook.com/v2.6/']);

	$myPath = str_replace("index.php", "", __FILE__);

	$data01 = "data desu\r\n\r\n";
	foreach ($body['entry'][0]['changes'][0]['value'] as $key => $val) {
		$data01 .= $key . ":" . $val . "\r\n";
	}
	$data .= multi_implode($body, ":");
	
	$txt = date("Y-m-d H:i:s") . "  " . $text . "\r\n";
	$fp = fopen($myPath . "test.txt", "a+");
	fwrite($fp, $data01);
	fclose($fp);
    
    foreach ($body['entry'] as $obj) {
        $app['monolog']->addInfo(sprintf('obj: %s', json_encode($obj)));

        foreach ($obj['messaging'] as $m) {
            $app['monolog']->addInfo(sprintf('messaging: %s', json_encode($m)));
            $from = $m['sender']['id'];
            $text = $m['message']['text'];

            if ($text) {
                $path = sprintf('me/messages?access_token=%s', getenv('FACEBOOK_PAGE_ACCESS_TOKEN'));
                $json = [
                    'recipient' => [
                        'id' => $from, 
                    ],
                    'message' => [
                        'text' => sprintf('%sじゃーない！', $text), 
                    ],
                ];
                $client->request('POST', $path, ['json' => $json]);
            }
        }

    }

    return 0;
});

$app->run();

function multi_implode($array, $glue) {
    $ret = '';

    foreach ($array as $item) {
        if (is_array($item)) {
            $ret .= multi_implode($item, $glue) . $glue;
        } else {
            $ret .= $item . $glue;
        }
    }

    $ret = substr($ret, 0, 0-strlen($glue));

    return $ret;
}
