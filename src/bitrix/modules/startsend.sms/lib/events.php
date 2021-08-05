<?php
namespace Startsend\Sms;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Events {
	
	public static $cache;
	
	public static function getList(){
		
		$events = array(
			"ISMS_NEWORDER" => array(
				"BX_EVENT" => array(
					array('sale','OnSaleOrderSaved','startsend.sms','\Startsend\Sms\Events','OnSaleOrderEntitySaved','new'),
				),
				"FIELD" => array(
					"HTML" => array('\Startsend\Sms\Fields','newOrderHtml'),
					"BEFORE_SAVE" => array('\Startsend\Sms\Fields','newOrderSave')
				),
				"NAME" => Loc::getMessage("STARTSEND_SMS_EVENTCODE_ISMS_NEWORDER")
			),
			"ISMS_STATUSUPDATE" => array(
				"BX_EVENT" => array(
					array('sale','OnSaleOrderSaved','startsend.sms','\Startsend\Sms\Events','OnSaleOrderEntitySaved','new'),
				),
				"FIELD" => array(
					"HTML" => array('\Startsend\Sms\Fields','statusOrderHtml'),
					"BEFORE_SAVE" => array('\Startsend\Sms\Fields','statusOrderSave')
				),
				"NAME" => Loc::getMessage("STARTSEND_SMS_EVENTCODE_ISMS_STATUSUPDATE")
			),
			"ISMS_PAYED" => array(
				"BX_EVENT" => array(
					array('sale','OnSaleOrderSaved','startsend.sms','\Startsend\Sms\Events','OnSaleOrderEntitySaved','new'),
				),
				"FIELD" => array(
					"HTML" => array('\Startsend\Sms\Fields','payedOrderHtml'),
					"BEFORE_SAVE" => array('\Startsend\Sms\Fields','payedOrderSave')
				),
				"NAME" => Loc::getMessage("STARTSEND_SMS_EVENTCODE_ISMS_PAYED")
			),
			"ISMS_BXEVENT" => array(
				"BX_EVENT" => array(
					array('main','OnBeforeEventSend','startsend.sms','\Startsend\Sms\Events','OnBeforeEventSend','old'),
				),
				"FIELD" => array(
					"HTML" => array('\Startsend\Sms\Fields','eventSendHtml'),
					"BEFORE_SAVE" => array('\Startsend\Sms\Fields','eventSendSave')
				),
				"NAME" => Loc::getMessage("STARTSEND_SMS_EVENTCODE_ISMS_BXEVENT")
			)
		);
		
		$event = new \Bitrix\Main\Event("startsend.sms", "OnAfterEventsAdd",array("EVENTS"=>$events));
		$event->send();
		   if ($event->getResults()){
			  foreach($event->getResults() as $evenResult){
				 if($evenResult->getResultType() == \Bitrix\Main\EventResult::SUCCESS){
				 $params = $evenResult->getParameters();
				 if(is_array($params['EVENTS'])) $events = $params['EVENTS'];
			  }
		   }
		}
		
		return $events;
		
	}
	
