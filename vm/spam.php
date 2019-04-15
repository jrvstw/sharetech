<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title></title>
<script type="text/javascript">
  <!-- 
  if (top.location != self.location) {
      top.location=self.location;
}
  -->
</script>
</head>
<?
if(stristr($_SERVER["QUERY_STRING"], "%26Auth_codes")) {
	$_SERVER["QUERY_STRING"] = str_replace("%26","&",$_SERVER["QUERY_STRING"]);
}
?>
<frameset rows="*,0" style="'framespacing:0' ; 'frameborder:0' ; 'border:0'" >
  <frame name="main" src="/Program/mailrec/login_get_user_spamlist.php?<?=$_SERVER["QUERY_STRING"];?>">
  <frame name="no" src="" scrolling="auto" noresize>
</frameset><noframes></noframes>
</html>
