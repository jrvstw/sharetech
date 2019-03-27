<?php

include("class/MyTable.php");
$bookTable = new MyTable("books");
$tableColumn = $bookTable->get_columns();

$sortOption = array("isbn", "publisher", "name", "author", "price", "date");
$orderOption = array("asc", "dsc");
$sort = substr($_GET['sortOrder'], 0, -3);
$order = substr($_GET['sortOrder'], -3);

if (in_array($sort, $tableColumn) == false or
	in_array($order, $orderOption) == false)
	die("sorting option matching failed.");

echo '<form id="sortOrder" action="index.php" method="get">
		<input type="hidden" name="sort" value="' . $sort . '">
		<input type="hidden" name="order" value="' . $order . '">
	  </form>';

?>
<script type="text/javascript">
	document.getElementById('sortOrder').submit();
</script>

