<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class startsend_sms extends CModule
{
        var $MODULE_ID = "startsend.sms";
        var $MODULE_VERSION;
        var $MODULE_VERSION_DATE;
        var $MODULE_NAME;
        var $MODULE_DESCRIPTION;
        var $PARTNER_NAME;
        var $PARTNER_URI;

        function startsend_sms() {
				$path = str_replace("\\", "/", __FILE__);
				$path = substr($path, 0, strlen($path) - strlen("/index.php"));
				include($path."/version.php");
				
				$this->MODULE_VERSION = $arModuleVersion["VERSION"];
				$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
				$this->PARTNER_NAME = 'StartSend';
				$this->PARTNER_URI = 'http://startsend.ru/';
				
				if(Loc::getMessage("STARTSEND_SMS_PARTNER_NAME")){
					$this->PARTNER_NAME = Loc::getMessage("STARTSEND_SMS_PARTNER_NAME");
				}
				if(Loc::getMessage("STARTSEND_SMS_PARTNER_URI")){
					$this->PARTNER_URI = Loc::getMessage("STARTSEND_SMS_PARTNER_URI");
				}
				
				$this->MODULE_NAME = Loc::getMessage("STARTSEND_SMS_MODULE_NAME");
				$this->MODULE_DESCRIPTION = Loc::getMessage("STARTSEND_SMS_MODULE_DESC");
			return true;
        }

        function DoInstall() {
			
			CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
			CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/css/".$this->MODULE_ID,
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/css/".$this->MODULE_ID);
			
			RegisterModule($this->MODULE_ID);
			
			$eventManager = \Bitrix\Main\EventManager::getInstance();
			$eventManager->registerEventHandlerCompatible('main', 'OnAdminTabControlBegin', $this->MODULE_ID, '\Startsend\Sms\Events', 'OnAdminTabControlBegin');
			$eventManager->registerEventHandler('messageservice', 'onGetSmsSenders', $this->MODULE_ID, '\Startsend\Sms\Handlers', 'onGetSmsSenders');
			
			$this->createTable();
			$this->createAgents();
		
			LocalRedirect('/bitrix/admin/settings.php?lang=ru&mid='.$this->MODULE_ID.'&mid_menu=1');
        }

        function DoUninstall() {
			//Удаление файлов визуальной части админ панели
			DeleteDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
			DeleteDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/css/".$this->MODULE_ID,
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/css/".$this->MODULE_ID);
			
			$this->deleteTable();
			$this->deleteAgents();
			
			UnRegisterModuleDependences("main", "OnAdminTabControlBegin", $this->MODULE_ID, "\Startsend\Sms\Events", "OnAdminTabControlBegin");
			
			UnRegisterModuleDependences("messageservice", "onGetSmsSenders", $this->MODULE_ID, '\Startsend\Sms\Handlers', "onGetSmsSenders");
			
			\Bitrix\Main\Loader::includeModule("startsend.sms");
			\Startsend\Sms\EventlistTable::removeAllEvent();
			
			UnRegisterModule($this->MODULE_ID);
			
			COption::RemoveOption($this->MODULE_ID);
        }
	
	function createTable() {
		global $DB;
		$sql = "
		CREATE TABLE IF NOT EXISTS `startsend_sms_history` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`SMSID` varchar(100) DEFAULT NULL,
		`SENDER` varchar(50) NOT NULL,
		`PHONE` varchar(50) NOT NULL,
		`TIME_SEND` datetime NOT NULL,
		`TIME_STATE` datetime DEFAULT NULL,
		`MESS` varchar(655) NOT NULL,
		`PRIM` varchar(655) NOT NULL,
		`STATUS` varchar(20) NOT NULL,
		`EVENT` varchar(100) DEFAULT NULL,
		`EVENT_NAME` varchar(100) DEFAULT NULL,
		`SORT` int(3) NOT NULL DEFAULT '500',
		 PRIMARY KEY (`ID`)
		);
		";
		if(strtolower($DB->type)=="mysql") $res = $DB->Query($sql);
		$sql = "
		CREATE TABLE IF NOT EXISTS `startsend_sms_eventlist` (
		`ID` int(9) NOT NULL AUTO_INCREMENT,
		`SITE_ID` varchar(10) NOT NULL,
		`SENDER` varchar(50) NULL,
		`EVENT` varchar(50) NOT NULL,
		`NAME` varchar(255) NOT NULL,
		`TEMPLATE` varchar(2500) NULL,
		`PARAMS` varchar(6255) NULL,
		`ACTIVE` varchar(1) NOT NULL DEFAULT 'N',
		 PRIMARY KEY (`ID`)
		);
		";
		if(strtolower($DB->type)=="mysql") $res = $DB->Query($sql);
	}
	
	function deleteTable () {
		global $DB;
		$sql = 'DROP TABLE IF EXISTS `startsend_sms_history`';
		$res = $DB->Query($sql);
		$sql = 'DROP TABLE IF EXISTS `startsend_sms_eventlist`';
		$res = $DB->Query($sql);
	}
	
	function createAgents() {
		CAgent::AddAgent(
		"\\Startsend\\Sms\\Agent::statusSms();",
		$this->MODULE_ID,
		"N",
		600);
		CAgent::AddAgent(
		"\\Startsend\\Sms\\Agent::turnSms();",
		$this->MODULE_ID,
		"N",
		300);
	}
	
	function deleteAgents() {
		CAgent::RemoveAgent("\\Startsend\\Sms\\Agent::turnSms();", $this->MODULE_ID);
		CAgent::RemoveAgent("\\Startsend\\Sms\\Agent::statusSms();", $this->MODULE_ID);
	}
}