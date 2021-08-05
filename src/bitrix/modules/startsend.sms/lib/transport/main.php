<?php
namespace Startsend\Sms\Transport;

class Main{
	
	private $config;

	private $module_id = 'startsend.sms';

    // Api request methods and URLs
    public static $apiMethods = [
        'get_balance' => ['method' => 'GET', 'url' => '/api/v1/getBalance'],
        'financial_statistics' => ['method' => 'GET', 'url' => '/api/v1/financialStatistics'],
        'get_token' => ['method' => 'POST', 'url' => '/remote-api/login'],
        'register' => ['method' => 'POST', 'url' => '/remote-api/register'],
        'get_api_key' => ['method' => 'POST', 'url' => '/remote-api/getApiKey'],
        'recovery_password' => ['method' => 'GET', 'url' => '/remote-api/recovery-password'],
        'get_alphanames' => ['method' => 'GET', 'url' => '/api/v1/getAlphanames'],
        'get_alphaname_id' => ['method' => 'GET', 'url' => '/api/v1/getAlphanameId'],
        'get_alphaname_category' => ['method' => 'GET', 'url' => '/api/v1/getAlphanameCategory'],
        'create_alphaname' => ['method' => 'POST', 'url' => '/api/v1/createAlphaname'],
        'edit_password' => ['method' => 'POST', 'url' => '/api/v1/editPassword'],
        'edit_notification' => ['method' => 'POST', 'url' => '/api/v1/editNotification'],
        'requisites' => ['method' => 'POST', 'url' => '/api/v1/requisites'],
        'get_requisites' => ['method' => 'POST', 'url' => '/api/v1/getRequisites'],
        'send_quick_sms' => ['method' => 'GET', 'url' => '/api/v1/sendQuickSMS'],
        'create_cms_message' => ['method' => 'GET', 'url' => '/api/v1/createSmsMessage'],
        'send_sms' => ['method' => 'GET', 'url' => '/api/v1/sendSMS'],
        'get_messages_list' => ['method' => 'GET', 'url' => '/api/v1/getMessagesList'],
        'get_sms_by_list' => ['method' => 'GET', 'url' => '/api/v1/getSmsByList'],
        'get_sms_by_list_id' => ['method' => 'GET', 'url' => '/api/v1/getSmsByListId'],
        'send_sms_list' => ['method' => 'GET', 'url' => '/api/v1/sendSmsList'],
    ];


    /**
     * Main constructor.
     * Constructor, we get access data to the gateway
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
	function __construct() {
        $this->config->token = \Bitrix\Main\Config\Option::get($this->module_id, "token", '');
        $this->config->api = \Bitrix\Main\Config\Option::get($this->module_id, "api", '');
        $this->config->sender = \Bitrix\Main\Config\Option::get($this->module_id, "sender", '');
	}


    /**
     * Get all sender (sign) method
     * @return array|\stdClass
     */
	public function _getAllSender() {
        $gate = $this->config->api;
        $url = $gate.self::$apiMethods['get_alphanames']['url'];
        $method = self::$apiMethods['get_alphanames']['method'];
        return json_decode($this->_curl($url, $method), true);
	}

    /**
     * Send SMS
     * @param $phones
     * @param $mess
     * @param int $time
     * @param false $sender
     * @param string $addInfo
     * @return \stdClass
     */
	public function _sendSms ($phones, $mess, $time=0, $sender=false, $addInfo='1cbitrixorders_sendmanualsms') {
        $gate = $this->config->api;
        $alfaname_id = isset($this->config->sender) ? $this->config->sender : 0;

        $url = $gate.self::$apiMethods['send_quick_sms']['url'];
        $method = self::$apiMethods['send_quick_sms']['method'];
        return json_decode($this->_curl($url, $method, ['phone' => $phones, 'message' => $mess, 'alfaname_id' => $alfaname_id]), true);
	}

    /**
     * Get balance
     */
	public function _getBalance() {
        $gate = $this->config->api;
        $url = $gate.self::$apiMethods['get_balance']['url'];
        $method = self::$apiMethods['get_balance']['method'];
        return json_decode($this->_curl($url, $method), true);
	}
	
