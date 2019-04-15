<?
/* DOC_NO:A2-090507-00027 */
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// ImageReport 圖片產生物件
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
class ImageReport
{
	var $X;//圖片大小X軸
	var $Y;//圖片大小Y軸
	var $R;//背影色R值
	var $G;//...G.
	var $B;//...B.
	var $bTransparent;//是否透明1或0
	var $rcImage;//圖片對像
	var $nColorBk;
	//-------------------
	var $nReportType;//圖表類型,1為垂直條狀圖 2為水平條狀圖 3為折線形 4為餅圖
	var $nBorder;//距離
	//-------------------
	var $nFontSize = 2;//字體大小
	var $nFontColor = 64;//字體顏色
	var $sUseTtfFont;
	var $nPieType = 0x01;//&1=> 圓邊
	var $nPieFlightHight = 18;
	var $StripHeight = 3;//條狀圖突起高度
	
	var $aRowDatas;
	var $aRowMsgs;
	var $aRowColors;
	var $nMaxValue = 0;
	var $nTotal = 0;
	var $nAllocateFontColor;
	var $bLight = true;
 	var $ainfo;
	function setImage($SizeX, $SizeY, $R, $G, $B, $bTransparent)
	{
		$this->X = $SizeX;
		$this->Y = $SizeY;
		$this->R = $R;
		$this->G = $G;
		$this->B = $B;
		$this->bTransparent = $bTransparent;
	}
	function setItem($nReportType, $Border, $aDatas, $nTotal = 0, $aRowMsgs = null, $ainfo = null)
	{
		$this->nReportType = $nReportType;
		$this->nBorder = $Border;
		$this->aRowDatas = $aDatas;
		$this->aRowMsgs = $aRowMsgs;
		$this->nTotal = $nTotal;
		$this->ainfo = $ainfo;
		$l = count($this->aRowDatas);

		if(2 == $this->nReportType)//水平條狀圖
			$this->Y = $Border * ($l + 1) * 2 + $Border;
		else if(4 == $this->nReportType)
		{//餅圖 $nSize = $this->X - $this->nBorder - $nPieFlightHight * 3;
			if($this->nTotal < 0)
				$this->nTotal = 0.45;
			else if($this->nTotal > 1)
				$this->nTotal = 1;
			//in imagePieChart() $nDis2 = 4; $nY1 = $this->nBorder * 3 + $nSizeY + $nPieFlightHight * 3;
			$this->Y = $this->X * $this->nTotal + ($Border + 4) * $l + $this->nBorder * 4 + $this->nPieFlightHight * 3;
		}
		else if(3 == $this->nReportType){
			$this->X = $l * 5 + $Border * 2 + 6 * 7;//時間折線圖
		//0:Day unit=15 mins; 1:Week unit=2 hrs; 2:Month unit=6 hrs; 3:Quarter unit=1 day; 4:1/2 Year unit=2 days; 5:Year unit=3 days
		}
		$this->nMaxValue = 0;
		for ($i = 0; $i < $l; $i++)
		{
			if($this->nMaxValue < floatval($this->aRowDatas[$i]))
				$this->nMaxValue = floatval($this->aRowDatas[$i]);
		}
	}

