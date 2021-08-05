<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$aMenu = array();

$POST_RIGHT = $APPLICATION->GetGroupRight("startsend.sms");
if ($POST_RIGHT == "D") $aMenu;

$aMenu = Array(
	"parent_menu" => "global_menu_marketing",
		"section" => "startsend.sms",
		"sort" => 100,
		"module_id" => "startsend.sms",
		"text" => Loc::getMessage("STARTSEND_SMS_MODULE_NAME"),
		"title" => Loc::getMessage("STARTSEND_SMS_MODULE_DESC"),
		"items_id" => "menu_incoredevsms",
		"items" => array(
			array(
				"text" => Loc::getMessage("STARTSEND_SMS_MODULE_MENU_SENSMS"),
				"url" => "startsend_sms_sendform.php?lang=".LANGUAGE_ID,
				"more_url" => Array(),
				"title" => Loc::getMessage("STARTSEND_SMS_MODULE_MENU_SENSMS")
			),
			array(
				"text" => Loc::getMessage("STARTSEND_SMS_MODULE_MENU_BALANCE"),
				"url" => "startsend_sms_balance.php?lang=".LANGUAGE_ID,
				"more_url" => Array(),
				"title" => Loc::getMessage("STARTSEND_SMS_MODULE_MENU_BALANCE")
			),
			array(
				"text" => Loc::getMessage("STARTSEND_SMS_MODULE_MENU_HISTORY"),
				"url" => "startsend_sms_list.php?lang=".LANGUAGE_ID,
				"more_url" => Array(),
				"title" => Loc::getMessage("MLIFESS_MENU_HISTORY")
			),
			array(
				"text" => Loc::getMessage("STARTSEND_SMS_MODULE_MENU_EVENTLIST"),
				"url" => "startsend_sms_eventlist.php?lang=".LANGUAGE_ID,
				"more_url" => Array('startsend_sms_eventlist_edit.php?lang='.LANGUAGE_ID),
				"title" => Loc::getMessage("MLIFESS_MENU_EVENTLIST")
			),
		)
);
return $aMenu;
?>