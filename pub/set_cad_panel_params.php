<?php

	$modalityMenuVal = array();
	$cadList = array();
	$modalityNum = count($modalityList);
	$modalityCadList = array();

	for($i=0; $i<$modalityNum; $i++)
	{
		$tmpStr = "";
		$prevCadName = "";

		$sqlStr = "SELECT DISTINCT pm.plugin_name, pm.version"
				. " FROM executed_plugin_list el, executed_series_list es,"
				. " series_list sr, plugin_master pm"
				. " WHERE el.status=?"
				. " AND es.job_id=el.job_id"
				. " AND sr.sid=es.series_sid"
				. " AND pm.plugin_id=el.plugin_id";

		if($modalityList[$i] != 'all')  $sqlStr .= " AND sr.modality=?";
		$sqlStr .= " ORDER BY pm.plugin_name ASC, pm.version DESC";

		$stmt = $pdo->prepare($sqlStr);
		$stmt->bindValue(1, Job::JOB_SUCCEEDED);
		if($modalityList[$i] != 'all')  $stmt->bindParam(2, $modalityList[$i]);
		$stmt->execute();

		while($result = $stmt->fetch(PDO::FETCH_NUM))
		{
			$modalityCadList[$modalityList[$i]][$result[0]][] = $result[1];

			if($result[0] != $prevCadName)
			{
				if($prevCadName != "")  $tmpStr .= '/';
				$tmpStr .= $result[0];
				$prevCadName = $result[0];
			}
			$tmpStr .= '^' . $result[1];
		}
		$modalityMenuVal[] = $tmpStr;
	}

	$cadMenuStr = explode('/', $modalityMenuVal[0]);

	$cadNum = count($cadMenuStr);

	for($i=0; $i<$cadNum; $i++)
	{
		$tmpStr = explode('^', $cadMenuStr[$i]);

		$cadList[$i][0] =  $tmpStr[0];                                     // plug-in (CAD) name
		$cadList[$i][1] =  substr($cadMenuStr[$i], strlen($tmpStr[0])+1);  // version str
	}

