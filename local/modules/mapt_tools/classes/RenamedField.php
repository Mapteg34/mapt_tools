<?php

namespace Mapt\Tools;

use Bitrix\Main\Entity\Field;

class RenamedField extends Field {
	/**
	 * 
	 * @param Field $clone
	 */
	public function __construct($clone,$title=false)
	{
		parent::__construct($clone->getName(), $clone->initialParameters);
		
		if ($title!==false)
			$this->setTitle($title);
	}
	public function setTitle($title) {
		$this->title = $title;
	}
}