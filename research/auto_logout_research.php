<?php
	
	$toTopDir = (!isset($params['toTopDir'])) ? $params['toTopDir'] : '../';

	//-----------------------------------------------------------------------------------------------------------------
	// Auto logout (session timeout)
	//-----------------------------------------------------------------------------------------------------------------
	if(time() > $_SESSION['timeLimit'] || ($_SESSION['researchShowFlg'] == 0 && $_SESSION['researchExecFlg'] == 0))
	{
		header('location: ' . $toTopDir . 'index.php?mode=timeout');
		exit();
	}
	else
	{
		$_SESSION['timeLimit'] = time() + $SESSION_TIME_LIMIT;
	}
	//-----------------------------------------------------------------------------------------------------------------

?>