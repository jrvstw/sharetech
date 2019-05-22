<?php

// warning:
$string = "=?UTF-8?Q?You_know_what's_hot_in_China??=";

// notice:
//$string = "Subject: =?utf-8?B?QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6?=
 //=?utf-8?B?uueNjumHkemAmuefpQ==?=";
//$string = "QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6uueNjumHkemAmuefpQ";
//         C 049343  葉  秋  枝   0 4月  獎  金   -  個  人  獎  金  通  知
/*
$string = "Subject: =?utf-8?B?SjAwMzM1Oeadjua3keWNvyAwNOaciOeNjumHkSAtIOWAi+S6?=
 =?utf-8?B?uueNjumHkemAmuefpQ==?=";
Subject: =?big5?B?qm+u5Krhtn2kRqFJqKvFb36sS7lDpm6uyaX6oUGyb6TiuqmoQr3gruSmbqVos0Kh?=
 =?big5?B?SQ==?=
Subject: =?UTF-8?B?44CQ5oqY5YO55Yi455m86YCB6YCa55+l44CR5pyD5ZOh5pel5pyA6auY54++?=
 =?UTF-8?B?5oqYMjAw77yB5aSP5aSp5Yiw5LqG546p5rC06YCZ5qij546p5LiA5aSP77yB?=
 =?UTF-8?B?6YCg5Z6L5rWu5o6S44CA5rOz5rGg5pyA5pyJ5Z6LL+OAkOael+S4ieebiuWI?=
 =?UTF-8?B?t+WFt+e1hOOAke+8jeWkj+aXpeS/nemkiumdoOmAmee1hO+8gS/lqr3lqr3l?=
 =?UTF-8?B?ronlv4PluavmiYvjgJDml6XmnKzms6Hms6HnjonjgJE=?=
 */

//echo mb_convert_encoding($string, 'UTF-8', 'GB2312');
//echo base64_decode($string);
//echo iconv('GBK', 'UTF-8', $string);
echo iconv_mime_decode($string);
echo "\n";

