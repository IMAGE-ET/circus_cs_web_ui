<?php
	include("../common.php");
	Auth::checkSession();
	Auth::purgeUnlessGranted(Auth::PROCESS_MANAGE);

	//--------------------------------------------------------------------------
	// Import $_REQUEST variables
	//--------------------------------------------------------------------------
	$mode = $_REQUEST['mode'];
	$filename = $_REQUEST['filename'];
	//--------------------------------------------------------------------------

	if($mode == "clear")
	{
		if (preg_match('/^[A-Za-z0-9\_]+\.txt$/', $filename))
		{
			$f = $LOG_DIR . $DIR_SEPARATOR . $filename;
			unlink($f);
			touch($f);
		}
	}

	$flist = scandir($LOG_DIR);
	foreach ($flist as $file)
	{
		if($file != "." && $file != "..")
		{
			$f = $LOG_DIR . $DIR_SEPARATOR . $file;
			$fileData[] = array(
				'name' => $file,
				'lastUpdate' => date("Y-m-d H:i:s", filemtime($f)),
				'size' => filesize($f)
			);
		}
	}

	//--------------------------------------------------------------------------
	// Settings for Smarty
	//--------------------------------------------------------------------------
	$smarty = new SmartyEx();
	$smarty->assign('fileData', $fileData);
	$smarty->display('administration/server_logs.tpl');
	//--------------------------------------------------------------------------