	function printReport($sSaveName)
	{//建立完整圖片
		if(!$this->nMaxValue) $this->nMaxValue = 1;
		$this->rcImage = @imagecreatetruecolor($this->X,$this->Y);
		$this->nColorBk = imagecolorallocate($this->rcImage, $this->R, $this->G, $this->B);
		imagefilledrectangle($this->rcImage, 0, 0, $this->X, $this->Y, $this->nColorBk);
		if(1 == $this->bTransparent)
			imagecolortransparent($this->rcImage,$this->nColorBk);//背景透明
		$this->nAllocateFontColor = imagecolorallocate($this->rcImage, $this->nFontColor, $this->nFontColor, $this->nFontColor);
		if(3 == $this->nReportType){
			$this->printBkLine3();//時間折線圖
		}else if(1 == $this->nReportType)
			$this->printBkLine();

		switch($this->nReportType)
		{
		case "1": $this->imageStripVer(); break;//繪垂直條狀圖///NOT YET
		case "2": $this->imageStripHor(); break;//繪水平條狀圖
		case "3": $this->imageTimePolyline(); break;//時間折線圖
		case "4": $this->imagePieChart(); break;//圓形圖
		default: break;
		}

		if(1 == $this->nReportType || 3 == $this->nReportType)
			$this->printXY();
		imagepng($this->rcImage, $sSaveName);
		imagedestroy($this->rcImage);
	}
	function printBkLine()
	{
		$w = $nFontSize * 3;
		$nStep = 5;
		$color = imagecolorallocate($this->rcImage, 218, 218, 188);
		$color2 = imagecolorallocate($this->rcImage, 198, 198, 145);
		$nMax = $this->X - $this->nBorder * 3;
		$nX = $this->nBorder;
		$nXadd = (int)($nMax / $this->nBorder);
		if($this->nMaxValue < $nXadd)
			$nXadd = $this->nMaxValue;
		$nAdd = $nValue = $this->nMaxValue / $nXadd * $nStep;
		$nXadd = $nMax / $nXadd;
		$nMax = $this->X - $this->nBorder * 2;
		$nY1 = $this->Y - $this->nBorder;
		$nY2 = $this->nBorder + 2;

		for($i = 1; $nX <= $nMax; $i++)
		{
			$nX += $nXadd;
			if(($i % $nStep) == 0)
			{
				imagestring($this->rcImage, $this->nFontSize, $nX - $w, $nY1 + 2, round($nValue, 0), $this->nAllocateFontColor);
				$c = $color2;
				$nValue += $nAdd;
			}
			else
				$c = $color;
			ImageLine($this->rcImage, $nX, $nY1, $nX, $nY2, $c);
		}
	}
	function printBkLine3()
	{//For 時間折線圖 網格
		$w = imagefontwidth($this->nFontSize);
		$color = imagecolorallocate($this->rcImage, 227, 227, 244);
		$colorHeavy = imagecolorallocate($this->rcImage, 209, 209, 237);
		$colorFont = imagecolorallocate($this->rcImage, 170, 170, 213);
		$nX = $this->nBorder + 6 * $w;
		$nY1 = $this->Y - $this->nBorder;
		$nY2 = $this->nBorder + 2;
		switch($this->nTotal)
		{//0:Day unit=15 mins; 1:Week unit=2 hrs; 2:Month unit=6 hrs; 3:Quarter unit=1 day; 4:1/2 Year unit=2 days; 5:Year unit=3 days
		case 5: $nStep = 5; $sUnits = '6 days'; $nAdd = 30; $nMax = 61; break;//Year
		case 4: $nStep = 4; $sUnits = '4 days'; $nAdd = 20; $nMax = 45; break;// 1/2 Year
		case 3: $nStep = 5; $sUnits = '2 days'; $nAdd = 10; $nMax = 46; break;//Quarter
		case 2: $nStep = 10; $sUnits = '12 hrs'; $nAdd = 5; $nMax = 62; break;//Month
		case 1: $nStep = 6; $sUnits = '4 hrs'; $nAdd = 1; $nMax = 42; break;//Week
		case 6://query
			$info = explode(",", $this->ainfo);
			if($info[1] >= 1){
				$sUnits = $info[1].' hrs' ;
				$nAdd = 1;
				$nStep = ceil(24*$nAdd/$info[1]);
				$nMax = ceil($info[0]/2);
				break;
			}
		default: $nStep = 2; $sUnits = '30 mins'; $nAdd = 1; $nMax = 48; break;//day
		}
		
		$nValue = $nAdd;
		$nAddX = 10;// all = 5 pixel / unit, $nAddX = 10 ==> 2 units ==> 30 min

		for($i = 1; $i <= $nMax; $i++)
		{
			$nX += $nAddX;
			if(($i % $nStep) == 0)
			{
				imagestring($this->rcImage, $this->nFontSize, $nX - $w, $nY1 + 2, (int)$nValue, $this->nAllocateFontColor);
				$nValue += $nAdd;
				$c = $colorHeavy;
			}
			else
				$c = $color;
			ImageLine($this->rcImage, $nX, $nY1, $nX, $nY2, $c);
		}

		$nStep = 5;
		$nAdd = $nValue = $this->nMaxValue / ( ($this->Y - $this->nBorder * 3) / $this->nBorder) * $nStep;
		$nMin = $this->nBorder * 2;
		$nX1 = $this->nBorder + 6 * $w;
		$nX2 = $this->X - $this->nBorder - 2;
		$nY = $this->Y - $this->nBorder;
		$nMinusY = $this->nBorder;
		for($i = 1; $nY >= $nMin; $i++)
		{
			$nY -= $nMinusY;
			if(($i % $nStep) == 0)
			{
				$sVal = (int)$nValue;
				imagestring($this->rcImage, $this->nFontSize, (6 - strlen($sVal)) * $w + $this->nBorder - 2, $nY - $w, $sVal, $this->nAllocateFontColor);
				$nValue += $nAdd;
				$c = $colorHeavy;
			}
			else
				$c = $color;
			ImageLine($this->rcImage, $nX1, $nY, $nX2, $nY, $c);
		}
		$sUnits = "($sUnits)";
		$l = strlen($sUnits) + 1;
		imagestring($this->rcImage, $this->nFontSize, $this->X - $this->nBorder - $l * $w, $this->nBorder + 2, $sUnits, $colorFont);
	}

