<?php

if (!function_exists('getallheaders')) 
{ 
	function getallheaders() 
	{ 
		$headers = []; 
		foreach ($_SERVER as $name => $value) 
		{ 
			if (substr($name, 0, 5) == 'HTTP_') 
			{ 
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
			} 
		} 
		return $headers; 
	} 
} 

function callApi($url, $options = [])
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return string instead of write to stdout
	$headers = [];
	if (isset($options['headers'])) {
		$headers = $options['headers'];
	}
	if (isset($options['body'])) {
		$body = $options['body'];
		if (is_array($body)) {
			$body = http_build_query($body);
		}
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		$headers['Content-Type'] = 'application/x-www-form-urlencoded';
	}
	if (isset($options['json'])) {
		$json = $options['json'];
		if (is_array($json)) {
			$json = json_encode($json, JSON_UNESCAPED_UNICODE);
		}
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		$headers['Content-Type'] = 'application/json';
	}
	$httpheader = [];
	foreach ($headers as $k => $v) {
		$httpheader []= "$k: $v";
	}

	curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	$result = curl_exec($ch);
	$info = curl_getinfo($ch);
	$info['response'] = $result;
	curl_close($ch);

	return $info;
}



class Request {
	private $signature;
	private $body;
	private $json;
	private $accessToken;
	private $channelSecret;
	private $logger;

	public function __construct($accessToken, $channelSecret, $logger = null) {
		$this->accessToken = $accessToken;
		$this->channelSecret = $channelSecret;

		$this->logger = $logger;


		$headers = getallheaders();
		if (isset($headers['X-Line-Signature'])) {
			$this->signature = $headers['X-Line-Signature'];
		}
		else {
			if ($this->logger) {
				$this->logger->info("X-Line-Signature is not set");
			}
		}
		$this->body = file_get_contents("php://input");
	}

	public function verify() {
		$hash = hash_hmac('sha256', $this->body, $this->channelSecret, true);
		if ($this->signature !== base64_encode($hash)) {
			return false;
		}
		$this->json = json_decode($this->body, true);
		return true;
	}

	public function getBody() {
		return $this->body;
	}

	public function getMessage() {
		if ($this->json['events'][0]["type"] !== "message") {
			if ($this->logger) {
				$this->logger->info("getMessage called for non message event");
			}
			return "";
		}
		return $this->json['events'][0]["message"]["text"];
	}

	private function buildHeader() {
		return [
			'Content-Type' => 'application/json',
		       	'Authorization' => "Bearer {{$this->accessToken}}"
		];
	}

	public function reply($obj) {
		$json = [
				'replyToken' => $this->json['events'][0]['replyToken'],
				'messages' => $obj,
			];
		return callApi("https://api.line.me/v2/bot/message/reply", [
			'headers' => $this->buildHeader(),
			'json' => $json
		]);
	}

	public function text($text) {
		return [
			[
				'type'=>'text',
				'text'=>$text,
			]
		];
	}

	public function image($url, $thumburl) {
	    return [
		[
		    'type' => 'image',
		    'originalContentUrl' => $url,
		    'previewImageUrl' => $thumburl,
		]
	    ];
	}

	public function push($to, $obj) {
		$json = [
				'to' => $to,
				'messages' => $obj,
		];
		return callApi("https://api.line.me/v2/bot/message/push", [
			'headers' => $this->buildHeader(),
			'json' => $json
		]);
	}

}

class PoorLogger {
	private $logfile;
	public function __construct($logfile) {
		$this->logfile = $logfile;
	}

	public function info($message, $context = []) {
		$this->log("info", $message, $context);
	}


	public function log($level, $message, $context = []) {
		$file = new SplFileObject($this->logfile, "a");
		$template = "%level[%date %time] %mes\n";

		$out = str_replace("%mes", $message, $template);
		$out = str_replace("%level", $level, $out);
		$out = str_replace("%date", date("Y-m-d"), $out);
		$out = str_replace("%time", date("H:i:s"), $out);

		$file->fwrite($out);
	}
}


function loadData() {
    if (! file_exists('../data.json')) {
	return [
	    "dates" => [],
	    "templates" => [],
	];
    }
    $jsonStr = file_get_contents('../data.json');
    return json_decode($jsonStr, true);
}
function saveData($data) {
    file_put_contents('../data.json', json_encode($data, JSON_UNESCAPED_UNICODE));
}

function getTemplates() {
    $templates = loadData()["templates"];
    if (count($templates) == 0) {
	for ($i = 0; $i < 8; $i++) {
	    $templates[getDayName($i)] = getDayName($i);
	}
    }
    return $templates;
}

function getDayName($i) {
    return [
	"日曜",
	"月曜",
	"火曜",
	"水曜",
	"木曜",
	"金曜",
	"土曜",
	"祝日",
    ][$i];
}

function save($data) {
    $saveData = loadData();

    foreach ($data["dates"] as $d) {
	$saveData["dates"][$d["date"]] = $d["message"]; 
    }
    foreach ($data["templates"] as $d) {
	$saveData["templates"][$d["day"]] = $d["message"];
    }
    saveData($saveData);
    return true;
}

function templates() {
    $ret = [];

    foreach (getTemplates() as $day => $msg) {
	$ret []= [
	    "day" => $day,
	    "message" => $msg
	];
    }

    return $ret;
}

function messages() {
    $result = callApi("https://holidays-jp.github.io/api/v1/date.json");
    $holidayStrs = array_keys(json_decode($result['response'], true));

    $daysOfWeek = [];
    for ($i = 0; $i < 7; $i++) {
	$daysOfWeek []= new DateTime("+$i day");
    }

    $data = loadData()['dates'];
    $templates = getTemplates();
    $ret = [];
    foreach ($daysOfWeek as $day) {
	$key = $day->format("Y-m-d (D)");
	if (array_key_exists($key, $data) && !is_null($data[$key])) {
	    $ret []= [
		"date" => $key,
		"message" => $data[$key]
	    ];
	}
	else {
	    $dateKey = $day->format("Y-m-d");
	    if (array_search($dateKey, $holidayStrs) !== false) {
		$ret []= [
		    "date" => $key,
		    "message" => $templates["祝日"],
		];
	    }
	    else {
		$ret []= [
		    "date" => $key,
		    "message" => $templates[getDayName($day->format("w"))]
		];
	    }
	}
    }

    return $ret;
}


function genSecureToken() {
	$rand = bin2hex(random_bytes(10));
	file_put_contents(CONSTANTS::SECURETOKEN, $rand);
	return $rand;
}

function checkSecureToken($tok) {
	return trim(file_get_contents(CONSTANTS::SECURETOKEN)) === trim($tok);
}

function parseMes($mes) {
    $pat = "/@\w+\.(jpg|png|gif)/";
    preg_match_all($pat, $mes, $imgs);
    $newmes = preg_replace($pat, "", $mes);

    return [
	'mes' => $newmes,
	'imgs' => $imgs[0],
    ];
}

function makeImageUrl($image) {
    return "https://theoldmoon0602.tk/kujokaren/img.php?file=$image";
}
