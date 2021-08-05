<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$module_id = "startsend.sms";

\Bitrix\Main\Loader::includeModule($module_id);
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($POST_RIGHT == "D")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$APPLICATION->SetAdditionalCSS("/bitrix/css/".$module_id."/style.css");
	
class MlifeRowListAdmin extends \Startsend\Sms\Main {
	
	public function __construct($params) {
		parent::__construct($params);
	}
	
	public function getMlifeRowListAdminCustomRow($row){

		if($row->arRes['TIME_SEND']) {
			$row->AddViewField("TIME_SEND", $row->arRes['TIME_SEND']->toString(new \Bitrix\Main\Context\Culture(array("FORMAT_DATETIME" => "DD.MM.YYYY HH:MI"))));
		}
		if($row->arRes['TIME_STATE']) {
			$row->AddViewField("TIME_STATE", $row->arRes['TIME_STATE']->toString(new \Bitrix\Main\Context\Culture(array("FORMAT_DATETIME" => "DD.MM.YYYY HH:MI"))));
		} else {
			$row->AddViewField("TIME_STATE", Loc::getMessage("STARTSEND_SMS_LIST_TIME_ST_L"));
		}
		$row->AddViewField("STATUS", $row->arRes['STATUS']);
	}
	
}

$arParams = array(
	"PRIMARY" => "ID",
	"ENTITY" => "\\Startsend\\Sms\\ListTable",
	"FILE_EDIT" => 'startsend_sms_sendform.php',
	"BUTTON_CONTECST" => array(),
	"ADD_GROUP_ACTION" => array("delete"),
	"COLS" => true,
	"FIND" => array(
		"SENDER","PHONE","EVENT","MESS","SMSID", "STATUS",
	),
	"LIST" => array("ACTIONS" => array("delete")),
	"CALLBACK_ACTIONS" => array()
);

$adminCustom = new MlifeRowListAdmin($arParams);
$adminCustom->defaultInterface();