	function printXY()
	{//-----------XY坐標軸
		$rImg = $this->rcImage;
		$color = $colorFont;
		$y = $this->Y - $this->nBorder;
		if(3 == $this->nReportType)
		{//時間折線圖
			$x = $this->nBorder + 6 * 6;
			imageline($rImg, $x, $y, $this->X - $this->nBorder, $y, $color);//x軸
			imageline($rImg, $x, $this->nBorder, $x, $y, $color);//y軸
			imagestring($rImg, $this->nFontSize, $x - 6, $this->Y - $this->nBorder + 2, "0", $this->nAllocateFontColor);
			return;
		}
		imagestring($rImg, $this->nFontSize, $this->nBorder - 6, $this->Y - $this->nBorder + 2, "0", $this->nAllocateFontColor);
		imageline($rImg, $this->nBorder, $y, $this->X - $this->nBorder, $y, $color);//x軸

		imageline($rImg, $this->nBorder, $this->nBorder, $this->nBorder, $y, $color);//y軸

/*
		$rulerY = $rulerX = "";
		$rulerY = $this->Y - $this->nBorder;
		for($i = 0; $rulerY > $this->nBorder * 2; $i++)
		{//Y軸上刻度
			$rulerY=$rulerY-$this->nBorder;
			imageline($rImg, $this->nBorder, $rulerY, $this->nBorder-2, $rulerY, $color);
			$i++;
		}
		$rulerX = $rulerX + $this->nBorder;
		for($i = 0; $rulerX < ($this->X - $this->nBorder * 2); $i++)
		{//X軸上刻度
			$rulerX = $rulerX+$this->nBorder;
			imageline($rImg, $rulerX, $this->Y - $this->nBorder, $rulerX, $this->Y - $this->nBorder + 2, $color);
		}
		*/
	}
	function imageStripHor()
	{//繪水平條狀圖
		$nTextWd = imagefontwidth($this->nFontSize);
		$nBr = $this->StripHeight;//Distance of shadow offset
		$nBr1 = 1;//Width of strip border
		$nCrStep = $this->bLight ? 6: -6;

		$nX = $this->nBorder;
		$rImg = $this->rcImage;
		$nFontSize = $this->nFontSize;
		$nFontWidth = $nFontSize * 2;
		$nYorigin = $nY = $this->Y - $this->nBorder * 2;
		$nStripMaxLen = $this->X - $nX * 3;
		$nLen = count($this->aRowDatas);
		srand((double)microtime()*1000000);
		$aTmp = array();
		$this->printBkLine();
		for($i = 0; $i < $nLen; $i++, $nY -= ($nX * 2))
		{
			$this->getRandColor($rImg, $nCrStep, $aRGB, $color, $color1, $color2);
			$nValue = $this->aRowDatas[$i];
			if($bOverMax = (floatval($nValue) > floatval($this->nMaxValue)))
				$nStripLen = (int)($nStripMaxLen + $nX * 1.2);//條圖長度
			else
				$nStripLen = (int)($nStripMaxLen * ($nValue / $this->nMaxValue));//條圖長度
			$this->drawStripSpec3($rImg, $nX + $nBr1 + 1, $nY - $nX, $nStripLen + $nX, $nY, $aRGB, 8);//Draw shadow
			$aTmp[] = array($nValue, $nStripLen, $aRGB, $color, $color1, $color2, $bOverMax);
		}
		$this->printXY();
		for($nY = $nYorigin, $i = 0; $i < $nLen; $i++, $nY -= ($nX * 2))
		{
			$aTmpItem = $aTmp[$i];
			$nValue = $aTmpItem[0];
			$nStripLen = $aTmpItem[1];//條圖長度
			$aRGB = $aTmpItem[2]; $color = $aTmpItem[3]; $color1 = $aTmpItem[4]; $color2 = $aTmpItem[5];
			$bOverMax = $aTmpItem[6];
			$this->drawStripSpec4($rImg, $nX + 1, $nY - $nX, $nStripLen + $nX, $nY, $this->bLight? $color: $color1, $nBr, $bOverMax);//Draw 3d side
			$this->drawStripSpec1($rImg, $nX + 1 - $nBr, $nY - $nX - $nBr, $nStripLen + $nX - $nBr, $nY - $nBr, $aRGB, $nCrStep, $nBr, $bOverMax);
			$n = ($nX - ($nFontSize * 4) - 2) / 2;
			$nTextY = $nY - $nX + $n - $nBr;
			if($this->nTotal)
				$sValue = sprintf(", %.2f%%", $nValue / $this->nTotal * 100.0);
			else
				$sValue = "";
			if($str = $this->aRowMsgs[$i])
				$str = ($nLen - $i) . ": ${str} (" . trim($nValue) . "${sValue})";
			else
				$str = ($nLen - $i) . ": " . trim($nValue). "$sValue";
			if($nCrStep > 0)
			{
				$this->drawText($nX + $nBr * 2, $nTextY, $str, $color1);
				continue;
			}
			$ll = $nStripLen;
			$nStripLen = (int)(($nStripLen - $nFontWidth) / $nTextWd);
			$l = strlen($str);

			if($ll < $nFontWidth)
				$this->drawText($nX + $nFontWidth, $nTextY, $str, $color);
			else if($l > $nStripLen)
			{
				if($nStripLen > 0)
					$this->drawText($nX + $nFontWidth, $nTextY, substr($str, 0, $nStripLen), $color2);
				$this->drawText($nX + $nFontWidth + $nStripLen * $nTextWd, $nTextY, substr($str, $nStripLen), $color);
			}
			else
				$this->drawText($nX + $nFontWidth, $nTextY, $str, $color2);
		}
	}
	function drawStripSpec3($rImg, $nX1, $nY1, $nX2, $nY2, $aRGB, $nCrStep)
	{//Draw shadow
		$nCm = 32;//Color more light for shadow
		$cX = 40; $cR = $this->R - $cX; $cG = $this->G - $cX; $cB = $this->B - $cX;
		$aRGBx[0] = ($cR - $aRGB[0] - $nCm) / $nCrStep;
		$aRGBx[1] = ($cG - $aRGB[1] - $nCm) / $nCrStep;
		$aRGBx[2] = ($cB - $aRGB[2] - $nCm) / $nCrStep;
		$aRGB[0] = $cR;
    $aRGB[1] = $cG;
    $aRGB[2] = $cB;
		for(; $nCrStep > 0; $nCrStep--)
		{
			$color = imagecolorallocatealpha($rImg, $aRGB[0], $aRGB[1], $aRGB[2], 107);
			imagefilledrectangle($rImg, $nX1 - $nCrStep, $nY1, $nX2 + $nCrStep, $nY2 + $nCrStep, $color);
			$aRGB[0] -= $aRGBx[0];
			$aRGB[1] -= $aRGBx[1];
			$aRGB[2] -= $aRGBx[2];
		}
	}
	function drawStripSpec4($rImg, $nX1, $nY1, $nX2, $nY2, $color, $nStep, $bOverMax)
	{//Draw 3d side
		if($nX2 < $nX1)
			$nX2 = $nX1;
		for(; $nStep > 0; $nStep--)
			imagefilledrectangle($rImg, $nX1 - $nStep, $nY1 - $nStep, $nX2 - $nStep, $nY2 - $nStep, $color);
	}
	function drawStripSpec1($rImg, $nX1, $nY1, $nX2, $nY2, $aRGB, $nCrStep, $nBr = 0, $bOverMax = false)
	{//draw strip body
		if($nX2 < $nX1)
			$nX2 = $nX1;
		$nOrigin = array($nY1, $nY2, $aRGB[0], $aRGB[1], $aRGB[2]);
		for(; $nY2 >= $nY1; $nY1++, $nY2--)
		{
			$color = imagecolorallocate($rImg, $aRGB[0], $aRGB[1], $aRGB[2]);
			imagefilledrectangle($rImg, $nX1, $nY1, $nX2, $nY2, $color);
			$aRGB[0] += $nCrStep; if($aRGB[0] > 255)$aRGB[0] = 255; else if($aRGB[0] < 0)$aRGB[0] = 0;
			$aRGB[1] += $nCrStep; if($aRGB[1] > 255)$aRGB[1] = 255; else if($aRGB[1] < 0)$aRGB[1] = 0;
			$aRGB[2] += $nCrStep; if($aRGB[2] > 255)$aRGB[2] = 255; else if($aRGB[2] < 0)$aRGB[2] = 0;
		}
		$nY1 = $nOrigin[0];
		$nY2 = $nOrigin[1];
		$nD1 = ($nY2 - $nY1 + $nBr + 1) / 5;
		$color = imagecolorallocatealpha($rImg, 255, 255, 255, 100);
		$nDw = 3;
		imagefilledrectangle($rImg, $nX1 + 2, $nY1 + 1, $nX2 - $nDw, $nY1 + ($nY2 - $nY1) / 2 - 1, $color);
		if(!$bOverMax)
			return;
		$nColorBk = imagecolorat($rImg, $nX2 + $nBr, $nY2);
		$color = imagecolorat($rImg, $nX2, $nY2 + 1);
		$nD2 = $nD1;
		$nD3 = $nBr - 2;
		$nX1 = $nX2;
		$aPt = array($nX1,$nY1, $nX1-$nD2,$nY1+$nD1, $nX1,$nY1+$nD1*2, $nX1-$nD2,$nY1+$nD1*3, $nX1+$nBr,$nY2+$nBr, $nX1+$nBr,$nY1+$nBr);
		imagefilledpolygon($rImg, $aPt, 6, $this->nColorBk);
		imagefilledpolygon($rImg, $aPt, 6, $nColorBk);
		$aPt = array($nX1,$nY1, $nX1-$nD2,$nY1+$nD1, $nX1-$nD2+$nD3,$nY1+$nD1+$nD3, $nX1+$nD3,$nY1+$nD3);
		imagefilledpolygon($rImg, $aPt, 4, $color);
		$aPt = array($nX1,$nY1+$nD1*2, $nX1-$nD2,$nY1+$nD1*3, $nX1-$nD2+$nD3,$nY1+$nD1*3+$nD3, $nX1+$nD3,$nY1+$nD1*2+$nD3);
		imagefilledpolygon($rImg, $aPt, 4, $color);
	}

