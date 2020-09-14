<?php 
header('Content-Type: application/json');
require_once('phpmailer/PHPMailerAutoload.php');
$mail = new PHPMailer;
$mail->CharSet = 'utf-8';
$mail->SMTPDebug = false;   

function getIp() {
  $keys = [
    'HTTP_CLIENT_IP',
    'HTTP_X_FORWARDED_FOR',
    'REMOTE_ADDR',
  ];
  foreach ($keys as $key) {
    if (!empty($_SERVER[$key])) {
      $tmp_ip = explode(',', $_SERVER[$key]);
			$ip = trim(end($tmp_ip));
      if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
      }
    }
  }
}

$ip = getIp();  
require_once 'SxGeo.php';
// подключаем файл с базой данных городов
$SxGeo = new SxGeo('SxGeoCity.dat', SXGEO_BATCH | SXGEO_MEMORY);
$city = $SxGeo->get($ip);

// широта
$lat = $city['city']['lat'];
// долгота
$lon = $city['city']['lon'];
// название города на русском языке
$city_name_ru = $city['city']['name_ru'];
// название города на английском языке
$city_name_en = $city['city']['name_en'];
// ISO-код страны
$country_code = $city['country']['iso'];

// для получения информации более полной информации (включая регион) можно осуществить через метод getCityFull
$city = $SxGeo->getCityFull($ip);
// название региона на русском языке
$region_name_ru = $city['region']['name_ru'];
// название региона на английском языке
$region_name_en = $city['city']['name_en'];
// ISO-код региона
$region_name_iso = $city['city']['iso'];                               // Enable verbose debug output


  
$today = date("d.m.y");

if(isset($_REQUEST['phone'])){
	$phone = $_REQUEST['phone'];
} elseif (isset($_REQUEST['Phone'])) {
	$phone = $_REQUEST['Phone'];
} elseif (isset($_REQUEST['tel'])) {
	$phone = $_REQUEST['tel'];
}
if(isset($_REQUEST['email'])){
	$email = $_REQUEST['email'];
} elseif (isset($_REQUEST['Email'])) {
	$email = $_REQUEST['Email'];
}
if(isset($_REQUEST['name'])){
	$name = $_REQUEST['name'];
} 
if(isset($_REQUEST['text'])){
	$text = $_REQUEST['text'];
} 
if(isset($_REQUEST['href'])){
	$href = $_REQUEST['href'];
}
if($phone){
	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = 'smtp.yandex.ru';  																							// Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = 'site-noreply@amulex.ru'; // Ваш логин от почты с которой будут отправляться письма
	$mail->Password = 'BCBD86jhgVqzqrDjQK7v'; // Ваш пароль от почты с которой будут отправляться письма
	$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
	$mail->Port = 465; // TCP port to connect to / этот порт может отличаться у других провайдеров
	$mail->setFrom('site-noreply@amulex.ru'); // от кого будет уходить письмо?
	$mail->addAddress('nalog-call@amulex.ru'); 
	$mail->addAddress('amulexdata@gmail.com'); 
	$mail->addAddress('asamoylo@amulex.ru'); 

	//amulexdata@gmail.com
	$cli = '';
	// * utm_campaign_id
	// * utm_ad_id
	// * utm_criteria

	//camp_id
	$utm_campaign_id = '';
	//utm_content
	$utm_ad_id = '';
	//utm_term
	$utm_criteria = '';
	
	$cookieNamePrefix = "_lnd_";
	$utmParams = ["utm_source", "utm_medium","utm_campaign","utm_term","utm_content", "client_id"];
	$utm_str = '';
	foreach ($utmParams as $key => $value) {
		$utm = $cookieNamePrefix.$value;
		if(isset($_COOKIE[$utm]) && $_COOKIE[$utm]){
				$utm_str.= $value.": ".$_COOKIE[$utm]."<br>\n";
				if($value == 'client_id'){
					$cli = $_COOKIE[$utm];
				}
				if($value == 'utm_campaign'){
					$utm_campaign_id = $_COOKIE[$utm];
				}
				if($value == 'utm_content'){
					$utm_ad_id = $_COOKIE[$utm];
				}
				if($value == 'utm_term'){
					$utm_criteria = $_COOKIE[$utm];
				}
				
		}
	}
	
	


	$mail->isHTML(true); 
	$mail->Subject = "Лендинг Алименты - запрос платной консультации"; // Заголовок письма
	$mail->Body = "Телефон - ".$phone."\r\n<br>Почта - ".$email."\r\n<br>Имя - ".$name."\r\n<br><br>----- UTM метки ------<br>Наименование услуги: Запрос консультации с пост-оплатой<br>Город: ".$city_name_ru."<br><br>Страница с отправки: ".$href."<br><br>".$utm_str; // Текст письма// Результат


	//---- API bof ---

/**
* @var string URL получателя
*/
$recipientUrl = 'https://sale.amulex.ru/api/receiver';
/**
6
* @var string Название отправителя */
$provider = 'landing-api@amulex.ru';
/**
* @var string Секретное слово для хеширования
*/
$password = '625254eaecfe5fb0cf0848d38ac49acd';
/**
 * @var string Данные для отправки
*/

$sendData = json_encode([ 'rate_id' => '2731',
'name' => $name,
'mobile' => $phone, 
'email' => $email,
'utm_client_id' => $cli,
'utm_campaign_id' => $utm_campaign_id,
'utm_ad_id' => $utm_ad_id,
'utm_criteria' => $utm_criteria,
]);
// Если данные находятся в xml-файле
// $sendData = file_get_contents('data.xml');
//print var_dump($sendData);
/**
* Формируем данные для отправки
*/
$xmlData = array(
    'provider' => $provider,
		'datetime' => date('Y-m-d\TH:i:s'),
		'data' => base64_encode(trim($sendData)),
);
$xmlData['hash'] = trim($xmlData['provider'])
. trim($xmlData['datetime']) . trim(sha1($password))
. $xmlData['data'];
$xmlData['hash'] = sha1($xmlData['hash']);
/**
* Формируем XML
*/
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request />');
$xml->addChild('datetime', $xmlData['datetime']); 
$xml->addChild('provider', $xmlData['provider']);
$xml->addChild('data', $xmlData['data']); $xml->addChild('hash', $xmlData['hash']);
$streamOptions = array(
'http' => array(
    'method'  => 'POST',
		'header' => 'Content-type: application/xml; charset=UTF-8' . "\r\n",
		'content' => $xml->asXML(),
),
"ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
);
/**
 * Отправляем XML
*/
$file = file_get_contents($recipientUrl, false, stream_context_create($streamOptions));
$xm = new SimpleXMLElement($file);


	//print var_dump($xm->imagesRefreshUrl[0]);

	//---- API eof ---
	if($mail->send() && ($xm->status[0] === 0 || isset($xm->imagesRefreshUrl[0]))) {
	 //echo 'Message could not be sent.';
	 //echo 'Mailer Error: ' . var_dump($mail->ErrorInfo);
		$data = [
			'status' => 200,
			'success' => 'Ваша заявка отправлена'
		];
		echo json_encode($data);
	} else {
		
		$data = [
			'status' => 401,
			'error' => 'Что-то пошло не так'
		];
		echo json_encode($data);
	}
	
} else {
	$data = [
		'status' => 401,
		'error' => 'Что-то пошло не так'
	];
		echo json_encode($data);
}



