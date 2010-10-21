<?php

	function CreateThumbnailMPR($ifname, $ofname, $dstWidth)
	{
		$img = new Imagick($ifname);
		$srcWidth  = $img->getImageWidth();
		$srcHeight = $img->getImageHeight();
	
		$dstHeight = (int)($COL_WIDTH / $srcWidth * $srcHeight);
		
		$img->resizeImage($dstWidth, $dstHeight, Imagick::FILTER_SINC,1);

		$img->setImageDepth(8); // 16bit -> 8bit
		$img->setImageColorspace(1);

		$img->writeImage($ofname);
		$img->destroy();

		$im = @imagecreatefrompng($ofname);
		imagealphablending( $im, false );
		imagepng($im, $ofname, 9);
	}

	$anotation = array();
	
	$title = array('Normalized image', 'Curved MPR');
	
	$anotation[0][0] = "Sagittal";		$anotation[0][1] = "&nbsp;";
	$anotation[1][0] = "Coronal";		$anotation[1][1] = "vertebral body";
	$anotation[2][0] = "Coronal";		$anotation[2][1] = "anterior wall of the canal";
	$anotation[3][0] = "Coronal";		$anotation[3][1] = "center of the canal";
	$anotation[4][0] = "Coronal";		$anotation[4][1] = "posterior wall of the canal";
	
	$COL_WIDTH = 150;

	if($_SESSION['personalFBFlg'] || $_SESSION['consensualFBFlg'] || $_SESSION['groupID'] == 'demo')
	{
		if($registTime != "")
		{
			$registMsg = 'registered at ' . $registTime;
		}
	}
	
	//----------------------------------------------------------------------------------------------
	// Show images	
	//----------------------------------------------------------------------------------------------
	$thumbnailImgFname = array();
	$orgImgFname = array();
	
	for($k=0; $k<2; $k++)
	{
		for($j=1; $j<=5; $j++)
		{
			$srcImgFname = sprintf("result%03d.png",  $k * 5 + $j);
			$thumbnailFname = sprintf("result%03d_thumb.png", $k * 5 + $j);
		
			$ifname = $pathOfCADReslut . $DIR_SEPARATOR . $srcImgFname;
			$ofname = $pathOfCADReslut . $DIR_SEPARATOR . $thumbnailFname;
			$dstWidth = $COL_WIDTH;
			
			if(!is_file($ofname))	CreateThumbnailMPR($ifname, $ofname, $dstWidth);
		
			$orgImgFname[$k][$j-1] = '../' . $webPathOfCADReslut . $DIR_SEPARATOR_WEB . $srcImgFname;
			$thumbnailImgFname[$k][$j-1] = '../' . $webPathOfCADReslut . $DIR_SEPARATOR_WEB . $thumbnailFname;
		}
	} // end for : $k
	//----------------------------------------------------------------------------------------------

	//----------------------------------------------------------------------------------------------
	// Create HTML for scoring interface
	//----------------------------------------------------------------------------------------------
	if($_SESSION['personalFBFlg'] || $_SESSION['consensualFBFlg'])
	{	
		include("visual_scoring_interface.php");
	}
	//----------------------------------------------------------------------------------------------

	//------------------------------------------------------------------------------------------------------------------
	// Settings for Smarty
	//------------------------------------------------------------------------------------------------------------------
	//�G���[�����������ꍇ�ɃG���[�\��������ݒ�
	ini_set( 'display_errors', 1 );

	require_once('../smarty/SmartyEx.class.php');
	$smarty = new SmartyEx();

	$smarty->assign('params', $params);

	$smarty->assign('consensualFBFlg', $consensualFBFlg);

	$smarty->assign('ticket', htmlspecialchars($_SESSION['ticket'], ENT_QUOTES));

	$smarty->assign('patientID',         $patientID);
	$smarty->assign('patientName',       $patientName);	
	$smarty->assign('sex',               $sex);
	$smarty->assign('age',               $age);
	$smarty->assign('studyID',           $studyID);
	$smarty->assign('studyDate',         $studyDate);
	$smarty->assign('seriesID',          $seriesID);
	$smarty->assign('modality',          $modality);
	$smarty->assign('seriesDescription', $seriesDescription);
	$smarty->assign('seriesDate',        $seriesDate);
	$smarty->assign('seriesTime',        $seriesTime);
	$smarty->assign('bodyPart',          $bodyPart);
	
	$smarty->assign('registTime',    $registTime);	
	$smarty->assign('registMsg',     $registMsg);
	
	$smarty->assign('orgImgFname',       $orgImgFname);	
	$smarty->assign('thumbnailImgFname', $thumbnailImgFname);	

	$smarty->assign('scoringHtml',       $scoringHtml);	

	// For CAD detail

	$smarty->display('cad_results/Spine-CPR_v2.2.tpl');
	//------------------------------------------------------------------------------------------------------------------		


?>