	function drawText($nX, $nY, $str, $color)
	{
		$str = mb_convert_encoding($str, 'UTF-8', 'BIG-5');
		if($this->sUseTtfFont)
		{
			$nFontSize = $this->nFontSize * 6;
			imagettftext($this->rcImage, $nFontSize, 0, $nX, $nY + $nFontSize, $color, $this->sUseTtfFont, $str);
		}
		else
			imagestring($this->rcImage, $this->nFontSize, $nX, $nY, $str, $color);
	}

	function getRandColor($rImg, $nCrStep, &$aRGB, &$color, &$color1, &$color2, $aColor = null)
	{
		$nCm = 68;//Color more light for shadow
		$nCm1 = 32;//Color more dark for pie border
		srand((double)microtime()*1000000);
		if($nCrStep > 0)//bLight
		{
			$nRandColor = array('From' => 68, 'To' => 158);
			$nCm2 = $nCm / 2;
		}
		else
		{
			$nRandColor = array('From' => 92, 'To' => 178);
			$nCm2 = $nCm / 2;
		}
		if(is_array($aColor))
		{
			$nRandColor['From'] = $nCm1;
			$nRandColor['To'] = 255 - $nCm;
			$R = $aColor[0]; if($R < $nRandColor['From']) $R = $nRandColor['From']; else if($R > $nRandColor['To']) $R = $nRandColor['To'];
			$G = $aColor[1]; if($G < $nRandColor['From']) $G = $nRandColor['From']; else if($G > $nRandColor['To']) $G = $nRandColor['To'];
			$B = $aColor[2]; if($B < $nRandColor['From']) $B = $nRandColor['From']; else if($B > $nRandColor['To']) $B = $nRandColor['To'];
		}
		else
		{
			$R = rand($nRandColor['From'], $nRandColor['To']);
			$G = rand($nRandColor['From'], $nRandColor['To']);
			$B = rand($nRandColor['From'], $nRandColor['To']);
		}
		$color = imagecolorallocate($rImg, $R, $G, $B);//Color for main
		$color1 = imagecolorallocate($rImg, $R - $nCm1, $G - $nCm1, $B - $nCm1);//Color more dark for border
		$color2 = imagecolorallocate($rImg, $R + $nCm, $G + $nCm, $B + $nCm);//Color more light for shadow
		$aRGB = array($R, $G, $B);
	}

