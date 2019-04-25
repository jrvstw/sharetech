<?php

class User
{
	public $uname;

	function __construct($name)
	{
		$this->uname = $name;
	}

	public function get_uname()
	{
		return $this->uname;
	}
	public function validate($pswd)
	{
		$account_pswd = array(
			"admin" => "admin"
		);

		if ($pswd == $account_pswd[$this->uname]) {
			$_SESSION["tried_times"] = 0;
			return true;
		} else {
			$_SESSION["tried_times"]++;
			return false;
		}
	}
}

