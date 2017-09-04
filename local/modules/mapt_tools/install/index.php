<?php
use Bitrix\Main\ModuleManager;

class mapt_tools extends CModule
{
	var $MODULE_ID = "mapt_tools";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;

	var $errors;

	function __construct()
	{
		$arModuleVersion = array();
		include(dirname(__FILE__)."/version.php");
		
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		
		$this->MODULE_NAME = "Mapt Tools";
		$this->MODULE_DESCRIPTION = "Mapt tools module";
		$this->PARTNER_NAME = "Mapt aka Malahov Artem";
		$this->PARTNER_URI = "http://ibs1c.ru";
	}

	function InstallDB() {
		return true;
	}
	function UnInstallDB($arParams = array()) {
		return true;
	}

	function InstallEvents() {
		return true;
	}
	function UnInstallEvents() {
		return true;
	}

	function InstallFiles() {
		return true;
	}
	function UnInstallFiles() {
		return true;
	}


	function DoInstall() {
		$RIGHT = $GLOBALS["APPLICATION"]->GetGroupRight($this->MODULE_ID);
		if($RIGHT == "W") {
			$this->InstallDB();
			ModuleManager::registerModule($this->MODULE_ID);
			$this->InstallEvents();
			$this->InstallFiles();
		}
	}

	function DoUninstall() {
		$RIGHT = $GLOBALS["APPLICATION"]->GetGroupRight($this->MODULE_ID);
		if($RIGHT == "W") {
			ModuleManager::unRegisterModule($this->MODULE_ID);
			$this->UnInstallDB();
			$this->UnInstallEvents();
			$this->UnInstallFiles();
		}
	}
}