	function imagePieChart()
	{
		$nYscale = $this->nTotal;
		$nPieFlightHight = $this->nPieFlightHight;
		$fMoreLightAdd = 0.55;
		$nMoreLightMod = 6;
		$nBlockSize = 10;
		$nDis1 = 2;
		$nDis2 = 4;
		$nWidthOfChars = 8;
		$nCrStep = $this->bLight ? 2: -2;
		$nTextWd = imagefontwidth($this->nFontSize);
		$nThickness = $nYscale < 1? (0.6 - ($nYscale - 0.4)) / 0.6 * 12 + 16: 0; 

		$nSize = $this->X - $this->nBorder * 2 - $nPieFlightHight * 2;
		$nSizeY = $nSize * $nYscale;
		$nX = $this->X / 2;
		$nY = $nSizeY / 2 + $this->nBorder + $nPieFlightHight;
		$rImg = $this->rcImage;
		$nFontSize = $this->nFontSize;
		$nY1 = $this->nBorder * 3 + $nSizeY + $nPieFlightHight * 3;
		$textBackground = imagecolorallocate($rImg, 251, 251, 251);//Color for text background
		$aDists['Height'] = $this->nBorder * 1.35;
		$aDists['Width'] = $this->X - $this->nBorder * 2;
		$aDists['Width3'] = $nTextWd * $nWidthOfChars;//Width of 8 characters in 3rd column  
		$aDists['Width2'] = $nTextWd * $nWidthOfChars;//Width of 8 characters in 2nd column
		$aDists['Width1'] = $aDists['Width']- $aDists['Width2'] - $aDists['Width3'] - $nDis2 * 2;//Width of 1nd column

		$nLen = count($this->aRowDatas) - 1;
		$nTotal = 0;
		for($i = 0; $i <= $nLen; $i++)
			$nTotal += $this->aRowDatas[$i];
		if(!$nTotal)
			return;
		srand((double)microtime()*1000000);
		$nAngTo = 0;
		$aTmp = array();
		for($nAngFrom = $i = 0; $i <= $nLen; $i++, $nY1 += $aDists['Height'])
		{
			$this->getRandColor($rImg, $nCrStep, $aRGB, $color, $color1, $color2, $this->aRowColors[$i]);
			$nValue = $this->aRowDatas[$i];
			$fPercent = $nValue / $nTotal;//Pie scale

			$xTxt = $this->nBorder + $nBlockSize + $nTextWd + $nDis2;
			imagefilledrectangle($rImg, $this->nBorder, $nY1, $this->nBorder + $aDists['Width1'], $nY1 + $this->nBorder, $textBackground);
			$this->drawStripSpec1($rImg, $this->nBorder + $nDis2, $nY1 + $nDis1 + $nDis2
				, $this->nBorder + $nDis2 + $nBlockSize, $nY1  + $nDis1 + $nDis2 + $nBlockSize, $aRGB, $nCrStep);
			$this->drawText($xTxt, $nY1 + $nDis2, $this->aRowMsgs[$i], $color1);
			$x = $this->nBorder + $aDists['Width1'] + $nDis2;
			$str = trim($nValue);
			$xTxt = $x + ($nWidthOfChars - strlen($str)) * $nTextWd;
			imagefilledrectangle($rImg, $x, $nY1, $x + $aDists['Width2'], $nY1 + $this->nBorder, $textBackground);
			$this->drawText($xTxt, $nY1 + $nDis2, $str, $color1);
			$x += ($aDists['Width2'] + $nDis2);
			$str = sprintf("%.2f%%", $fPercent * 100.0);
			$xTxt = $x + ($nWidthOfChars - strlen($str)) * $nTextWd;
			imagefilledrectangle($rImg, $x, $nY1, $x + $aDists['Width3'], $nY1 + $this->nBorder, $textBackground);
			imagestring($rImg, $nFontSize, $xTxt, $nY1 + $nDis2, $str, $color1);
			$this->drawText($xTxt, $nY1 + $nDis2, $str, $color1);
			//if($fPercent < 0.0032678)
			//	continue;
			
			if($nLen <= 2) $nAngFrom = $nAngTo;
			else $nAngFrom = $nAngTo - 2;
			//$nAngFrom = $nAngTo;
			if($i < $nLen)
				$nAngTo = $nAngTo + $fPercent * 360;
			else
				$nAngTo = 360;
			$n = $nPieFlightHight;
			$nCrMinsR = ($this->R - $aRGB[0]) / $n * $fMoreLightAdd;
			$nCrMinsG = ($this->G - $aRGB[1]) / $n * $fMoreLightAdd;
			$nCrMinsB = ($this->B - $aRGB[2]) / $n * $fMoreLightAdd;
			$aRGBx[0] = $this->R; $aRGBx[1] = $this->G; $aRGBx[2] = $this->B;
			$nYadd = $nYscale > 0.95 ? 0: $nPieFlightHight + $nThickness;
			for($x = $nPieFlightHight * 2; $n > 0; $x -= 2, $n--)
			{
				$aRGBx[0] -= $nCrMinsR; $aRGBx[1] -= $nCrMinsG; $aRGBx[2] -= $nCrMinsB;
				$color3 = imagecolorallocate($rImg, $aRGBx[0], $aRGBx[1], $aRGBx[2]);
				imageFilledArc($rImg, $nX, $nY + $nYadd, $nSize + $x, ($nSize + $x) * $nYscale, $nAngFrom, $nAngTo, $color3, IMG_ARC_PIE);
			}
			$aTmp[] = array($nAngFrom, $nAngTo, $color, $aRGB, $color1);
		}
		$nLen = count($aTmp) - 1;
		$aItem = $aTmp[$nLen];
		$bRedraw = $aItem[0] < 90;
		$this->drawPieSpec2($rImg, $nX, $nY, $nSize, $nSizeY, $aItem[0], $aItem[1], $aItem[4], $nThickness);
		for($i = 0; $i < $nLen; $i++)
		{
			$aItem = $aTmp[$i];
			$this->drawPieSpec2($rImg, $nX, $nY, $nSize, $nSizeY, $aItem[0], $aItem[1], $aItem[4], $nThickness);
			$n = $i;
			while($n > 0 && $aItem[0] > 89 && $aItem[0] < 220)
			{
				$aItem = $aTmp[--$n];
				if(($nTo = $aItem[1] - 2) > 230)
					$nTo = 230;
				$this->drawPieSpec2($rImg, $nX, $nY, $nSize, $nSizeY, $aItem[0], $nTo, $aItem[4], $nThickness);
				if($aItem[0] < 90 && ($aItem[1] - 2) < 90)
					$bRedrawNext = $n + 1;
			}
		}
		if($bRedraw)
		{
			$aItem = $aTmp[$nLen];
			$this->drawPieSpec2($rImg, $nX, $nY, $nSize, $nSizeY, $aItem[0], $aItem[0] + 15, $aItem[4], $nThickness);
		}
		foreach($aTmp as $aItem)
		{
			$nAngFrom = $aItem[0];
			$nAngTo = $aItem[1];
			$color = $aItem[2];
			$aRGB = $aItem[3];
			$this->drawPieSpec1($rImg, $nX, $nY, $nSize, $nYscale, $nAngFrom, $nAngTo, $aRGB, $nCrStep);
			$color = imagecolorallocatealpha($rImg, $aRGB[0] += $nMoreLightMod, $aRGB[1] += $nMoreLightMod, $aRGB[2] += $nMoreLightMod, 72);
			$nSize1 = $nSize + 2;
			for($x = 3; $x > -1; $x--)
				imageFilledArc($rImg, $nX, $nY, $nSize1 - $x, ($nSize1 - $x) * $nYscale, $nAngFrom, $nAngTo, $color, IMG_ARC_NOFILL | IMG_ARC_PIE | IMG_ARC_EDGED);
		}
	}
	function drawPieSpec1($rImg, $nX, $nY, $nSize1, $nYscale, $nAngFrom, $nAngTo, $aRGB, $nCrStep)
	{//draw shadow
		$nSize2 = $nSize1 * $nYscale;
		$nDist = $nSize1 * 0.85;
		$bWaitSwitch = false;//if true will draw laps
		for(; $nSize1 > 1 && $nSize2 > 1; $nSize1 -= 6, $nSize2 = $nSize1 * $nYscale)
		{
			$color = imagecolorallocate($rImg, $aRGB[0], $aRGB[1], $aRGB[2]);
			imageFilledArc($rImg, $nX, $nY, $nSize1, $nSize2, $nAngFrom, $nAngTo, $color, IMG_ARC_PIE);
			$aRGB[0] += $nCrStep; if($aRGB[0] > 255)$aRGB[0] = 255; else if($aRGB[0] < 0)$aRGB[0] = 0;
			$aRGB[1] += $nCrStep; if($aRGB[1] > 255)$aRGB[1] = 255; else if($aRGB[1] < 0)$aRGB[1] = 0;
			$aRGB[2] += $nCrStep; if($aRGB[2] > 255)$aRGB[2] = 255; else if($aRGB[2] < 0)$aRGB[2] = 0;
			if($bWaitSwitch && $nSize1 < $nDist)
			{
				$bWaitSwitch = false;
				$nCrStep = 0 - $nCrStep;
			}
		}
	}
	function drawPieSpec2($rImg, $nX, $nY, $nSize, $nSizeY, $nAngFrom, $nAngTo, $color, $nThickness)
	{//draw body
		if($nAngFrom > 0 && $nAngTo <= $nAngFrom)
			return;
		$n = $nThickness / 2;
		$nRd = 0;
		for($x = $nThickness; $x > 0; $x--)
		{
			imageFilledArc($rImg, $nX, $nY + $x, $nSize + $nRd, $nSizeY + $nRd, $nAngFrom, $nAngTo, $color, IMG_ARC_PIE);
			if(!($this->nPieType & 1))
				continue;
			if($x > $n)
				$nRd++;
			else
				$nRd--;
		}
	}