	//multiple sms
	public function _getStatusSms($arAll=array()){
		
		$arLinkSmsId = array();
		
		$xml = '<?xml version="1.0" encoding="utf-8" ?>
		<request>
		<security>
			<login value="'.$this->config->login.'" />
			<password value="'.$this->config->passw.'" />
		</security>
		<get_state>';
		foreach($arAll as $data){
			$arLinkSmsId[$data['SMSID']] = $data['ID']; //для оптимизации все обновления по ид на стороне клиента
			$xml .=	'<id_sms>'.$data['SMSID'].'</id_sms>'."\n";
		}
		$xml .= '</get_state>
		</request>';
		
		$url = 'https://'.$this->config->server.'/xml/state.php';
		
		$response = $this->openHttp($url, $xml);
		
		$allResponce = array();
		
		if(!$response){
			foreach($arLinkSmsId as $serviceId=>$baseId){
				$data = new \stdClass();
				$data->error = 'Service is not available';
				$data->error_code = '9998';
				$data->baseId = $baseId;
				$allResponce[] = $data;
			}
			return $allResponce;
		}
		
		$error = $this->checkError($response);
		
		if($error) {
			foreach($arLinkSmsId as $serviceId=>$baseId){
			$data = new \stdClass();
			$data->error = $error;
			$data->error_code = $this->chechErrorCode($error);
			$data->baseId = $baseId;
			$allResponce[] = $data;
			}
			return $allResponce;
		}
		
		$count_resp = preg_match_all('/<state id_sms=(.*) time="(.*)".*>(.*)<\/state>/Ui',$response, $matches);
		
		if($count_resp>0){
		
			foreach($matches[2] as $key=>$m){
				if($this->_checkStatus($matches[3][$key])){
					$data = new \stdClass();
					$data->last_timestamp = strtotime($matches[2][$key]);
					$data->status = $this->_checkStatus($matches[3][$key]);
					$data->baseId = $arLinkSmsId[preg_replace("/([^0-9])/is","",$matches[1][$key])];
					$allResponce[] = $data;
				}
			}
			
			return $allResponce;
		}
		
		foreach($arLinkSmsId as $serviceId=>$baseId){
			$data = new \stdClass();
			$data->error = 'Service is not available';
			$data->error_code = '9998';
			$data->baseId = $baseId;
			$allResponce[] = $data;
		}
		return $allResponce;
		
	}
	
	//old method for get status
	public function _getStatusSms_old($smsid,$phone=false) {
		
		$xml = '<?xml version="1.0" encoding="utf-8" ?>
		<request>
		<security>
			<login value="'.$this->config->login.'" />
			<password value="'.$this->config->passw.'" />
		</security>
		<get_state>
			<id_sms>'.$smsid.'</id_sms>
		</get_state>
		</request>';
		
		$url = 'https://'.$this->config->server.'/xml/state.php';
		
		$response = $this->openHttp($url, $xml);

		$data = new \stdClass();
		
		if(!$response){
			$data->error = 'Service is not available';
			$data->error_code = '9998';
			return $data;
		}
		
		$error = $this->checkError($response);
		
		if($error) {
			$data->error = $error;
			$data->error_code = $this->chechErrorCode($error);
			return $data;
		}
		
		$count_resp = preg_match_all('/<state.*time="(.*)".*>(.*)<\/state>/Ui',$response, $matches);
		
		if($count_resp>0){
			if($this->_checkStatus($matches[2][0])){
			$data->last_timestamp = strtotime($matches[1][0]);
			$data->status = $this->_checkStatus($matches[2][0]);
			return $data;
			}
		}
			$data->error = 'Service is not available';
			$data->error_code = '9998';
			return $data;
		
	}
	
	private function getConfig($params) {
		
		$c = new \stdClass();
		if(strpos($params['login'],"||")!==false){
			$arPrm = explode("||",$params['login']);
		}else{
			$arPrm = array('sms.startsend.ru',$params['login']);
		}
		$c->login = $arPrm[1];
		$c->server = $arPrm[0];
		$c->passw = $params['passw'];
		$c->sender = $params['sender'];
		
		return $c;
		
	}
	
	private function openHttp($url, $xml) {
		
		//default bitrix HttpClient
		$curl = \Bitrix\Main\Config\Option::get("startsend.sms","curl","");
		
		if($curl != 'Y'){
		
			$httpClient = new \Bitrix\Main\Web\HttpClient();
			$httpClient->setHeader('Content-Type', 'text/xml; charset=utf-8', true);
			$result = $httpClient->post($url, $xml);
			
			return $result;
			
		}
		
		if (!function_exists('curl_init')) {
		    return false;
		}

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-type: text/xml; charset=utf-8' ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_CRLF, true );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $xml );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );

		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
		
	}
	
