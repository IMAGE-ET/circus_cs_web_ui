<?php
	session_cache_limiter('nocache');
	session_start();

	include_once("common.php");
	
	//-----------------------------------------------------------------------------------------------------------------
	// Auto logout (session timeout)
	//-----------------------------------------------------------------------------------------------------------------
	if(time() > $_SESSION['timeLimit'])  header('location: index.php?mode=timeout');
	else	$_SESSION['timeLimit'] = time() + $SESSION_TIME_LIMIT;
	//-----------------------------------------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------------------------------------
	// Import $_REQUEST variables and set $param array
	//-----------------------------------------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------------------------------------

	$data = array();

	try
	{	
		// Connect to SQL Server
		$pdo = new PDO($connStrPDO);

		//-------------------------------------------------------------------------------------------------------------
		// Settings for Smarty
		//-------------------------------------------------------------------------------------------------------------
		require_once('smarty/SmartyEx.class.php');
		$smarty = new SmartyEx();
		
		$smarty->display('favorites_demo.tpl');
		//-------------------------------------------------------------------------------------------------------------
	}
	catch (PDOException $e)
	{
		var_dump($e->getMessage());
	}

	$pdo = null;
?>