	function imageTimePolyline()
	{//繪時間折線圖
		$nTextWd = imagefontwidth($this->nFontSize);
		$nBr = 1;//Distance of shadow offset
		$nBr1 = 6;//Width of strip border
		$nColorLight = 68;
		$nCm1 = 24;//Color more dark for total
		$nCrStep = 5;

		$nX = $this->nBorder + 6 * $nTextWd + 1;
		$nY = $this->Y - $this->nBorder;
		$nStripMax = $this->Y - $this->nBorder * 3;
		$nLen = count($this->aRowDatas);
		$rImg = $this->rcImage;
		$nFontSize = $this->nFontSize;
		srand((double)microtime()*1000000);
		$this->getRandColor($rImg, $nCrStep, $aRGB, $color, $color1, $color2);
		$aRGB[0] += $nColorLight;
		$aRGB[1] += $nColorLight;
		$aRGB[2] += $nColorLight;

		$color = imagecolorallocate($rImg, $aRGB[0] - $nCm1, $aRGB[1] - $nCm1, $aRGB[2] - $nCm1);
		for($i = 0; $i < $nLen; $i++, $nX += 5)
		{
			$nValue = $this->aRowDatas[$i];
			$nStripLen = (int)($nStripMax * ($nValue / $this->nMaxValue));//條圖長度
			$ny = $nY - $nStripLen;
			if($this->aRowMsgs)
				$ny2 = $nY - (int)($nStripMax * ($this->aRowMsgs[$i] / $this->nMaxValue));
			else
				$ny2 = -1;
			$this->drawTimePolylineSpec1($rImg, $nX, $ny, $ny2, $nY, $aLastPt, $aRGB, $nCrStep, $color);
			$aLastPt = array($nX, $ny, $ny2);
		}
	}