	public static function OnSaleOrderEntitySaved(\Bitrix\Main\Event $event){
		
		$order = $event->getParameter("ENTITY");
		
		if($order){
		
			$orderId = $order->getId();
			
			$oldValues = $event->getParameter("VALUES");
			$isNew = $order->isNew();
			
			$arOrderFields = \Bitrix\Sale\Internals\OrderTable::getList(
				array(
					'select' => array(
						"ID",
						"DATE_INSERT_FORMAT",
						"DATE_INSERT",
						"LID",
						"ACCOUNT_NUMBER",
						"TRACKING_NUMBER",
						"PAY_SYSTEM_ID",
						"DELIVERY_ID",
						"PERSON_TYPE_ID",
						"USER_ID",
						"PAYED",
						"STATUS_ID",
						"PRICE_DELIVERY",
						"ALLOW_DELIVERY",
						/*"PRICE_PAYMENT",*/
						"PRICE",
						"CURRENCY",
						"DISCOUNT_VALUE",
						"TAX_VALUE",
						"SUM_PAID",
						"USER_DESCRIPTION",
						"AFFILIATE_ID",
						//"BASKET_PRICE_TOTAL",
						"STATUS_NAME"=>"STATUS.NAME",
						"USER_EMAIL"=>"USER.EMAIL",
						"USER_NAME"=>"USER.NAME",
						"USER_PERSONAL_PHONE"=>"USER.PERSONAL_PHONE",
						"USER_PERSONAL_MOBILE"=>"USER.PERSONAL_MOBILE",
						"USER_PERSONAL_CITY"=>"USER.PERSONAL_CITY",
						"USER_WORK_PHONE"=>"USER.WORK_PHONE",
						"USER_PERSONAL_GENDER"=>"USER.PERSONAL_GENDER"
						//"*"
						),
					'filter' => array("ID"=>$orderId)
				)
			)->fetch();
			$arOrderFields['DATE_INSERT'] = $arOrderFields['DATE_INSERT']->toString(new \Bitrix\Main\Context\Culture(array("FORMAT_DATETIME" => "DD.MM.YYYY")));
			
			$dbProperty = \CSaleOrderProps::GetList(array("SORT" => "ASC"));
			$arMakros = array();
			
			foreach($arOrderFields as $prop_code=>$val){
				$arMakros['#'.$prop_code.'#'] = $val;
			}
			
			while($arProp = $dbProperty->Fetch()) {
				$arMakros['#PROPERTY_'.$arProp['CODE'].'#'] = '';
			}
			
			$dbOrderProps = \Bitrix\Sale\Internals\OrderPropsValueTable::getList(array(
				'select'=> array("*"), 
				'filter' => array("ORDER_ID"=>$orderId)
			)
			);
					
			while($arOrderProps = $dbOrderProps->fetch()) {
				$arMakros['#PROPERTY_'.$arOrderProps['CODE'].'#'] = $arOrderProps['VALUE'];
			}
			
			if ($propertyCollection = $order->getPropertyCollection())
			{
				$propVal = $propertyCollection->getArray();
				foreach($propVal['properties'] as $v){
					$arMakros['#PROPERTY_'.$v['CODE'].'#'] = $v['VALUE'][0];
				}
			}
			
			$arDelivery =  array();
			if($arOrderFields['DELIVERY_ID']) $arDelivery = \Bitrix\Sale\Delivery\Services\Table::getRowById($arOrderFields['DELIVERY_ID']); //NAME
			if(is_array($arDelivery) && isset($arDelivery["NAME"])){
				$delivery = $arDelivery["NAME"];
			}else{
				$delivery = "";
			}
			
			$arPayment = array();
			if($arOrderFields['PAY_SYSTEM_ID']) $arPayment = \Bitrix\Sale\Internals\PaySystemActionTable::getRowById($arOrderFields['PAY_SYSTEM_ID']); //NAME
			if(is_array($arPayment) && isset($arPayment["NAME"])){
				$payment = $arPayment["NAME"];
			}else{
				if($arOrderFields['PAY_SYSTEM_ID']) $arPayment = \Bitrix\Sale\Internals\PaySystemTable::getRowById($arOrderFields['PAY_SYSTEM_ID']); //NAME
				if(is_array($arPayment) && isset($arPayment["NAME"])){
					$payment = $arPayment["NAME"];
				}else{
					$payment = "";
				}
			}
			
			$arMakros['#ORDER_SUM#'] = $arOrderFields['PRICE']-$arOrderFields['PRICE_DELIVERY'];
			$arMakros['#DELIVERY_NAME#'] = $delivery;
			$arMakros['#PAYMENT_NAME#'] = $payment;
			
			\Bitrix\Main\Loader::includeModule('currency');
			\Bitrix\Main\Loader::includeModule('catalog');
			$arMakros['#ORDER_SUM_FORMAT#'] = \CCurrencyLang::CurrencyFormat($arMakros['#ORDER_SUM#'],$arOrderFields['CURRENCY']);
			$arMakros['#SUM_PAID_FORMAT#'] = \CCurrencyLang::CurrencyFormat($arMakros['#SUM_PAID#'],$arOrderFields['CURRENCY']);
			$arMakros['#PRICE_DELIVERY_FORMAT#'] = \CCurrencyLang::CurrencyFormat($arMakros['#PRICE_DELIVERY#'],$arOrderFields['CURRENCY']);
			//$arMakros['#PRICE_PAYMENT_FORMAT#'] = \CCurrencyLang::CurrencyFormat($arMakros['#PRICE_PAYMENT#'],$arOrderFields['CURRENCY']);
			$arMakros['#PRICE_FORMAT#'] = \CCurrencyLang::CurrencyFormat($arMakros['#PRICE#'],$arOrderFields['CURRENCY']);
			$arMakros['#DISCOUNT_VALUE_FORMAT#'] = \CCurrencyLang::CurrencyFormat($arMakros['#DISCOUNT_VALUE#'],$arOrderFields['CURRENCY']);
			$arMakros['#TAX_VALUE_FORMAT#'] = \CCurrencyLang::CurrencyFormat($arMakros['#TAX_VALUE#'],$arOrderFields['CURRENCY']);
			$arMakros['#SUM_PAID_FORMAT#'] = \CCurrencyLang::CurrencyFormat($arMakros['#SUM_PAID#'],$arOrderFields['CURRENCY']);
			
			$arMakros['#EVENT_NAME#'] = 'ISMS_ORDER_'.$arOrderFields['ID'];
			
			if($isNew){
				
				//ISMS_NEWORDER новый заказ 
				$res = \Startsend\Sms\EventlistTable::getList(
					array(
						'select' => array("*"),
						'filter' => array("=EVENT"=>'ISMS_NEWORDER',"ACTIVE"=>"Y","SITE_ID"=>$arOrderFields['LID'])
					)
				);
				while($arData = $res->fetch()){
					$arData['PARAMS'] = unserialize($arData['PARAMS']);
					if($arData['PARAMS']['PHONE']){
						$arData['TEMPLATE'] = self::compileTemplate($arData['TEMPLATE'], $arMakros);
						$phoneAr = str_replace(array_keys($arMakros), $arMakros, $arData['PARAMS']['PHONE']);
						
						$phoneAr = preg_replace("/([^0-9,])/is","",$phoneAr);
						$phoneAr = explode(",",$phoneAr);
						$sender = ($arData['SENDER']) ? $arData['SENDER'] : "";
						
						foreach($phoneAr as $phone){
							
							if(strlen($phone)>7){
								
								
								if(trim($arData['TEMPLATE'])){
									$smsOb = new \Startsend\Sms\Sender();
									$smsOb->event = $arMakros['#EVENT_NAME#'];
									$smsOb->eventName = Loc::getMessage("STARTSEND_SMS_EVENTCODE_ISMS_NEWORDER");
									$smsOb->sendSms($phone, $arData['TEMPLATE'],0,$sender);
									
									$smsOb->event = null;
									$smsOb->eventName = null;
								}
								
								break;
							}
						}
					}
				}
				
			}
			
			//ISMS_STATUSUPDATE смена статуса заказа
			if($oldValues['STATUS_ID'] && ($oldValues['STATUS_ID'] != $arOrderFields['STATUS_ID'])){
				$res = \Startsend\Sms\EventlistTable::getList(
					array(
						'select' => array("*"),
						'filter' => array("=EVENT"=>'ISMS_STATUSUPDATE',"ACTIVE"=>"Y","SITE_ID"=>$arOrderFields['LID'])
					)
				);
				while($arData = $res->fetch()){
					$arData['PARAMS'] = unserialize($arData['PARAMS']);
					
					$right = false;
					
					if($arData['PARAMS']['STATUS_FROM'] == 'ALL') {
						$right = true;
					}else{
						if($arData['PARAMS']['STATUS_FROM'] == $oldValues['STATUS_ID']) $right = true;
					}
					if($right){
						$right = false;
						if($arData['PARAMS']['STATUS_TO'] == 'ALL') {
							$right = true;
						}else{
							if($arData['PARAMS']['STATUS_TO'] == $arOrderFields['STATUS_ID']) $right = true;
						}
					}
					
					if($arData['PARAMS']['PHONE'] && $right){
						$arData['TEMPLATE'] = self::compileTemplate($arData['TEMPLATE'], $arMakros);
						$phoneAr = str_replace(array_keys($arMakros), $arMakros, $arData['PARAMS']['PHONE']);
						$phoneAr = preg_replace("/([^0-9,])/is","",$phoneAr);
						$phoneAr = explode(",",$phoneAr);
						$sender = ($arData['SENDER']) ? $arData['SENDER'] : "";
						
						foreach($phoneAr as $phone){
							if(strlen($phone)>7){
								
								
								if(trim($arData['TEMPLATE'])){
									$smsOb = new \Startsend\Sms\Sender();
									$smsOb->event = $arMakros['#EVENT_NAME#'];
									$smsOb->eventName = Loc::getMessage("STARTSEND_SMS_EVENTCODE_ISMS_STATUSUPDATE");
									$smsOb->sendSms($phone, $arData['TEMPLATE'],0,$sender);
									
									$smsOb->event = null;
									$smsOb->eventName = null;
								}
								
								break;
							}
						}
					}
					
				}
			}
			
			//ISMS_PAYED - оплата заказа
			if($oldValues['PAYED'] && ($oldValues['PAYED'] != $arOrderFields['PAYED'])){
				$res = \Startsend\Sms\EventlistTable::getList(
					array(
						'select' => array("*"),
						'filter' => array("=EVENT"=>'ISMS_PAYED',"ACTIVE"=>"Y","SITE_ID"=>$arOrderFields['LID'])
					)
				);
				while($arData = $res->fetch()){
					$arData['PARAMS'] = unserialize($arData['PARAMS']);
					
					$right = false;
					if($arOrderFields['PAYED'] == $arData['PARAMS']['PAYED']) $right = true;
					
					if($arData['PARAMS']['PHONE'] && $right){
						$arData['TEMPLATE'] = self::compileTemplate($arData['TEMPLATE'], $arMakros);
						$phoneAr = str_replace(array_keys($arMakros), $arMakros, $arData['PARAMS']['PHONE']);
						$phoneAr = preg_replace("/([^0-9,])/is","",$phoneAr);
						$phoneAr = explode(",",$phoneAr);
						$sender = ($arData['SENDER']) ? $arData['SENDER'] : "";
						
						foreach($phoneAr as $phone){
							if(strlen($phone)>7){
								
								
								if(trim($arData['TEMPLATE'])){
									$smsOb = new \Startsend\Sms\Sender();
									$smsOb->event = $arMakros['#EVENT_NAME#'];
									$smsOb->eventName = Loc::getMessage("STARTSEND_SMS_EVENTCODE_ISMS_PAYED");
									$smsOb->sendSms($phone, $arData['TEMPLATE'],0,$sender);
									
									$smsOb->event = null;
									$smsOb->eventName = null;
								}
								
								break;
							}
						}
					}
					
				}
			}
			
			
		}
		
	}
	