	private function checkError($resp) {
		$count = preg_match_all('/<error>(.*)<\/error>/Ui',$resp, $matches);
		if($count>0) {
			$error = $GLOBALS['APPLICATION']->ConvertCharset($matches[1][0], 'UTF-8', SITE_CHARSET);
			return $error;
		}
		return false;
	}
	
	private function chechErrorCode($code) {
		if(toLower(SITE_CHARSET)=='windows-1251') $code = $GLOBALS['APPLICATION']->ConvertCharset($code, SITE_CHARSET, 'UTF-8');
		$code = trim($code);
		if(strpos($code,'логин')!==false) return 2;
		if(strpos($code,'XML')!==false) return 1;
		if(strpos($code,'POST')!==false) return 1;
		
		if(strpos($code,'логин или')!==false) return 2; //'Неправильный логин или пароль'
		if(strpos($code,'формат XML')!==false) return 1; //'Неправильный формат XML документа'
		if(strpos($code,'аккаунт заблокирован')!==false) return 2; //'Ваш аккаунт заблокирован'
		if(strpos($code,'POST данные')!==false) return 1; //'POST данные отсутствуют'
		if(strpos($code,'закончились SMS')!==false) return 3; //'У нас закончились SMS. Для разрешения проблемы свяжитесь с менеджером.'
		if(strpos($code,'Закончились SMS')!==false) return 3; //'Закончились SMS.'
		if(strpos($code,'Аккаунт заблокирован')!==false) return 2; //'Аккаунт заблокирован.'
		if(strpos($code,'Укажите номер')!==false) return 1; //'Укажите номер телефона.'
		if(strpos($code,'стоп-листе')!==false) return 8; //'Номер телефона присутствует в стоп-листе.'
		if(strpos($code,'направление закрыто')!==false) return 6; //'Данное направление закрыто для вас.'
		if(strpos($code,'направление закрыто')!==false) return 6; //'Данное направление закрыто.'
		if(strpos($code,'SMS отклонен')!==false) return 6; //'Текст SMS отклонен модератором.'
		if(strpos($code,'Нет отправителя')!==false) return 6; //'Нет отправителя.'
		if(strpos($code,'символов для цифровых')!==false) return 6; //'Отправитель не должен превышать 15 символов для цифровых номеров и 11 символов для буквенно-числовых.'
		if(strpos($code,'телефона должен')!==false) return 7; //'Номер телефона должен быть меньше 15 символов.'
		if(strpos($code,'текста сообщения')!==false) return 1; //'Нет текста сообщения.'
		if(strpos($code,'Нет ссылки')!==false) return 1; //'Нет ссылки.'
		if(strpos($code,'название контакта')!==false) return 1; //'Укажите название контакта и хотя бы один параметр для визитной карточки.'
		if(strpos($code,'отправителя нет')!==false) return 6; //'Такого отправителя нет.'
		if(strpos($code,'не прошел модерацию')!==false) return 6; //'Отправитель не прошел модерацию.'
		
		return 9999;
	
	}
	
	private function _checkStatus($code) {
	
		if($code=='send') return 3;
		if($code=='not_deliver') return 7;
		if($code=='expired') return 5;
		if($code=='deliver') return 4;
		if($code=='partly_deliver') return false;
		return false;
		
	}


    /**
     * Curl request for service
     * @param $url
     * @param $method
     * @param array $post_data
     */
	private function _curl($url, $method, $post_data = []) {
        $post_data = !is_null($post_data) && !empty($post_data) ?
            array_merge($post_data, ['token' => $this->config->token]) :
            ['token' => $this->config->token];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_USERAGENT, "OpenCart CMS");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
            curl_setopt($curl, CURLOPT_URL, $url);
        } else if ($method === 'GET') {
            curl_setopt($curl, CURLOPT_POST, false);
            curl_setopt($curl, CURLOPT_URL, $url . '?' . http_build_query($post_data));
        }

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $curl_error =
                "StartSend cURL Error " . curl_errno($curl) . ": " . curl_error($curl);
        } else {
            $curl_error = "";
        }
        if ($curl_error) {
            AddMessage2Log($curl_error, "startsend.sms");
            return ["error" => $curl_error];
        }
        curl_close($curl);
        return $response;
    }
	
}
?>