	function drawTimePolylineSpec1($rImg, $nX, $nY1, $nYx, $nY2, $aLastPt, $aRGB, $nCrStep, $color1)
	{
		$nAlpha = 75 - ($aRGB[0] + $aRGB[1] + $aRGB[2] - 520) / 8;
		$color2 = imagecolorallocatealpha($rImg, $aRGB[0], $aRGB[1], $aRGB[2], $nAlpha);
		for(; $nY2 >= $nY1; $nY2 -= $nCrStep)
		{
			$aRGB[0] += $nCrStep; if($aRGB[0] > $this->R)$aRGB[0] = $this->R;
			$aRGB[1] += $nCrStep; if($aRGB[1] > $this->G)$aRGB[1] = $this->G;
			$aRGB[2] += $nCrStep; if($aRGB[2] > $this->B)$aRGB[2] = $this->B;
			$color = imagecolorallocate($rImg, $aRGB[0], $aRGB[1], $aRGB[2]);
			imagefilledrectangle($rImg, $nX, $nY2 - $nCrStep - 1, $nX + 5, $nY2, $color);
		}

		if(is_array($aLastPt))
		{
			if($nYx > -1)
			{
				imagesetthickness($rImg, 2);
				imageline($rImg, $nX, $nYx, $aLastPt[0], $aLastPt[2], $color2);
				imagesetthickness($rImg, 1);
			}
			imageline($rImg, $nX, $nY1, $aLastPt[0], $aLastPt[1], $color1);
		}
	}
}
//###***===------- END ImageReport  圖片產生物件 --------------------------------------------===***###
?>