	public static function executePhp($template,&$macros,&$arParams)
	{
		$result = eval('use \Bitrix\Main\Mail\EventMessageThemeCompiler; ob_start();?>' . $template . '<? return ob_get_clean();');
		return $result;
	}
	
	public static function compileTemplate($template, &$macros){
		$arParams = array();
		foreach($macros as $k=>$v){
			$arParams[str_replace("#","",$k)] = $v;
		}
		$template = str_replace(array_keys($macros), $macros, $template);
		$template = self::executePhp($template,$macros,$arParams);
		foreach($arParams as $k=>$v){
			$macros['#'.$k.'#'] = $v;
		}
		//$template = preg_replace('/(\#[^#]+\#)/is',"",$template);
		return $template;
	}
	
	//вывод таба в админке
	public static function OnAdminTabControlBegin(&$form){
		
		$module_id = "startsend.sms";
		$MODULE_RIGHT_ = $GLOBALS["APPLICATION"]->GetGroupRight($module_id);
		
		if( ($MODULE_RIGHT_ >= "R") && (($GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/sale_order_view.php") || ($GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/sale_order_edit.php")))
		{
			$orderId = intval($_REQUEST["ID"]);
			
			if($orderId) {
			
				$GLOBALS["APPLICATION"]->SetAdditionalCSS("/bitrix/css/startsend.sms/style.css");
				
				$res = \Startsend\Sms\ListTable::getList(array(
					'select' => array("*"),
					'filter' => array("=EVENT"=>'ISMS_ORDER_'.$orderId),
					'order' => array("TIME_SEND"=>"ASC")
				));
				$html = '<tr class="heading" id="tr_DETAIL_TEXT_LABEL">
					<td colspan="2">'.Loc::getMessage("STARTSEND_SMS_EVENTCODE_TABCONTROL_NAME").'</td>
				</tr>';
				$html .= '<tr><td colspan="2"><table style="width:100%;border:1px solid #000000;">';
				
				while ($arData = $res->fetch()){
					$html .= '<tr>
					<td style="border:1px solid #000000;">'.$arData['SENDER'].' -> <br>'.$arData['PHONE'].'
					</td>
					<td style="border:1px solid #000000;">'.$arData['TIME_SEND']->toString(new \Bitrix\Main\Context\Culture(array("FORMAT_DATETIME" => "DD.MM.YYYY HH:MI"))).' -> <br>
					<font class="status_'.$arData['STATUS'].'">'.Loc::getMessage("STARTSEND_SMS_LIST_STATUS_".$arData['STATUS']).'</font>
					</td>
					<td style="border:1px solid #000000;">'.$arData['MESS'].'
					</td>
					</tr>';
				}
				$html .= '</table>
				<br/>
				<a href="/bitrix/admin/startsend_sms_sendform.php?LANG='.LANG_ID.'&event=ISMS_ORDER_'.$orderId.'">'.Loc::getMessage("STARTSEND_SMS_EVENTCODE_SENDSMS").'</a>
				</td></tr>';
				
				$form->tabs[] = array("DIV" => "my_edit", "TAB" => Loc::getMessage("STARTSEND_SMS_EVENTCODE_TABCONTROL_NAME"), "ICON"=>"aszmagazin", "TITLE"=>Loc::getMessage("STARTSEND_SMS_EVENTCODE_TABCONTROL_NAME"), "CONTENT"=>$html);
				
			}
			
		}
		
	}
	
