<?php
namespace Startsend\Sms;


class Handlers {
	
	//обертка над стандартной службой сообщений в битриксе
	public static function onGetSmsSenders(){
		
		return array(new Bxsms());
		
	}
	
	public static function onBeforeSendSms(\Bitrix\Main\Event $event){
		
		//$messageOb = $event->getParameter("message");
		
	}
	
}