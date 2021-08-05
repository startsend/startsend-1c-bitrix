<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
$module_id = "startsend.sms";
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
$zr = "";
$phone = '';
if (! ($MODULE_RIGHT >= "R"))
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
?>
<?
$APPLICATION->SetTitle(Loc::getMessage("STARTSEND_SMS_SENDFORM_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
$APPLICATION->SetAdditionalCSS("/bitrix/css/".$module_id."/style.css");

\Bitrix\Main\Loader::includeModule($module_id);
$smsOb = new \Startsend\Sms\Sender();

$senderOptions = $smsOb->getAllSender();

$orderId = false;

$datesend = $_REQUEST['datesend'];

//echo \Startsend\Sms\Agent::statusSms();

if($_REQUEST['event'] && strpos($_REQUEST['event'],'ISMS_ORDER_')!==false){
	$res = \Startsend\Sms\ListTable::getList(array(
		'select' => array("*"),
		'filter' => array("=EVENT"=>htmlspecialcharsbx($_REQUEST['event'])),
		'order' => array("TIME_SEND"=>"DESC"),
		'limit' => 1
	));
	$phoneAr = array();
	while ($arData = $res->fetch()){
		$phone = $arData['PHONE'];
		$phoneAr[preg_replace("/([^0-9])/is","",$arData['PHONE'])] = $arData['PHONE'];
	}
	$orderId = true;
	
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $MODULE_RIGHT == "W" && strlen($_REQUEST["Send"]) > 0)
{
	$message = strip_tags($_REQUEST['message']);
	$smsOb->translit = $_REQUEST['translit'] ? true : false;
	
	if($_REQUEST['event']) $smsOb->event = htmlspecialcharsbx($_REQUEST['event']);

	if(!$_REQUEST['phone'] or !$_REQUEST['message']) {
		\ShowNote(Loc::getMessage("STARTSEND_SMS_SENDFORM_ERR_REQ"),'errortext');
	}
	else{
		$phone = preg_replace("/([^0-9])/is","",$_REQUEST['phone']);
		if($phone) {
			$resp = $smsOb->sendSms($phone, $message, MakeTimeStamp($datesend, "DD.MM.YYYY HH:MI:SS"),trim($_REQUEST['sender']),Loc::getMessage("STARTSEND_SMS_SENDFORM_PRIM"));
			if(!$resp->error){
				\ShowNote(Loc::getMessage("STARTSEND_SMS_SENDFORM_NOTICE_SEND"),'notetext');
				$phone = false;
				$message = false;
				if($orderId) LocalRedirect('/bitrix/admin/sale_order_view.php?ID='.preg_replace("/([^0-9])/is","",$_REQUEST['event']).'&lang='.LANGUAGE_ID);
			}else{
				\ShowNote($resp->error,'errortext');
				
			}
		}
		else{
			\ShowNote(Loc::getMessage("STARTSEND_SMS_SENDFORM_ERR_PHONE").', '.$phone,'errortext');
		}
		
	}
	
}


$aTabs = array(
	array("DIV" => "edit3", "TAB" => Loc::getMessage("STARTSEND_SMS_SENDFORM_TAB"), "ICON" => "vote_settings", "TITLE" => Loc::getMessage("STARTSEND_SMS_SENDFORM_TAB")),
);
$tabControl = new \CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?=LANGUAGE_ID?>" id="FORMACTION">
<input type="hidden" name="event" value="<?=htmlspecialcharsbx($_REQUEST['event'])?>"/>
<?
$tabControl->BeginNextTab();
?>
	<?if($orderId){?>
	<tr><td colspan="2" style="text-align:center;font-weight:bold;"><?=Loc::getMessage("STARTSEND_SMS_SENDFORM_ORDER")?> <?=preg_replace("/([^0-9])/is","",$_REQUEST['event'])?><br/><br/></td></tr>
	<?}?>
	<?
	if($orderId){
	
		if(count($phoneAr)>1){
			?>
			<tr><td colspan="2" style="text-align:center;font-weight:bold;"><?=Loc::getMessage("STARTSEND_SMS_SENDFORM_ORDERPHONE")?>: <?=implode(',',$phoneAr)?><br/><br/></td></tr>
			<?
		}
	
	}
	?>
	<tr>
		<td><b><?=Loc::getMessage("STARTSEND_SMS_SENDFORM_PHONE")?></b>*:</td>
		<td>
			<input type="text" size="28" maxlength="255" value="<?=$phone?>" name="phone">
		</td>
		</td>
	</tr>
	<tr>
		<td><b><?=Loc::getMessage("STARTSEND_SMS_SENDFORM_MESS")?></b>*:</td>
		<td>
			<textarea cols="37" rows="5" name="message"><?=$message?></textarea>
		</td>
	</tr>

	<tr>
		<td><?=Loc::getMessage("STARTSEND_SMS_SENDFORM_DATE")?>:</td>
		<td>
			<?echo \CalendarDate("datesend", htmlspecialcharsbx($_REQUEST['datesend']), "datesend", "25", "class=\"date\"")?>
		</td>
	</tr>
	<tr>
		<td width="40%"><?=Loc::getMessage("STARTSEND_SMS_SENDFORM_TRANSLIT")?></td>
		<td width="60%">
			<input type="checkbox" name="translit" value="Y"/>
		</td>
	</tr>
	<?
$tabControl->Buttons();
?>
	<input <?if ($MODULE_RIGHT<"W") echo "disabled" ?> type="submit" class="adm-btn-green" name="Sendform" value="<?=Loc::getMessage("STARTSEND_SMS_SENDFORM_SEND")?>" />
	<input type="hidden" name="Send" value="Y" />
<?$tabControl->End();
?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>