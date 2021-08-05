<?php
namespace Startsend\Sms;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Fields {
	
	public static function getOrderCodes($MCR_EXT){
		
		$str = '';
		$r = \Bitrix\Sale\Internals\OrderPropsTable::getList(array('select'=>array("CODE","NAME","PERSON"=>"PERSON_TYPE.NAME"),"order"=>array("PERSON_TYPE.NAME"=>"ASC","ID"=>"ASC")));
		while($data = $r->fetch()){
			$str .= "#PROPERTY_".$data["CODE"]."# - ".$data["NAME"]."(".$data["PERSON"].")"."<br>";
		}
		$str .= "";
		
		$MCR_EXT = str_replace("#ORDER_SUM#",$str."#ORDER_SUM#",$MCR_EXT);
		
		return $MCR_EXT;
	}
	
	public static function newOrderHtml($value=""){
		
		if(!$value) serialize(array());
		
		$data = unserialize($value);
		
		$macros = '';

		$pp = $data['PHONE'] ?? '#USER_PERSONAL_MOBILE#';
		$MCR_EXT = self::getOrderCodes(Loc::getMessage("STARTSEND_SMS_FIELDS_MACROS_NEWORDER"));
		$html = '<tr><td>'.Loc::getMessage("STARTSEND_SMS_FIELDS_MACROS").'</td><td>'.$MCR_EXT.'</td></tr><tr>';
		$html .= '<td><b>'.Loc::getMessage("STARTSEND_SMS_FIELDS_TO").'</b></td>';
		$html .= '<td><input type="text" name="PARAMS_PHONE" value="'.$pp.'"/><br>'.Loc::getMessage("STARTSEND_SMS_FIELDS_TO_MESS").'</td>';
		$html .= '</tr>';
		
		return $html;
	}
	
	public static function newOrderSave($arFields=array()){
		
		$PARAMS = array("PHONE"=>trim($_REQUEST['PARAMS_PHONE']));
		$arFields['PARAMS'] = serialize($PARAMS);
		
		return $arFields;
	}
	
	public static function payedOrderHtml($value=""){
		
		if(!$value) serialize(array());
		
		$data = unserialize($value);
		
		$macros = '';
        $pp = $data['PHONE'] ?? '#USER_PERSONAL_MOBILE#';
		$MCR_EXT = self::getOrderCodes(Loc::getMessage("STARTSEND_SMS_FIELDS_MACROS_NEWORDER"));
		$html = '<tr><td>'.Loc::getMessage("STARTSEND_SMS_FIELDS_MACROS").'</td><td>'.$MCR_EXT.'</td></tr><tr>';
		$html .= '<td><b>'.Loc::getMessage("STARTSEND_SMS_FIELDS_TO").'</b></td>';
		$html .= '<td><input type="text" name="PARAMS_PHONE" value="'.$pp.'"/><br>'.Loc::getMessage("STARTSEND_SMS_FIELDS_TO_MESS").'</td>';
		$html .= '</tr>';
		
		$statusAr = array(array("NAME"=>Loc::getMessage("STARTSEND_SMS_FIELDS_PAYED_Y"),"ID"=>"Y"), array("NAME"=>Loc::getMessage("STARTSEND_SMS_FIELDS_PAYED_N"),"ID"=>"N"));
		
		$html .= '<td><b>'.Loc::getMessage("STARTSEND_SMS_FIELDS_PAYED").'</b></td>';
		$html .= '<td>';
		$html .= '<select name="PARAMS_PAYED">';
			foreach($statusAr as $stat){
				$selected = '';
				if($stat['ID'] == $data['PAYED']) $selected = ' selected="selected"';
				$html .= '<option value="'.$stat['ID'].'"'.$selected.'>['.$stat['ID'].'] - '.$stat['NAME'].'</option>';
			}
		$html .= '</select>';
		$html .= '</td>';
		$html .= '</tr>';
		
		return $html;
	}
	
	public static function payedOrderSave($arFields=array()){
		
		$PARAMS = array(
			"PHONE"=>trim($_REQUEST['PARAMS_PHONE']),
			"PAYED"=>trim($_REQUEST['PARAMS_PAYED']),
		);
		$arFields['PARAMS'] = serialize($PARAMS);
		
		return $arFields;
	}
	
	public static function statusOrderHtml($value=""){
		
		if(!$value) serialize(array());
		
		$data = unserialize($value);
		
		$macros = '';
		$MCR_EXT = self::getOrderCodes(Loc::getMessage("STARTSEND_SMS_FIELDS_MACROS_NEWORDER"));
		$html = '<tr><td>'.Loc::getMessage("STARTSEND_SMS_FIELDS_MACROS").'</td><td>'.$MCR_EXT.'</td></tr><tr>';

        $pp = $data['PHONE'] ?? '#USER_PERSONAL_MOBILE#';
		
		$html .= '<td><b>'.Loc::getMessage("STARTSEND_SMS_FIELDS_TO").'</b></td>';
		$html .= '<td><input type="text" name="PARAMS_PHONE" value="'.$pp.'"/><br>'.Loc::getMessage("STARTSEND_SMS_FIELDS_TO_MESS").'</td>';
		$html .= '</tr>';
		
		$statusOb = \CSaleStatus::GetList();
		$statusAr = array(array("NAME"=>Loc::getMessage("STARTSEND_SMS_FIELDS_STATUS_ALL"),"ID"=>"ALL"));

		while($d = $statusOb->fetch()){
			$statusAr[$d['ID']] = $d;
		}
		
		$html .= '<td><b>'.Loc::getMessage("STARTSEND_SMS_FIELDS_STATUS_FROM").'</b></td>';
		$html .= '<td>';
		$html .= '<select name="PARAMS_STATUS_FROM">';
			foreach($statusAr as $stat){
				$selected = '';
				if($stat['ID'] == $data['STATUS_FROM']) $selected = ' selected="selected"';
				$html .= '<option value="'.$stat['ID'].'"'.$selected.'>['.$stat['ID'].'] - '.$stat['NAME'].'</option>';
			}
		$html .= '</select>';
		$html .= '</td>';
		$html .= '</tr>';
		
		$html .= '<td><b>'.Loc::getMessage("STARTSEND_SMS_FIELDS_STATUS_TO").'</b></td>';
		$html .= '<td>';
		$html .= '<select name="PARAMS_STATUS_TO">';
			foreach($statusAr as $stat){
				$selected = '';
				if($stat['ID'] == $data['STATUS_TO']) $selected = ' selected="selected"';
				$html .= '<option value="'.$stat['ID'].'"'.$selected.'>['.$stat['ID'].'] - '.$stat['NAME'].'</option>';
			}
		$html .= '</select>';
		$html .= '</td>';
		$html .= '</tr>';
		
		return $html;
	}
	
