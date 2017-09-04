<?php
namespace Mapt\Tools;
use Bitrix\Main\Localization\Loc;

abstract class AdminList {
	private $identify;
	private $mode = false;
	protected $arFields=array();
	
	private $defaultSortField = "ID";
	private $defaultSortOrder = "desc";
	
	private $filterEnabled = false;
	private $viewEnabled = false;
	private $addEnabled = false;
	private $editEnabled = false;
	private $deleteEnabled = false;
	
	/**
	 *
	 * @var \CAdminList
	 */
	protected $obAdmin;
	private $obFilter;
	private $obAdminResult;
	
	public function __construct($identify) {
		$this->identify = $identify;
	}
	
	public function initMode() {
		if ($_REQUEST["mode"]=="edit" && $this->editEnabled)
			$this->mode = "edit";
		elseif ($_REQUEST["mode"]=="view" && $this->viewEnabled)
			$this->mode = "view";
		else
			$this->mode = "list";
	}
	public function getMode() {
		if ($this->mode===false)
			$this->initMode();
		return $this->mode;
	}
	public function setFields($arFields) {
		$this->arFields = $arFields;
	}
	public function setField($fieldID,$obField) {
		$this->arFields[$fieldID] = $obField;
	}
	public function getField($fieldID) {
		return $this->arFields[$fieldID];
	}
	public function getFields($arFields) {
		return $this->arFields;
	}
	public function removeField($fieldID) {
		unset($this->arFields[$fieldID]);
	}
	public function prolog() {
		if ($this->mode===false)
			$this->initMode ();
		
		if ($this->mode=="edit")
			return $this->prolog_edit($_REQUEST["ID"]);
		if ($this->mode=="view")
			return $this->prolog_view($_REQUEST["ID"]);
		else
			return $this->prolog_list();
	}
	protected $editError=false;
	protected $editMessage=false;
	private $element=false;
	protected $editValsFromForm=false;
	private function prolog_edit($ID) {
		$GLOBALS["APPLICATION"]->SetTitle($ID>0 ? "Edit element ".$ID : "Create element");
		
		if (!empty($ID)) {
			if (!ctype_digit(strval($ID))) {
				$this->editError = new \CAdminMessage("Element not found");
				return;
			}

			$this->element = $this->getElement($ID);
			if (!$this->element) {
				$this->editError = new \CAdminMessage("Element not found");
				return;
			}
		}
		
		if($_SERVER["REQUEST_METHOD"] == "POST" && ($GLOBALS["save"]!="" || $GLOBALS["apply"]!="") && check_bitrix_sessid()) {
			$arFields = array();
			foreach ($this->arFields as $fieldID=>$obField) {
				if ($obField->isAutocomplete()) continue;
				if (isset($_POST[$fieldID]))
					$arFields[$fieldID] = $_POST[$fieldID];
			}
			
			$res = $this->saveElement($ID,$arFields);
			
			if($res->isSuccess()) {
				$ID = $res->getId();
				if ($GLOBALS["apply"] != "")
					LocalRedirect($GLOBALS["APPLICATION"]->GetCurPage()."?mode=edit&ID=".$ID."&mess=ok&lang=".LANG);
				else
					LocalRedirect($GLOBALS["APPLICATION"]->GetCurPage()."?lang=".LANG);
			} else {
				$this->editMessage = new \CAdminMessage("Fail to save element: ".implode(";",$res->getErrorMessages()));
				$this->editValsFromForm = true;
			}
		}
	}
	private function prolog_view($ID) {
		$GLOBALS["APPLICATION"]->SetTitle("View element ".$ID);
		
		if (empty($ID)) {
			$this->editError = new \CAdminMessage("Element not set");
			return;
		}
		if (!ctype_digit(strval($ID))) {
			$this->editError = new \CAdminMessage("Element not found");
			return;
		}
		$this->element = $this->getElement($ID);
		if (!$this->element) {
			$this->editError = new \CAdminMessage("Element not found");
			return;
		}
	}
	private $arFieldsPrepairFunctions=array();
	public function registerFieldPrepareFunction($fieldID,$func) {
		$this->arFieldsPrepairFunctions[$fieldID] = $func;
	}
	private function prepareFieldValue($fieldID,$value) {
		if (isset($this->arFieldsPrepairFunctions[$fieldID]))
			return $this->arFieldsPrepairFunctions[$fieldID]($value);
		
		return $value;
	}
	private function prolog_list() {
		$GLOBALS["APPLICATION"]->SetTitle("List");

		$oSort = new \CAdminSorting($this->identify, $this->defaultSortField, $this->defaultSortOrder);
		$this->obAdmin = new \CAdminList($this->identify, $oSort);
		
		$arHeaders = $this->getHeaders($this->arFields);
		
		$this->obAdmin->AddHeaders($arHeaders);
		
		$arFilter=array();
		if ($this->filterEnabled) {
			$arFilterFields = $this->getFilterFields();
			$this->obAdmin->InitFilter($arFilterFields);
			
			$this->obFilter = new \CAdminFilter(
				$this->identify."_filter",
				array_keys($arFilterFields)
			);
			
			if ($_REQUEST["del_filter"] != "Y") {
				foreach($this->arFields as $fieldID=>$obField) {
					if(
						$obField instanceof \Bitrix\Main\Entity\DatetimeField
						||
						$obField instanceof \Bitrix\Main\Entity\DateField
					) {
						$start = $GLOBALS["find_date_".strtolower($fieldID)."_start"];
						if (!empty($start)) {
							$start = ConvertDateTime($start,"D.M.Y");
							$arFilter[">=".$fieldID]=$start;
						}
						$end = $GLOBALS["find_date_".strtolower($fieldID)."_end"];
						if (!empty($end)) {
							$end = ConvertDateTime($end,"D.M.Y")." 23:59";
							$arFilter["<=".$fieldID]=$end;
						}
					} elseif($obField instanceof \Bitrix\Main\Entity\EnumField) {
						$vals = $GLOBALS["find_".strtolower($fieldID)];
						if (!empty($vals))
							$arFilter[$fieldID] = $vals;
					} elseif($obField instanceof \Bitrix\Main\Entity\IntegerField) {
						$start = $GLOBALS["find_".strtolower($fieldID)."_start"];
						if (!empty($start)) {
							$arFilter[">=".$fieldID]=$start;
						}
						$end = $GLOBALS["find_".strtolower($fieldID)."_end"];
						if (!empty($end)) {
							$arFilter["<=".$fieldID]=$end;
						}
					} else {
						$var = "find_" . strtolower($fieldID);
						if(!empty($GLOBALS[$var]))
							$arFilter[$fieldID] = "%".$GLOBALS[$var]."%";
					}
				}
			}
		}
		
		if($this->deleteEnabled && $arID = $this->obAdmin->GroupAction()) {
			if ($_REQUEST['action']=="delete") {
				if($_REQUEST['action_target']=='selected')
					$this->deleteAll($arFilter);
				else
					$this->deleteItems($arID);
			}
		}
		
		$res = $this->getData($arFilter);
		$this->obAdminResult = new \CAdminResult($res, $this->identify);
		$this->obAdminResult->NavStart();
		$this->obAdmin->NavText($this->obAdminResult->GetNavPrint("Страница"));
		
		$this->obAdmin->AddFooter(array(
			array("counter"=>true, "title"=>Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0")
		));
		
		if ($this->deleteEnabled)
			$this->obAdmin->AddGroupActionTable(Array(
				"delete"=>Loc::getMessage("MAIN_ADMIN_LIST_DELETE")
			));
		
		while($arItem = $this->obAdminResult->NavNext(true, "f_")) {
			$row = $this->obAdmin->AddRow($arItem["ID"], $arItem);
			foreach($arItem as $fieldID => $value)
				$row->AddViewField($fieldID, $this->prepareFieldValue($fieldID,$value));

			$arActions = array();
			
			if ($this->viewEnabled)
				$arActions[] = array(
					"ICON" => "view",
					"TEXT" => "Просмотреть",
					"ACTION" => $this->obAdmin->ActionRedirect($GLOBALS["APPLICATION"]->GetCurPage()."?mode=view&ID=".$arItem["ID"]."&lang=".LANGUAGE_ID),
					"DEFAULT" => true
				);
			
			if ($this->editEnabled)
				$arActions[] = array(
					"ICON" => "edit",
					"TEXT" => Loc::getMessage("MAIN_ADMIN_MENU_EDIT"),
					"ACTION" => $this->obAdmin->ActionRedirect($GLOBALS["APPLICATION"]->GetCurPage()."?mode=edit&ID=".$arItem["ID"]."&lang=".LANGUAGE_ID),
					"DEFAULT" => true
				);
			
			if ($this->deleteEnabled)
				$arActions[] = array(
					"ICON"=>"delete",
					"TEXT" => Loc::getMessage("MAIN_ADMIN_MENU_DELETE"),
					"ACTION"=>"if(confirm('Do you coonfirm delete?')) ".$this->obAdmin->ActionDoGroup($arItem["ID"], "delete")
				);

			if (!empty($arActions))
				$row->AddActions($arActions);
		}
		
		if ($this->addEnabled) {
			$this->obAdmin->AddAdminContextMenu(array(array(
				"TEXT"=>Loc::getMessage("MAIN_ADMIN_MENU_ADD"),
				"LINK"=>$GLOBALS["APPLICATION"]->GetCurPage()."?mode=edit&lang=".LANG,
				"TITLE"=>"",
				"ICON"=>"btn_new",
			)));
		}
		
		$this->obAdmin->CheckListMode();
	}
	public function epilog() {
		if ($this->mode===false)
			return false;
		
		if ($this->mode == "edit")
			return $this->epilog_edit($_REQUEST["ID"]);
		if ($this->mode == "view")
			return $this->epilog_view($_REQUEST["ID"]);
		else
			return $this->epilog_list();
	}
	private function epilog_edit($ID) {
		$aTabs = array(
			array("DIV" => "main", "TAB" => $ID ? "Edit element" : "Create element", "ICON"=>"main_user_edit")
		);
		$tabControl = new \CAdminTabControl("tabControl", $aTabs);
		
		$aMenu = array();
		$aMenu[]=array(
			"TEXT"  => "List",
			"TITLE" => "List",
			"LINK"  => $GLOBALS["APPLICATION"]->GetCurPage()."?lang=".LANG,
			"ICON"  => "btn_list",
		);
		/*
		$aMenu[] = array(
			"TEXT" => "Import",
			"TITLE" => "Import",
			"LINK"  => "mapt_blinger_promocodes_import.php?lang=".LANG,
			"ICON"  => "btn_new",
		);
		*/

		if($ID>0) {
			$aMenu[] = array("SEPARATOR"=>"Y");
			if ($this->addEnabled)
				$aMenu[] = array(
					"TEXT" => "Add",
					"TITLE" => "Add",
					"LINK"  => $GLOBALS["APPLICATION"]->GetCurPage()."?mode=edit&lang=".LANG,
					"ICON"  => "btn_new",
				);
			if ($this->deleteEnabled) {
				$aMenu[] = array(
					"TEXT" => "Delete",
					"TITLE" => "Delete",
					"LINK"=>"javascript:if(confirm('Do you coonfirm delete?')) window.location='".$GLOBALS["APPLICATION"]->GetCurPage()."?ID=".$ID."&action=delete&lang=".LANG."&".bitrix_sessid_get()."';",
					"ICON"  => "btn_delete",
				);
			}
		}

		$context = new \CAdminContextMenu($aMenu);
		$context->Show();
		
		if($_REQUEST["mess"] == "ok" && $ID>0)
			\CAdminMessage::ShowMessage(array("MESSAGE"=>"Element saved", "TYPE"=>"OK"));

		if ($this->editError) {
			echo $this->editError->Show();
			return;
		}
		
		if($this->editMessage)
			echo $this->editMessage->Show();
		
		include __DIR__."/../templates/admin_list_edit_form.php";
	}
	private function epilog_view($ID) {
		$aTabs = array(
			array("DIV" => "main", "TAB" => $ID ? "View element" : "View element", "ICON"=>"main_user_edit")
		);
		$tabControl = new \CAdminTabControl("tabControl", $aTabs);
		
		$aMenu = array();
		$aMenu[]=array(
			"TEXT"  => "List",
			"TITLE" => "List",
			"LINK"  => $GLOBALS["APPLICATION"]->GetCurPage()."?lang=".LANG,
			"ICON"  => "btn_list",
		);
		/*
		$aMenu[] = array(
			"TEXT" => "Import",
			"TITLE" => "Import",
			"LINK"  => "mapt_blinger_promocodes_import.php?lang=".LANG,
			"ICON"  => "btn_new",
		);
		*/

		if($ID>0) {
			$aMenu[] = array("SEPARATOR"=>"Y");
			if ($this->addEnabled)
				$aMenu[] = array(
					"TEXT" => "Add",
					"TITLE" => "Add",
					"LINK"  => $GLOBALS["APPLICATION"]->GetCurPage()."?mode=edit&lang=".LANG,
					"ICON"  => "btn_new",
				);
			if ($this->editEnabled)
				$aMenu[] = array(
					"TEXT" => "Edit",
					"TITLE" => "Edit",
					"LINK"  => $GLOBALS["APPLICATION"]->GetCurPage()."?mode=edit&ID=".$ID."&lang=".LANG,
					"ICON"  => "btn_new",
				);
			if ($this->deleteEnabled) {
				$aMenu[] = array(
					"TEXT" => "Delete",
					"TITLE" => "Delete",
					"LINK"=>"javascript:if(confirm('Do you coonfirm delete?')) window.location='".$GLOBALS["APPLICATION"]->GetCurPage()."?ID=".$ID."&action=delete&lang=".LANG."&".bitrix_sessid_get()."';",
					"ICON"  => "btn_delete",
				);
			}
		}

		$context = new \CAdminContextMenu($aMenu);
		$context->Show();

		if ($this->editError) {
			echo $this->editError->Show();
			return;
		}
		
		include __DIR__."/../templates/admin_list_view_form.php";
	}
	private function epilog_list() {
		if ($this->filterEnabled)
			include(__DIR__."/../templates/admin_list_filter.php");
		$this->obAdmin->DisplayList();
	}
	protected function getHeaders($arFields) {
		$arHeaders=array();
		foreach($arFields as $fieldID=>$obField) {
			$arHeaders[] = array(
				"id" => $fieldID,
				"sort"=>$fieldID,
				"content" => $obField->getTitle(),
				"default"  =>true
			);
		}
		return $arHeaders;
	}
	
	protected function getFilterFields() {
		$arFilterFields=array();
		foreach($this->arFields as $fieldID=>$obField) {
			$arFilterFields[$obField->getTitle()] = "find_" . strtolower($fieldID);
		}
		return $arFilterFields;
	}
	protected function deleteAll($arFilter){
		$this->obAdmin->AddGroupError("Fail to delete elements, method not redeclarated");
	}
	protected function deleteItems($arID){
		$this->obAdmin->AddGroupError("Fail to delete elements, method not redeclarated");
	}
	
	abstract protected function getData($arFilter);
	protected function getElement($ID) {
		return null;
	}
	/**
	 * 
	 * @param integer $ID
	 * @param \Bitrix\Main\Entity\Result $arFields
	 */
	protected function saveElement($ID,$arFields) {
		$res = new \Bitrix\Main\Entity\Result();
		$res->addError("Fail to save element, method not redeclarated");
		return $res;
	}
	
	public function setFilterEnabled($filterEnabled) {
		if ($this->mode!==false)
			return false;
		$this->filterEnabled = $filterEnabled;
		return true;
	}
	public function setViewEnabled($viewEnabled) {
		if ($this->mode!==false)
			return false;
		$this->viewEnabled = $viewEnabled;
		return true;
	}
	public function setAddEnabled($addEnabled) {
		if ($this->mode!==false)
			return false;
		$this->addEnabled = $addEnabled;
		return true;
	}
	public function setEditEnabled($editEnabled) {
		if ($this->mode!==false)
			return false;
		$this->editEnabled = $editEnabled;
		return true;
	}
	public function setDeleteEnabled($deleteEnabled) {
		if ($this->mode!==false)
			return false;
		$this->deleteEnabled = $deleteEnabled;
		return true;
	}
}