	//отправка писем
	public static function OnBeforeEventSend($arFields, $eventMessage){
		
		//$eventMessage['EVENT_NAME'] - тип события
		//$eventMessage['ID'] - ид шаблона
		//$eventMessage['LID'] - ид сайта
		
		$returnSendMail = true;
		
		$res = \Startsend\Sms\EventlistTable::getList(
			array(
				'select' => array("*"),
				'filter' => array("=EVENT"=>'ISMS_BXEVENT',"ACTIVE"=>"Y")
			)
		);
		while($arData = $res->fetch()){
			
			$arData['PARAMS'] = unserialize($arData['PARAMS']);
			
			$right = false;
			
			$r_site = \Bitrix\Main\Mail\Internal\EventMessageSiteTable::getList(array(
				'select' => array("SITE_ID"),
				'filter' => array("EVENT_MESSAGE_ID"=>$eventMessage['ID'])
			));
			while($dt = $r_site->fetch()){
				if($arData['SITE_ID'] == $dt['SITE_ID']) {
					$right = true;
					break;
				}
			}
			
			if(!$right) continue;
			
			if($arData['PARAMS']['EVENT_NAME'] == $eventMessage['EVENT_NAME']){
				
				$right = false;
				
				if($arData['PARAMS']['ID'] == 'ALL') {
					$right = true;
				}else{
					if($arData['PARAMS']['ID'] == $eventMessage['ID']) $right = true;
				}
				
				if(!$right) continue;
				
				$arMakros = array();
				foreach($arFields as $fieldKey=>$fieldVal){
					$arMakros['#'.$fieldKey.'#'] = $fieldVal;
				}
				
				$arMakros['#EVENT_NAME#'] = 'ISMS_BXEVENT_'.$eventMessage['EVENT_NAME'];
				
				if($arData['PARAMS']['PHONE']){
					$arData['TEMPLATE'] = self::compileTemplate($arData['TEMPLATE'], $arMakros);
					$phoneAr = str_replace(array_keys($arMakros), $arMakros, $arData['PARAMS']['PHONE']);
					$phoneAr = preg_replace("/([^0-9,])/is","",$phoneAr);
					$phoneAr = explode(",",$phoneAr);
					$sender = ($arData['SENDER']) ? $arData['SENDER'] : "";
					
					foreach($phoneAr as $phone){
						if(strlen($phone)>7){
							
							
							if(trim($arData['TEMPLATE'])){
								$smsOb = new \Startsend\Sms\Sender();
								$smsOb->event = $arMakros['#EVENT_NAME#'];
								$smsOb->eventName = Loc::getMessage("STARTSEND_SMS_EVENTCODE_ISMS_BXEVENT");
								$smsOb->sendSms($phone, $arData['TEMPLATE'],0,$sender);
								
								$smsOb->event = null;
								$smsOb->eventName = null;
								
								if($arData['PARAMS']['BREAK'] == "Y") $returnSendMail = false;
							}
							
							break;
						}
					}
				}
				
				
			}
			
			
		}
		 
		
		//запрет отправки письма
		if(!$returnSendMail) return false;
		
	}
	
}