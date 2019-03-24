<?php

$COLUMNS = array(
				 array("name"  => "isbn",
					   "shown" => "ISBN"
					   "regex" =>  "/^([0-9]{3}-){3}[0-9]$/");

				 array("name"  => "publisher",
					   "shown" => "出版社",
					   "regex" => "/^[^,]+$/");

				 array("name"  => "name",
					   "shown" => "書名",
					   "regex" => "/^[^,]+$/");

				 array("name"  => "author",
					   "shown" => "作者",
					   "regex" => "/^[^,]+$/");

				 array("name"  => "price",
					   "shown" => "定價",
					   "regex" => "/^[0-9]+$/");

				 array("name"  => "date",
					   "shown" => "發行日",
					   "regex" => "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/");

$columnName = array("isbn"      => "ISBN",
                    "publisher" => "出版社",
                    "name"      => "書名",
                    "author"    => "作者",
                    "price"     => "定價",
                    "date"      => "發行日");

$columnRegex = array("isbn"      => "/^([0-9]{3}-){3}[0-9]$/",
                     "publisher" => "/^[^,]+$/",
                     "name"      => "/^[^,]+$/",
                     "author"    => "/^[^,]+$/",
                     "price"     => "/^[0-9]+$/",
                     "date"      => "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/");

