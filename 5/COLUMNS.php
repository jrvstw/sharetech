<?php

$COLUMNS = array(
	"isbn" => array(
		"shown" => "ISBN",
		"regex" =>  "/^([0-9]{3}-){3}[0-9]$/"),

	"publisher" => array(
		"shown" => "出版社",
		"regex" => "/^.+$/"),

	"name" => array(
		"shown" => "書名",
		"regex" => "/^.+$/"),

	"author" => array(
		"shown" => "作者",
		"regex" => "/^.+$/"),

	"price" => array(
		"shown" => "定價",
		"regex" => "/^[0-9]+$/"),

	"date" => array(
		"shown" => "發行日",
		"regex" => "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/"));

