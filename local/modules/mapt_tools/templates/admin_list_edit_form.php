<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
?>
<form method="POST" ENCTYPE="multipart/form-data" name="post_form">
	<?=bitrix_sessid_post();?>
	<input type="hidden" name="lang" value="<?=LANG?>" />
	<?if($ID>0 && !$bCopy):?>
		<input type="hidden" name="ID" value="<?=$ID?>" />
	<?endif?>
	<?$tabControl->Begin();?>
	<?$tabControl->BeginNextTab();?>
	<?foreach($this->arFields as $fieldID=>$obField):?>
		<tr>
			<td width="40%">
				<?if($obField->isRequired()):?><span class="required">*</span><?endif?>
				<?=$obField->getTitle()?>
			</td>
			<td width="60%">
				<?if(($obField instanceof \Bitrix\Main\Entity\DatetimeField) || ($obField instanceof \Bitrix\Main\Entity\DateField)):?>
					<?=CalendarDate($fieldID, $this->editValsFromForm ? $_POST[$fieldID] : $this->element[$fieldID], "post_form", "20")?>
				<?elseif($obField instanceof \Bitrix\Main\Entity\EnumField):?>
					<?$options = $obField->getValues()?>
					<select name="<?=$fieldID?>">
						<?foreach($options as $option):?>
							<option value="<?=htmlspecialcharsEx($option)?>"><?=htmlspecialcharsEx($option)?></option>
						<?endforeach?>
					</select>
				<?else:?>
					<input type="text" name="<?=$fieldID?>" value="<?=htmlspecialcharsEx($this->editValsFromForm ? $_POST[$fieldID] : $this->element[$fieldID])?>"/>
				<?endif?>
			</td>
		</tr>
	<?endforeach?>
	<?$tabControl->Buttons(array(
		"disabled"=>false,
		"back_url"=>$GLOBALS["APPLICATION"]->GetCurPage()."?lang=".LANG,
	))?>
	<?$tabControl->End()?>

	<?echo BeginNote();?>
	<span class="required">*</span><?=Loc::getMessage("REQUIRED_FIELDS")?>
	<?echo EndNote();?>
</form>