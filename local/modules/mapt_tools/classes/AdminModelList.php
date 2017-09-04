<?php

namespace Mapt\Tools;

class AdminModelList extends AdminList {
	private $entity;
	private $entity_data_class;
	public function __construct($entity) {
		$this->entity = $entity;
		$this->entity_data_class = $this->entity->getDataClass();
		parent::__construct(str_replace("\\","_",$entity->getDataClass()));
	}
	public function initMode() {
		parent::initMode();
		$mode = $this->getMode();
		
		$class = $this->entity_data_class;
		$arSrcFields = $class::getMap();
		$arFields = array();
		foreach ($arSrcFields as $fieldID=>$obField) {
			if ($mode=="edit") {
				if ($obField instanceof \Bitrix\Main\Entity\ScalarField && $obField->isAutocomplete()) continue;
				if ($obField instanceof \Bitrix\Main\Entity\ExpressionField) continue;
				if ($obField instanceof \Bitrix\Main\Entity\ReferenceField) continue;
			}
			
			if ($obField instanceof \Bitrix\Main\Entity\ReferenceField) {
				if ($mode=="edit") continue;
				$refEntity = $obField->getRefEntity();
				foreach ($refEntity->getFields() as $refFieldID=>$obRefField) {
					if ($obRefField instanceof \Bitrix\Main\Entity\ReferenceField) continue;
					$arFields[\Bitrix\Main\Entity\QueryChain::getChainByDefinition(
						$this->entity,
						$fieldID.".".$refFieldID
					)->getAlias()] = $obRefField;
				}
			} else {
				$arFields[$fieldID] = $obField;
			}
		}
		$this->setFields($arFields);
	}
	private function getSelect() {
		$class = $this->entity_data_class;
		$arSrcFields = $class::getMap();
		$arSelect = array();
		foreach ($arSrcFields as $fieldID=>$obField) {
			if ($obField instanceof \Bitrix\Main\Entity\ReferenceField) {
				$refEntity = $obField->getRefEntity();
				foreach ($refEntity->getFields() as $refFieldID=>$obRefField) {
					if ($obRefField instanceof \Bitrix\Main\Entity\ReferenceField) continue;
					$_fieldID = $fieldID.".".$refFieldID;
					$alias = \Bitrix\Main\Entity\QueryChain::getChainByDefinition(
						$this->entity,
						$fieldID.".".$refFieldID
					)->getAlias();
					if (!isset($this->arFields[$alias])) continue;
					$arSelect[] = $_fieldID;
				}
			} else {
				$arSelect[] = $fieldID;
			}
		}
		return $arSelect;
	}
	protected function getData($arFilter) {
		$obData = new $this->entity_data_class();
		return $obData->GetList(array(
			"select"=>$this->getSelect(),
			"order"=>array($GLOBALS["by"]=>$GLOBALS["order"]),
			"filter"=>$arFilter
		));
	}
	protected function deleteAll($arFilter){
		$obData = new $this->entity_data_class();
		$arID=array();
		$rsData = $obData->GetList(array("filter"=>$arFilter));
		while($arRes = $rsData->Fetch())
			$arID[] = $arRes['ID'];
		
		$this->deleteItems($arID);
	}
	protected function deleteItems($arID){
		$obData = new $this->entity_data_class();
		
		foreach($arID as $ID) {
			if(strlen($ID)<=0) continue;
			$ID = IntVal($ID);

			$res = $obData->delete($ID);
			if(!$res->isSuccess())
				$this->obAdmin->AddGroupError("Fail to delete element: ".implode(";",$res->getErrorMessages()), $ID);
		}
	}
	protected function getElement($ID) {
		$class = $this->entity_data_class;
		return $class::getRow(array(
			"filter"=>array("ID"=>$ID),
			"select"=>$this->getSelect()
		));
	}
	protected function saveElement($ID,$arFields) {
		foreach ($this->arFields as $fieldID=>$obField) {
			if ($obField instanceof \Bitrix\Main\Entity\DateField) {
				if ($arFields[$fieldID])
					$arFields[$fieldID] = new \Bitrix\Main\Type\Date($arFields[$fieldID]);
				elseif (!$arFields[$fieldID] && $ID<=0)
					unset($arFields[$fieldID]);
			} elseif ($obField instanceof \Bitrix\Main\Entity\DatetimeField) {
				if ($arFields[$fieldID])
					$arFields[$fieldID] = new \Bitrix\Main\Type\DateTime($arFields[$fieldID]);
				elseif (!$arFields[$fieldID] && $ID<=0)
					unset($arFields[$fieldID]);
			}
		}

		$res = false;
		$class = $this->entity_data_class;
		if($ID > 0) {
			$res = $class::update($ID, $arFields);
		} else {
			$res = $class::add($arFields);
		}
		return $res;
	}
}
