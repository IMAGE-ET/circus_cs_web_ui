<?php
	include_once('common.php');
	Auth::checkSession();

	function DeleteDupeRows($data)
	{
		$ret = array();

		foreach($data as $key => $val)
		{
			if( !in_array( $val, $ret ) )	$ret[] = $val;
		}

		return $ret;
	}

	$dstData = array('message' => '');

	//-----------------------------------------------------------------------------------------------------------------
	// Import $_POST variables and validation
	//-----------------------------------------------------------------------------------------------------------------
	$params = array();

	$validator = new FormValidator();

	$validator->addRules(array(
		"mode" => array(
			"type" => "select",
			"options" => array('patient', 'study', 'series')),
		"sidArr" => array(
					'type' => 'array',
					'minLength' => 1,
					'childrenRule' => array('type' => 'int', 'min' => 1))
				));

	if($validator->validate($_POST))
	{
		$params = $validator->output;
		$dstData['message'] = "";

		if($_SESSION['ticket'] != $_POST['ticket'])
		{
			$dstData['message'] = "Ticket is invalid.";
		}
	}
	else
	{
		$params = $validator->output;
		$dstData['message'] = $validator->errors;
	}
	//-----------------------------------------------------------------------------------------------------------------

	if($dstData['message'] == "")
	{
		try
		{
			// Connect to SQL Server
			$pdo = DBConnector::getConnection();
		}
		catch (PDOException $e)
		{
			var_dump($e->getMessage());
		}

		try
		{
			//---------------------------------------------------------------------------------------------------------
			// Begin transaction and lock tables (patient_list, series_list, study_list)
			//---------------------------------------------------------------------------------------------------------
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$pdo->beginTransaction();	// Begin transaction

			//$sqlStr = "LOCK table patient_list, study_list, series_list, tag_list IN ACCESS EXCLUSIVE MODE";
			//$stmt = $pdo->prepare($sql);
			//$stmt->execute();
			//---------------------------------------------------------------------------------------------------------

			//---------------------------------------------------------------------------------------------------------
			// Create patient / study / series list
			//---------------------------------------------------------------------------------------------------------
			$tmpPtList = array();
			$tmpStList = array();
			$tmpSrList = array();

			$sqlStr = "SELECT pt.patient_id, st.study_instance_uid, sr.series_instance_uid, sm.path,"
					. " pt.sid, st.sid, sr.sid"
					. " FROM patient_list pt, study_list st, series_list sr, storage_master sm"
			        . " WHERE pt.patient_id=st.patient_id"
					. " AND st.study_instance_uid=sr.study_instance_uid"
					. " AND sr.storage_id=sm.storage_id"
					. " AND ";

			switch($params['mode'])
			{
				case 'patient':  $sqlStr .= "pt.sid=?";  break;
				case 'study':    $sqlStr .= "st.sid=?";  break;
				case 'series':   $sqlStr .= "sr.sid=?";  break;
			}

			$stmt = $pdo->prepare($sqlStr);

			foreach($params['sidArr'] as $sid)
			{
				$stmt->bindValue(1, $sid);
				$stmt->execute();

				while ($result = $stmt->fetch(PDO::FETCH_NUM))
				{
					$tmpPtList[] = array("ptSID"=> $result[4], "ptID" => $result[0], "path" => $result[3]);
					$tmpStList[] = array("stSID"=> $result[5], "ptID" => $result[0], "stUID" => $result[1],
					                     "path" => $result[3]);
					$tmpSrList[] = array("srSID"=> $result[6], "ptID" => $result[0], "stUID" => $result[1],
									     "srUID" => $result[2], "path" => $result[3]);
				}
			}

			$patientList = DeleteDupeRows($tmpPtList);
			$studyList   = DeleteDupeRows($tmpStList);
			$seriesList  = DeleteDupeRows($tmpSrList);
			//---------------------------------------------------------------------------------------------------------

			//---------------------------------------------------------------------------------------------------------
			// Find series which are already assigned to any CAD jobs
			//---------------------------------------------------------------------------------------------------------
			foreach($seriesList as $vals)
			{
				$sqlStr = "SELECT COUNT(*) FROM executed_plugin_list el, executed_series_list es "
				        . " WHERE es.series_sid=? AND el.job_id=es.job_id";

				$stmt = $pdo->prepare($sqlStr);
				$stmt->bindParam(1, $vals['srSID']);
				$stmt->execute();

				if($stmt->fetchColumn() > 0)
				{
					$dstData['message'] = 'Assigned plugin exists in selected series';
					break;
				}
			}

			// Otherwise this series can be safely deleted
			if($dstData['message'] == "")
			{
				foreach($seriesList as $vals)
				{
					$stmt = $pdo->prepare("DELETE FROM series_list WHERE series_instance_uid=?");
					$stmt->bindParam(1, $vals['srUID']);
					$stmt->execute();

					if($stmt->rowCount() == 1)
					{
						$stmtTag = $pdo->prepare("DELETE FROM tag_list WHERE category=3 AND reference_id=?");
						$stmtTag->bindParam(1, $vals['srSID']);
						$stmtTag->execute();

						$seriesDir = $vals['path'] . $DIR_SEPARATOR . $vals['ptID']
						           . $DIR_SEPARATOR . $vals['stUID']
								   . $DIR_SEPARATOR . $vals['srUID'];
						DeleteDirRecursively($seriesDir);
					}
					else
					{
						$message = $stmt->errorInfo();
						$dstData['message'] = $message[2];
					}
				}
			}
			//---------------------------------------------------------------------------------------------------------

			//---------------------------------------------------------------------------------------------------------
			// Delete studies which are not associated with any series
			//---------------------------------------------------------------------------------------------------------
			if($dstData['message'] == "")
			{
				foreach($studyList as $vals)
				{
					$sqlStr = "SELECT COUNT(*) FROM series_list WHERE study_instance_uid=?";
					$stmt = $pdo->prepare($sqlStr);
					$stmt->bindParam(1, $vals['stUID']);
					$stmt->execute();

					if($stmt->fetchColumn() == 0)
					{
						$stmt = $pdo->prepare("DELETE FROM study_list WHERE study_instance_uid=?");
						$stmt->bindParam(1, $vals['stUID']);
						$stmt->execute();

						if($stmt->rowCount() == 1)
						{
							$stmtTag = $pdo->prepare("DELETE FROM tag_list WHERE category=2 AND reference_id=?");
							$stmtTag->bindParam(1, $vals['stSID']);
							$stmtTag->execute();

							$studyDir = $vals['path'] . $DIR_SEPARATOR . $vals['ptID']
							          . $DIR_SEPARATOR . $vals['stUID'];
							DeleteDirRecursively($studyDir);
						}
						else
						{
							$message = $stmt->errorInfo();
							$dstData['message'] = $message[2];
							break;
						}
					}
				}
			}
			//---------------------------------------------------------------------------------------------------------

			//---------------------------------------------------------------------------------------------------------
			// Delete patients which are not associated with any study
			//---------------------------------------------------------------------------------------------------------
			if($dstData['message'] == "")
			{
				foreach($patientList as $vals)
				{
					$sqlStr = "SELECT COUNT(*) FROM study_list WHERE patient_id=?";
					$stmt = $pdo->prepare($sqlStr);
					$stmt->bindParam(1, $vals['ptID']);
					$stmt->execute();

					if($stmt->fetchColumn() == 0)
					{
						$stmt = $pdo->prepare("DELETE FROM patient_list WHERE patient_id=?");
						$stmt->bindParam(1, $vals['ptID']);
						$stmt->execute();

						if($stmt->rowCount() == 1)
						{
							$stmtTag = $pdo->prepare("DELETE FROM tag_list WHERE category=1 AND reference_id=?");
							$stmtTag->bindParam(1, $vals['ptSID']);
							$stmtTag->execute();

							$patientDir = $vals['path'] . $DIR_SEPARATOR . $vals['ptID'];
							DeleteDirRecursively($patientDir);
						}
						else
						{
							$message = $stmt->errorInfo();
							$dstData['message'] = $message[2];
							break;
						}
					}
				}
			}
			//---------------------------------------------------------------------------------------------------------

			//$stmt = $pdo->prepare("COMMIT WORK");
			//$stmt->execute();

			//---------------------------------------------------------------------------------------------------------
			// Commit transaction
			//---------------------------------------------------------------------------------------------------------
			$pdo->commit();
			//---------------------------------------------------------------------------------------------------------

		}
		catch (PDOException $e)
		{
			$pdo->rollBack();
			$dstData['message'] = $e->getMessage();
		}

		if($dstData['message'] == "")
		{
			$dstData['message'] = "The selected " . $params['mode'] . " was deleted successfully.";
		}

		$pdo = null;
	}

	echo json_encode($dstData);

