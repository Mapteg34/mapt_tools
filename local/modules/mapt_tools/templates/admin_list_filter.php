<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<form name="find_form" method="GET" action="">
	<?$this->obFilter->Begin()?>
	<?foreach($this->arFields as $fieldID=>$obField):?>
		<tr>
			<td><?=$obField->getTitle()?></td>
			<td>
				<?if(
					$obField instanceof \Bitrix\Main\Entity\DatetimeField
					||
					$obField instanceof \Bitrix\Main\Entity\DateField
				):?>
					<?
						$start_name = "find_date_".strtolower($fieldID)."_start";
						$end_name = "find_date_".strtolower($fieldID)."_end";
					?>
					<?=CalendarPeriod($start_name, $GLOBALS[$start_name],$end_name, $GLOBALS[$end_name],"find_form","Y")?>
				<?elseif($obField instanceof \Bitrix\Main\Entity\EnumField):?>
					<select multiple="true" name="find_<?=strtolower($fieldID)?>[]">
						<?foreach($obField->getValues() as $val):?>
							<?
								$isSelected = in_array($val,$GLOBALS["find_".strtolower($fieldID)]);
							?>
							<option <?if($isSelected):?>selected="Y"<?endif?> value="<?=htmlspecialcharsEx($val)?>"><?=htmlspecialcharsEx($val)?></option>
						<?endforeach?>
					</select>
				<?elseif($obField instanceof \Bitrix\Main\Entity\IntegerField):?>
					<input type="text" name="find_<?=strtolower($fieldID)?>_start" size="10" value="<?=htmlspecialcharsEx($GLOBALS["find_".strtolower($fieldID)."_start"])?>">
					...
					<input type="text" name="find_<?=strtolower($fieldID)?>_end" size="10" value="<?=htmlspecialcharsEx($GLOBALS["find_".strtolower($fieldID)."_end"])?>">
				<?else:?>
					<input type="text" name="find_<?=strtolower($fieldID)?>" value="<?=htmlspecialcharsEx($arFilter[$fieldID])?>" />
				<?endif?>
			</td>
		</tr>
	<?endforeach?>
	<?$this->obFilter->Buttons(array("table_id"=>$this->identify, "url"=>$GLOBALS["APPLICATION"]->GetCurPage(), "form"=>"find_form"))?>
	<?$this->obFilter->End();?>
</form>