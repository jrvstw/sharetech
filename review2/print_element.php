<?php

$buttons = array("button", array("Add"), null);
echo print_element($buttons);

function print_element($element)
{
	if (is_array($element)) {
		$string = "";
		foreach ($element[1] as $value)
			$string .= print_element($value);
		return "<$element[0] $element[2]>" . $string . "</$element[0]>";
	} else
		return $element;
}

