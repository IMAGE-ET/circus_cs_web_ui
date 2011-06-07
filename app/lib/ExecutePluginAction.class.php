<?php

class ExecutePluginAction extends ApiAction
{
	static $job_status_list = array(
		"PLUGIN_FAILED"        => -1,
		"PLUGIN_NOT_ALLOCATED" =>  1,
		"PLUGIN_ALLOCATED"     =>  2,
		"PLUGIN_PROCESSING"    =>  3,
		"PLUGIN_SUCESSED"      =>  4
	);
	
	
	function execute($api_request)
	{
		$action = $api_request['action'];
		$params = $api_request['params'];
		
		if(self::check_params($params) == FALSE) {
			throw new ApiException("Invalid parameter.", ApiResponse::STATUS_ERR_OPE);
		}
		
		// Throws ApiException if failed
		self::check_register($params);
		
		$jobID = self::register_job($params);
		
//		$result = QueryJobAction::queryJob(jobID);
		$result = array("jobID" => $jobID);
		$res = new ApiResponse();
		$res->setResult($action, $result);
		return $res;
	}
	
	
	private function check_params($params)
	{
		$name      = $params['pluginName'];
		$version   = $params['pluginVersion'];
		$seriesUID = $params['seriesUID'];
		
		if (!isset($name) || !isset($version) || !is_array($seriesUID)) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	private function check_register($params)
	{
		$name         = $params['pluginName'];
		$version      = $params['pluginVersion'];
		$seriesUIDArr = $params['seriesUID'];
		
		// Connect to SQL Server
		$pdo = DBConnector::getConnection();
		
		// Check series record count
		
		// Search plugin id
		$sqlStr = "SELECT pm.plugin_id, pm.plugin_name, pm.version, pm.exec_enabled, max(cs.series_description)"
				. " FROM plugin_master pm, plugin_cad_master cm, plugin_cad_series cs"
				. " WHERE cm.plugin_id=pm.plugin_id AND cs.plugin_id=cm.plugin_id"
				. " AND pm.plugin_name=?"
				. " AND pm.version=?"
				. " AND cs.volume_id=0"
				. " GROUP BY pm.plugin_id, pm.plugin_name, pm.version, pm.exec_enabled, cm.label_order"
				. " ORDER BY cm.label_order ASC";
		
		$stmtCADMaster = $pdo->prepare($sqlStr);
		$stmtCADMaster->execute(array($name, $version));
		
		if ($stmtCADMaster->rowCount() <= 0) {
			throw new ApiException("Plugin(name:".$name.", version:".$version.") is not found.", ApiResponse::STATUS_ERR_OPE);
		}
		
		$result = $stmtCADMaster->fetch(PDO::FETCH_ASSOC);
		$plugin_id = $result['plugin_id'];
		
		// Check ruleset
		for ($vid = 0; $vid < $stmtSeries->rowCount(); $vid++)
		{
			$sqlStr = "SELECT * FROM plugin_cad_series"
					. " WHERE plugin_id=?"
					. " AND volume_id=?";
			
			$stmtRule = $pdo->prepare($sqlStr);
			$stmtRule->execute(array($plugin_id, $vid));
			
			if ($stmtRule->rowCount() <= 0) {
				throw new ApiException("Series count is not match.", ApiResponse::STATUS_ERR_OPE);
			}
			
			$result = $stmtRule->fetch(PDO::FETCH_ASSOC);
		}
		
		throw new ApiException("Series count is not match.", ApiResponse::STATUS_ERR_OPE);
		
		
		// Check plugin status
		while($resultCADMaster = $stmtCADMaster->fetch(PDO::FETCH_NUM))
		{
			$sqlStr = "SELECT el.job_id, el.status, el.executed_at"
					. " FROM executed_plugin_list el, executed_series_list es, plugin_master pm"
					. " WHERE pm.plugin_name=? AND pm.version=?"
					. " AND pm.plugin_id=el.plugin_id"
					. " AND el.job_id=es.job_id"
					. " AND es.volume_id=0"
					. " AND es.series_sid=?"
					. " ORDER BY el.job_id DESC";
			
			$stmtCADExec = $pdo->prepare($sqlStr);
			$stmtCADExec->execute(array($name, $version, $seriesUIDArr));
			if ($stmtCADExec->rowCount() >= 1)
			{
				while($resultCADExec = $stmtCADExec->fetch(PDO::FETCH_NUM))
				{
					// executed or failed
					;
				}
			}
		}
		
		$pdo = null;
	}
	
	
	private function register_job($plugin)
	{
		//------------------------------------------------------------------------------------------------------------------
		// Import request variables
		//------------------------------------------------------------------------------------------------------------------
		$seriesUIDArr = $plugin['seriesUID'];
		$cadName      = $plugin['pluginName'];
		$version      = $plugin['pluginVersion'];
		$priolity     = $plugin['priolity'];
		$resultPolicy = $plugin['resultPolicy'];
		
		if (!isset($priolity)) {
			$priolity = 1;
		}
		if (!isset($resultPolicy)) {
			$resultPolicy = "default";
		}
		
		$seriesNum = count($seriesUIDArr);
		$userID = ApiExec::currentUser()->user_id;
		
		$dstData = array('message'      => "",
				         'registeredAt' => date("Y-m-d H:i:s"),
				         'executedAt'   => "");
		$sidArr = array();
		//------------------------------------------------------------------------------------------------------------------
	
		// Connect to SQL Server
		$pdo = DBConnector::getConnection();

		// Get plugin ID
		$sqlStr = "SELECT plugin_id FROM plugin_master WHERE plugin_name=? AND version=?";
		$pluginID = DBConnector::query($sqlStr, array($cadName, $version), 'SCALAR');

		// Get series sid
		$sqlStr = "SELECT sid FROM series_list WHERE series_instance_uid=?";
		
		foreach($seriesUIDArr as $item)
		{
			$sidArr[] = DBConnector::query($sqlStr, $item, 'SCALAR');
		}

		// Get storage ID of first series
		$sqlStr= "SELECT storage_id FROM series_list WHERE sid=?";
		$storageID =  DBConnector::query($sqlStr, $sidArr[0], 'SCALAR');
		
		// jobID duplication check
		$colArr =array();

		$sqlStr = "SELECT * FROM executed_plugin_list el, executed_series_list es"
				. " WHERE el.plugin_id=? AND el.job_id=es.job_id AND el.status>0"
				. " AND (";

		$colArr[] = $pluginID;

		for($i = 0; $i < count($seriesNum); $i++)
		{
			if($i > 0)  $sqlStr .= " OR ";

			$sqlStr .= "(es.volume_id=? AND es.series_sid=?)";

			$colArr[] = $i;
			$colArr[] = $sidArr[$i];
		}
		$sqlStr .= ");";

		$stmt = $pdo->prepare($sqlStr);
		$stmt->execute($colArr);

		if($stmt->rowCount() == $seriesNum)
		{
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			
			// job status check
			if($result['status'] != self::$job_status_list['PLUGIN_SUCESSED'])
			{
				throw new ApiException("Already registered", ApiResponse::STATUS_ERR_SYS);
			}
			else
			{
				throw new ApiException("Already executed", ApiResponse::STATUS_ERR_SYS);
			}
			$dsaData['executedAt'] = $result['executed_at'];
		}
	
		if($dstData['message'] == "")
		{
			try
			{
				//---------------------------------------------------------------------------------------------------------
				// Begin transaction
				//---------------------------------------------------------------------------------------------------------
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$pdo->beginTransaction();
				//---------------------------------------------------------------------------------------------------------
				
				// Get new job ID
				$sqlStr= "SELECT nextval('executed_plugin_list_job_id_seq')";
				$jobID =  DBConnector::query($sqlStr, NULL, 'SCALAR');
	
				// Set new job ID
				$sqlStr = "SELECT setval('executed_plugin_list_job_id_seq', ?, true)";
				$stmt = $pdo->prepare($sqlStr);
				$stmt->bindValue(1, $jobID);
				$stmt->execute();
	
				// Register into "execxuted_plugin_list"
				$sqlStr = "INSERT INTO executed_plugin_list"
						. " (job_id, plugin_id, storage_id, status, exec_user, executed_at)"
						. " VALUES (?, ?, ?, 1, ?, ?)";
				$stmt = $pdo->prepare($sqlStr);
				$stmt->execute(array($jobID, $pluginID, $storageID, $userID, $dstData['registeredAt']));
	
				// Register into "job_queue"
				$sqlStr = "INSERT INTO job_queue"
						. " (job_id, plugin_id, priolity, status, exec_user, registered_at, updated_at)"
						. " VALUES (?, ?, ?, 1, ?, ?, ?)";
				$stmt = $pdo->prepare($sqlStr);
				$stmt->execute(array($jobID, $pluginID, $priolity, $userID, $dstData['registeredAt'], $dstData['registeredAt']));
	
				// Register into executed_series_list and job_queue_series
				for($i=0; $i<$seriesNum; $i++)
				{
					$sqlParams = array($jobID, $i, $sidArr[$i]);
	
					$sqlStr = "INSERT INTO executed_series_list(job_id, volume_id, series_sid)"
							. " VALUES (?, ?, ?)";
					$stmt = $pdo->prepare($sqlStr);
					$stmt->execute($sqlParams);
	
					$sqlStr = "INSERT INTO job_queue_series(job_id, volume_id, series_sid)"
							. " VALUES (?, ?, ?)";
					$stmt = $pdo->prepare($sqlStr);
					$stmt->execute($sqlParams);
				}
	
				//---------------------------------------------------------------------------------------------------------
				// Commit transaction
				//---------------------------------------------------------------------------------------------------------
				$pdo->commit();
				//---------------------------------------------------------------------------------------------------------
	
				$dstData['message'] = 'Successfully registered plug-in job';
			}
			catch (PDOException $e)
			{
				$pdo->rollBack();
				
				throw new ApiException("Fail to register plug-in job", ApiResponse::STATUS_ERR_SYS);
			}
		}
		
		return $jobID;
	
		$pdo = null;
	}
}
?>
