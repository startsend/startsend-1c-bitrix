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

/**
 * Class MlifeRowListAdmin
 */
class MlifeRowListAdmin extends \Startsend\Sms\Main {

    /**
     * MlifeRowListAdmin constructor.
     * @param $params
     */
	public function __construct($params) {
		parent::__construct($params);
	}

    /**
     * @param $row
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
	public function getMlifeRowListAdminCustomRow($row){

		$row->AddViewField("SENDER", ($row->arRes['SENDER']) ? $row->arRes['SENDER'] : \Bitrix\Main\Config\Option::get("startsend.sms","sender","",""));
		$row->AddViewField("TEMPLATE", '<font style="font-size:12px;">'.$row->arRes['TEMPLATE'].'</font>');
		$row->AddCheckField("ACTIVE");
		
		$params = unserialize(htmlspecialcharsBack($row->arRes['PARAMS']));
		$html = '';
		foreach($params as $name=>$val){
			$html .= $name.': '.$val.';<br/>';
		}
		$row->AddViewField("PARAMS", '<font style="font-size:12px;">'.$html.'</font>');
		$row->AddInputField("NAME", array("size"=>20));
		$row->AddInputField("SENDER", array("size"=>20));

		$sHTML = '<textarea rows="7" cols="50" name="FIELDS['.$row->arRes['ID'].'][TEMPLATE]">'.htmlspecialcharsBack($row->arRes['TEMPLATE']).'</textarea>';
		$row->AddEditField("TEMPLATE", $sHTML);
		
	}
	
}

$arParams = array(
	"PRIMARY" => "ID",
	"ENTITY" => "\\Startsend\\Sms\\EventlistTable",
	"FILE_EDIT" => 'startsend_sms_eventlist_edit.php',
	"BUTTON_CONTECST" => array(),
	"ADD_GROUP_ACTION" => array("delete","edit"),
	"COLS" => true,
	"FIND" => array(
		"NAME","EVENT","SITE_ID"
	),
	"LIST" => array("ACTIONS" => array("delete","edit")),
	"CALLBACK_ACTIONS" => array()
);

$adminCustom = new MlifeRowListAdmin($arParams);
$adminCustom->defaultInterface();