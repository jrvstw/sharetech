<?php
include_once "tools/mail_analyzing_tools.php";

$case["usual"] = "=?utf-8?B?QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6uueNjumHkemAmuefpQ==?=";

$case["plain_text"] = "Fwd: SFExpress | Invoice | Shipping | Tracking |";

$case["mixed"] = "Subject: =?utf-8?B?QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6uueNjumHkemAmuefpQ==?=";

$case["q_mark_included"] = "=?UTF-8?Q?You_know_what's_hot_in_China??=";

//$case["big-5_encoded"] = "???????ī~?M?橱";

$case["incomplete_byte_1"] = "=?utf-8?B?QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6?=
 =?utf-8?B?uueNjumHkemAmuefpQ==?=";

$case["incomplete_byte_2"] = "=?UTF-8?B?44CQ5oqY5YO55Yi455m86YCB6YCa55+l44CR5pyD5ZOh5pel5pyA6auY54++?=
 =?UTF-8?B?5oqYMjAw77yB5aSP5aSp5Yiw5LqG546p5rC06YCZ5qij546p5LiA5aSP77yB?=
 =?UTF-8?B?6YCg5Z6L5rWu5o6S44CA5rOz5rGg5pyA5pyJ5Z6LL+OAkOael+S4ieebiuWI?=
 =?UTF-8?B?t+WFt+e1hOOAke+8jeWkj+aXpeS/nemkiumdoOmAmee1hO+8gS/lqr3lqr3l?=
 =?UTF-8?B?ronlv4PluavmiYvjgJDml6XmnKzms6Hms6HnjonjgJE=?=";

// org_spam_20190521/860/2019/05/03/957292
$case["incomplete_byte_3"] = "=?UTF-8?B?UmU64piF6L2J6IG35r2u5b615YSq6LOq5Lq65omN54++5Zyo5Y+q6KaBMTgw?=aAa
 =?UTF-8?B?MOWFgyzoq4vmtL3lsIjlsazlvrXmiY3poafllY/kuIHntJToloflhY3ku5jo?=
 =?UTF-8?B?srvlsIjnt5o6MDgwMC04ODExODY=?=";

foreach ($case as $key => $string)
	//echo mb_convert_encoding($string, 'UTF-8', 'GB2312');
	//echo base64_decode($string);
	//echo iconv('GBK', 'UTF-8', $string);
	echo $key . ":\n" . decode_mime_string(combine($string)) . "\n\n";
	//echo $key . ":\n" . edecode_mime_string($string) . "\n\n";

