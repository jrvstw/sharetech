<?php

class HtmlObject
{
	public $label;
	public $content;
	public $attr;

	function __construct($label, $content, $attr)
	{
		//
	}

	public function get_layout()
	{
		$output = "";
		if (is_array($content))
			foreach ($content as $child)
				$output .= $child->get_layout();
		elseif (is_string($content))
			$output .= $this->content;
		else
			$output .= $this->content->get_layout();
		if (!empty($label))
			$output = "<$this->label $this->attr>" . $output . "</$this->label>";
		return $output;
	}
}

