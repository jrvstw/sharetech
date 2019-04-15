<?
class tableclass
{
	var $tHeader = array();
	var $tRow = array();
	var $HeaderProperty = array();
	var	$javascript = "";
	
	function setHeader($aHeader)
	{
		$this->tHeader = $aHeader;
	}
	
	function setHeaderProperty($idx, $identify, $value)
	{
		$this->HeaderProperty[$idx][$identify] = $value;
	}
	
	function insertRow($aRow)
	{
		$this->tRow[] = $aRow;
	}
	
	function display()
	{
		unset($html);

		$html.= "<table class='list' id='cust_table'>";
		
		/** Table Header **/
		$html.= "	<tr>";
		foreach($this->tHeader as $idx => $elt)
		{
			unset($property);
			$property = $this->HeaderProperty[$idx];

			$html.= "<th class='list'" . (isset($property['width']) ? " style='width: $property[width];'" : "") . ">";
			if(isset($property['selectAll']))
			{
				$html.= "<input type='checkbox' id='selectAll_{$idx}' />";
				$this->javascript.= $this->selectAllCheckbox("selectAll_{$idx}", $property['selectAll']);
			}
			else
			{
				$html.= $elt;
			}
			$html.= "</th>";
		}
		$html.= "	</tr>";

		/** Table Row **/
		foreach($this->tRow as $elt)
		{
			$html.= "	<tr class='list'>";
			$html.= "  <td class='list'>";
			$html.= implode("</td><td class='list'>", $elt);
			$html.= "  </td>";
			$html.= "	</tr>";
		}
		
		$html.= "</table>";
		
		$this->javascript.= $this->hoverEffect();
		
		if(!empty($this->javascript))
		{
			$html.= "<script type='text/javascript'>";
			$html.= $this->javascript;
			$html.= "</script>";
		}
				
		/** Output **/
		echo $html;
	}

	function selectAllCheckbox($id, $target)
	{
		return '
		document.getElementById("'.$id.'").onclick = function() {
			var inputs = document.getElementsByTagName("input");
			for(var idx = 0; idx < inputs.length; idx++)
			{
				if(inputs[idx].type == "checkbox" && inputs[idx].name == "'.$target.'")
				{/** set all **/ 
					inputs[idx].checked = this.checked;
				}
			}
		};
		';
	}
	
	function hoverEffect()
	{
		return '		
			var allRow = document.getElementById("cust_table").getElementsByTagName("tr");
			for(var idx = 0; idx < allRow.length; idx++)
			{
				if(idx == 0)
				{//Header
					continue;
				}

				/** Add onMouseOut Event **/
				allRow[idx].onmouseout = function(){
					this.style.backgroundColor = "#f0f0f0";
				};

				/** Add onMouseOver Event **/
				allRow[idx].onmouseover = function(){
					this.style.backgroundColor = "#AAAAFF";
				};
			}
		';		
	}
}
?>