<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<form method="POST" ENCTYPE="multipart/form-data" name="post_form" onsubmit="return false;">
	<?$tabControl->Begin();?>
	<?$tabControl->BeginNextTab();?>
	<?foreach($this->arFields as $fieldID=>$obField):?>
		<?if($obField instanceof \Bitrix\Main\Entity\ScalarField && $obField->isAutocomplete()) continue;?>
		<?if($obField instanceof \Bitrix\Main\Entity\ReferenceField) continue;?>
		<tr>
			<td width="40%"><?=$obField->getTitle()?></td>
			<td width="60%">
				<input readonly type="text" value="<?=htmlspecialcharsEx($this->editValsFromForm ? $_POST[$fieldID] : $this->element[$fieldID])?>"/>
			</td>
		</tr>
	<?endforeach?>
	<?$tabControl->Buttons(array(
		"btnSave"=>false,
		"btnApply"=>false,
		"back_url"=>$GLOBALS["APPLICATION"]->GetCurPage()."?lang=".LANG,
	))?>
	<?$tabControl->End()?>
</form>