	public static function statusOrderSave($arFields=array()){
		
		$PARAMS = array(
			"PHONE"=>trim($_REQUEST['PARAMS_PHONE']),
			"STATUS_FROM"=>trim($_REQUEST['PARAMS_STATUS_FROM']),
			"STATUS_TO"=>trim($_REQUEST['PARAMS_STATUS_TO'])
		);
		$arFields['PARAMS'] = serialize($PARAMS);
		
		return $arFields;
	}
	
	public static function eventSendHtml($value=""){
		
		if(!$value) serialize(array());
		
		$data = unserialize($value);
		
		$macros = '';
		
		
		
		$defaultMactosText = "*".Loc::getMessage("STARTSEND_SMS_FIELDS_MACROS_BXEVENT");
		
		if($data['EVENT_NAME']){
			$resType = \Bitrix\Main\Mail\Internal\EventTypeTable::getList(array(
				'select' => array('NAME','DESCRIPTION'),
				'filter' => array('EVENT_NAME'=>$data['EVENT_NAME'],'LID'=>'ru')
			))->fetch();
			if($resType){
				$defaultMactosText = '<b>'.$resType['NAME'].'</b><br><pre>'.htmlspecialcharsBack($resType['DESCRIPTION']).'</pre>';
			}
		}
		
		$allType = \Bitrix\Main\Mail\Internal\EventTypeTable::getList(array(
			'select' => array('NAME','EVENT_NAME'),
			'filter' => array('LID'=>'ru')
		));
		$arAllType = array();
		while($dt = $allType->fetch()){
			$arAllType[$dt['EVENT_NAME']] = '['.$dt['EVENT_NAME'].'] - '.$dt['NAME'];
		}
		
		$html = '<tr><td>'.Loc::getMessage("STARTSEND_SMS_FIELDS_MACROS").'</td><td>'.$defaultMactosText.'<br>*'.Loc::getMessage("STARTSEND_SMS_FIELDS_MACROS_BXEVENT_NOTE").'</td></tr><tr>';
        $pp = $data['PHONE'] ?? '#USER_PERSONAL_MOBILE#';
		$html .= '<td><b>'.Loc::getMessage("STARTSEND_SMS_FIELDS_TO").'</b></td>';
		$html .= '<td><input type="text" name="PARAMS_PHONE" value="'.$pp.'"/><br>'.Loc::getMessage("STARTSEND_SMS_FIELDS_TO_MESS").'</td>';
		$html .= '</tr>';
		
		$html .= '<td><b>'.Loc::getMessage("STARTSEND_SMS_FIELDS_EVENT_NAME").'</b></td>';
		$html .= '<td>';
		$html .= '<select name="PARAMS_EVENT_NAME">';
			foreach($arAllType as $statKey=>$stat){
				$selected = '';
				if($statKey == $data['EVENT_NAME']) $selected = ' selected="selected"';
				$html .= '<option value="'.$statKey.'"'.$selected.'>'.$stat.'</option>';
			}
		$html .= '</select>';
		$html .= '</td>';
		$html .= '</tr>';
		
		$html .= '<td>'.Loc::getMessage("STARTSEND_SMS_FIELDS_BXEVENTID").'</td>';
		$html .= '<td><input type="text" name="PARAMS_ID" value="'.$data['ID'].'"/></td>';
		$html .= '</tr>';
		
		$html .= '<td>'.Loc::getMessage("STARTSEND_SMS_FIELDS_BXEVENT_BREAK").'</td>';
		$checked = '';
		if($data['BREAK'] == 'Y') $checked = ' checked="checked"';
		$html .= '<td><input type="checkbox" name="PARAMS_BREAK" value="Y"'.$checked.'/></td>';
		$html .= '</tr>';
		
		return $html;
	}
	
	public static function eventSendSave($arFields=array()){
		
		$PARAMS = array(
			"PHONE"=>trim($_REQUEST['PARAMS_PHONE']),
			"BREAK"=>($_REQUEST['PARAMS_BREAK'] == "Y") ? "Y" : "N",
			"ID"=>(trim($_REQUEST['PARAMS_ID']) ? trim($_REQUEST['PARAMS_ID']) : 'ALL'),
			"EVENT_NAME"=> trim($_REQUEST['PARAMS_EVENT_NAME'])
		);
		$arFields['PARAMS'] = serialize($PARAMS);
		
		return $arFields;
	}
	
}