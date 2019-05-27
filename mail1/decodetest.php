<?php
include_once("tools/mail_analyzing_tools.php");

$case["usual"] = "=?utf-8?B?QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6uueNjumHkemAmuefpQ==?=";

$case["plain_text"] = "Fwd: SFExpress | Invoice | Shipping | Tracking |";

$case["mixed"] = "Subject: =?utf-8?B?QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6uueNjumHkemAmuefpQ==?=";

$case["q_mark_included"] = "=?UTF-8?Q?You_know_what's_hot_in_China??=";

$case["incomplete_byte_1"] = "=?utf-8?B?QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6?=
 =?utf-8?B?uueNjumHkemAmuefpQ==?=";

$case["incomplete_byte_2"] = "=?UTF-8?B?44CQ5oqY5YO55Yi455m86YCB6YCa55+l44CR5pyD5ZOh5pel5pyA6auY54++?=
 =?UTF-8?B?5oqYMjAw77yB5aSP5aSp5Yiw5LqG546p5rC06YCZ5qij546p5LiA5aSP77yB?=
 =?UTF-8?B?6YCg5Z6L5rWu5o6S44CA5rOz5rGg5pyA5pyJ5Z6LL+OAkOael+S4ieebiuWI?=
 =?UTF-8?B?t+WFt+e1hOOAke+8jeWkj+aXpeS/nemkiumdoOmAmee1hO+8gS/lqr3lqr3l?=
 =?UTF-8?B?ronlv4PluavmiYvjgJDml6XmnKzms6Hms6HnjonjgJE=?=";

// org_spam_20190521/860/2019/05/03/957292
$case["incomplete_byte_3"] = "=?UTF-8?B?UmU64piF6L2J6IG35r2u5b615YSq6LOq5Lq65omN54++5Zyo5Y+q6KaBMTgw?=
 =?UTF-8?B?MOWFgyzoq4vmtL3lsIjlsazlvrXmiY3poafllY/kuIHntJToloflhY3ku5jo?=
 =?UTF-8?B?srvlsIjnt5o6MDgwMC04ODExODY=?=";

$case["incomplete_byte_4"] = "Subject: =?UTF-8?B?UmU64piF6L2J6IG35r2u5b615YSq6LOq5Lq65omN54++5Zyo5Y+q6KaBMTgw?= =?UTF-8?B?MOWFgyzoq4vmtL3lsIjlsazlvrXmiY3poafllY/kuIHntJToloflhY3ku5jo?= =?UTF-8?B?srvlsIjnt5o6MDgwMC04ODExODY=?=";

$case["too_long"] = "=?UTF-8?Q?=E5=A4=96=E8=B2=BF=E5=8D=94=E6=9C=83=E9=AB=98=E9=9B=84?= =?UTF-8?Q?=E8=BE=A6=E4=BA=8B=E8=99=95=E6=95=AC=E9=82=80=7E=E3=80=8C?= =?UTF-8?Q?=E6=96=B0=E5=8D=97=E5=90=91=E5=B9=B9=E9=83=A8=E5=9F=B9=E8=A8=93?= =?UTF-8?Q?=E7=8F=AD=E3=80=8D-=E5=BF=AB=E9=80=9F=E6=8E=8C=E6=8F=A1?= =?UTF-8?Q?=E6=96=B0=E5=8D=97=E5=90=91=E5=9C=8B=E5=AE=B6=E6=A5=AD/?= =?UTF-8?Q?=E5=BB=A0=E5=8B=99=E6=A6=82=E6=B3=81=E7=9A=84=E6=9C=80=E4=BD=B3?= =?UTF-8?Q?=E9=80=94=E5=BE=91!?=";

$case["incomplete_byte_5"] = "Subject:  
 =?UTF-8?q?***=E5=9E=83=E5=9C=BE=E9=83=B5=E4=BB=B6***=20=AD=BB=A4?=
 =?UTF-8?q?=F4=A4]=ACO=A5=CD=AC=A1=A4=A4=AA=BA=B6=CA=B1=A1=C3=C4?=";

foreach ($case as $key => $string) {
	//echo mb_convert_encoding($string, 'UTF-8', 'GB2312');
	//echo base64_decode($string);
	//echo iconv('GBK', 'UTF-8', $string);
	//echo $key . ":\n" . combine($string) . "\n\n";
	echo $key . ":\n" . decode_mixed_string($string) . "\n\n";
	//echo $key . ":\n" . edecode_mime_string($string) . "\n\n";
}

return;

