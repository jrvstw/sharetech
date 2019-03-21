<?php

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

