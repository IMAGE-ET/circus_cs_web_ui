<?php

/**
 * CadResult represents result set for one CAD process.
 *
 * @author Soichiro Miki <smiki-tky@umin.ac.jp>
 */
class CadResult extends Model
{
	/* Model Definitions */
	protected static $_table = 'executed_plugin_list';
	protected static $_primaryKey = 'job_id';
	protected static $_belongsTo = array(
		'Plugin' => array('key' => 'plugin_id'),
		'Storage' => array('key' => 'storage_id'),
		'PluginResultPolicy' => array('key' => 'policy_id')
	);
	protected static $_hasMany = array(
		'Feedback' => array('key' => 'job_id'),
		'PluginAttribute' => array('key' => 'job_id')
	);
	protected static $_hasAndBelongsToMany = array(
		'Series' => array(
			'joinTable' => 'executed_series_list',
			'foreignKey' => 'job_id',
			'associationForeignKey' => 'series_sid',
			'foreignPrimaryKey' => 'sid'
		)
	);

	/* Protected Properties */
	protected $attributes;
	protected $displayPresenter;
	protected $feedbackListener;
	protected $extensions;
	protected $rawResult;
	protected $presentation;

	/**
	 * Retrieves the list of feedback data associated with this CAD Result.
	 * @param string $kind One of the following. 'personal', the list of
	 * all personal feedback set. 'consensual', the consensual feedback.
	 * 'all', the all feedback set. 'user', ID specified.
	 * @param string $user_id Specifies the user ID when $kind is 'user'
	 * @param bool $ignoreTemporary If true, temporarily saved feedback sets
	 * are ignored.
	 * @return array Array of Feedback objects
	 */
	public function queryFeedback($kind = 'all', $user_id = null, $ignoreTemporary = true)
	{
		$feedback_list = $this->Feedback;
		if ($ignoreTemporary)
			$feedback_list = array_filter($feedback_list, function($in) {
				return $in->status == Feedback::REGISTERED;
			});
		switch ($kind)
		{
			case "personal":
				return array_filter($feedback_list, function($in) {
					return $in->is_consensual == false;
				});
				break;
			case "consensual":
				return array_filter($feedback_list, function($in) {
					return $in->is_consensual == true;
				});
				break;
			case "all":
				return $feedback_list;
				break;
			case "user":
				return array_filter($feedback_list, function($in) use ($user_id) {
					$bool = $in->is_consensual == false && $in->entered_by == $user_id;
					return $bool;
				});
				break;
		}
		throw new BadMethodCallException();
	}

	/**
	 * Returns the feedback visiblity/availability
	 * associated with this CAD Result.
	 * This is based on the user group settings and the feedback policy.
	 *
	 * @param string $feedbackMode 'consensual' or 'personal'
	 * @param User $user The user
	 * @param string &$reason Outputs the reason why the specified feedback mode
	 * is disabled or locked.
	 * @return string 'normal', 'disabled', 'locked', or 'hidden'.
	 * The 'normal' status means the login user can input or see his feedback.
	 * The 'disabled' status means the user can inspect the feedback
	 * result, but you cannot enter or modify it. (But he may go back
	 * to 'normal' status by unregistering)
	 * The 'locked' status applies only for consensual feedback and
	 * means that the user cannot enter the consensual mode.
	 */
	public function feedbackAvailability($feedbackMode = 'personal', User $user = null, &$reason)
	{
		$policy = $this->PluginResultPolicy;

		// The availability is 'locked' when the user has not yet entered
		// his personal feedback, or there is no enough sets of personal
		// feedback to make consensus (configured by plugin result policy)
		$my_personal_feedback = $this->queryFeedback('user', $user->user_id);
		$consensual_feedback = $this->queryFeedback('consensual');
		$personal_feedback = $this->queryFeedback('personal');
		if ($feedbackMode == 'consensual')
		{
			if (!count($my_personal_feedback) && !count($consensual_feedback))
			{
				$reason = 'You can not enter consensual feedback before entering personal feedback.';
				return 'locked';
			}
			$minfb = $policy->min_personal_fb_to_make_consensus;
			if ($minfb > count($personal_feedback))
			{
				$reason = "You need at least $minfb feedback to enter consensual mode.";
				return 'locked';
			}
		}

		// The availability is 'disabled' when:
		// (1) The user has no privileges to give personal/consensual feedback
		// (2) Feedback registration is declined by result policy
		// (3) The user has already entered the feedback.
		// (4) Consensual feedback is already registered by someone.
		$currentUser = Auth::currentUser();
		$policy = $this->PluginResultPolicy;
		if ($feedbackMode == 'personal')
		{
			if (!$currentUser->hasPrivilege(Auth::PERSONAL_FEEDBACK_ENTER))
			{
				$reason = "You do not have privilege to give personal feedback.";
				return 'disabled';
			}
			if (!$policy->searchGroup($policy->allow_personal_fb, $currentUser->Group))
			{
				$reason = "Your personal feedback is refused by result policy settings.";
				return 'disabled';
			}
			$max = $policy->max_personal_fb;
			if ($max > 0 && count($personal_feedback) >= $max)
			{
				$reason = "You can not enter more than $max sets of personal feedback.";
				return 'disabled';
			}
		}
		if ($feedbackMode == 'consensual')
		{
			if (!$currentUser->hasPrivilege(Auth::CONSENSUAL_FEEDBACK_ENTER))
			{
				$reason = "You do not have privilege to give personal feedback.";
				return 'disabled';
			}
			if (!$policy->searchGroup($policy->allow_consensual_fb, $currentUser->Group))
			{
				$reason = "Your consensual feedback is refused by result policy settings.";
				return 'disabled';
			}
		}
		if ($feedbackMode == 'personal' && count($my_personal_feedback))
		{
			$reason = 'Your personal feedback is already registered.';
			return 'disabled';
		}
		if (count($consensual_feedback))
		{
			$reason = 'The consensual feedback is already registered.';
			return 'disabled';
		}

		return 'normal';
	}

	/**
	 * Returns whether the user can unregister the feedback.
	 * @param string $feedbackMode 'personal' or 'consensual'.
	 * Please note there is no plant to unregister consensual feedback,
	 * so this method always return false for consensual feedback.
	 * @return bool $feedbackMode True if the user can unregister this
	 * CAD result.
	 */
	public function feedbackUnregisterable($feedbackMode = 'personal')
	{
		if ($feedbackMode != 'personal')
			return false;
		return false; // TODO: implement feedbackUnregisterable
	}

	/**
	 * Returns the CAD result visibility for the user currently logged in.
	 * @param array $groups The array of Group instances or group ID strings
	 * @return bool True if the current user can view this CAD result.
	 */
	public function checkCadResultAvailability(array $groups)
	{
		$policy = $this->PluginResultPolicy;
		return $policy->searchGroup($policy->allow_result_reference, $groups);
	}

	/**
	 * Retrieves the list of displays (such as lesion candidates).
	 * @return array Array of CAD dispalys
	 */
	public function getDisplays()
	{
		$presenter = $this->Plugin->presentation()->displayPresenter();
		return $presenter->extractDisplays($this->rawResult);
	}

	/**
	 * Returns the raw result of CAD.
	 * @return array Array of raw CAD result from database table
	 */
	public function rawCadResult()
	{
		return $this->rawResult;
	}

	/**
	 * Retrieves the list of attributes associated with this CAD Result.
	 */
	public function getAttributes()
	{
		if (is_array($this->attributes))
			return $this->attributes;
		$tmp = $this->PluginAttribute;
		$result = array();
		foreach ($tmp as $attribute)
		{
			$result[$attribute->key] = $attribute->value;
		}
		$this->attributes = $result;
		return $result;
	}

	/**
	 * Retrieves the Plugin object which produced this CAD result.
	 * @return Plugin
	 */
	public function getExecutedPlugin()
	{
		return null; // not implemented
	}

	public function load($id)
	{
		//
		// STEP: Load using inheriting load method
		//
		parent::load($id);

		if (!isset($this->_data['job_id']))
			return;

		//
		// STEP: Get the table name which actually holds the result data
		//
		$pid = $this->Plugin->plugin_id;
		$sqlStr = "SELECT * FROM plugin_cad_master WHERE plugin_id = ?";
		$cad_master = DBConnector::query($sqlStr, $pid, 'ARRAY_ASSOC');
		$result_table = $cad_master['result_table'];

		//
		// STEP: Get the actual CAD results from the result table
		//
		$sqlStr = "SELECT * FROM \"$result_table\" WHERE job_id=?";
		$this->rawResult = DBConnector::query($sqlStr, $this->job_id, 'ALL_ASSOC');
	}

	/**
	 * Returns the URL of plugin-specific public directory.
	 */
	public function webPathOfPluginPub()
	{
		$plugin_name = $this->Plugin->fullName();
		return "plugin/$plugin_name";
	}

	/**
	 * Returns the plugin-specific public directory.
	 * This directory contains plugin-specific image files, css files,
	 * javascript files, etc.
	 */
	public function pathOfPluginPub()
	{
		global $WEB_UI_ROOT;
		$plugin_name = $this->Plugin->fullName();
		return "$WEB_UI_ROOT/pub/plugin/$plugin_name";
	}

	/**
	 * Returns CAD result directory URL.
	 * @return string CAD result directory web path.
	 */
	public function webPathOfCadResult()
	{
		global $DIR_SEPARATOR_WEB, $SUBDIR_CAD_RESULT;
		$str_id = $this->storage_id;
		return '../storage/' . $str_id . '/' . $this->job_id; // TODO: change
	}

	/**
	 * Returns CAD result directory path.
	 * @return string CAD result directory path.
	 */
	public function pathOfCadResult()
	{
		global $DIR_SEPARATOR, $SUBDIR_CAD_RESULT;
		$path = $this->Storage->path;
		$result =  $path . $DIR_SEPARATOR . $this->job_id;
		return $result;
	}
}

?>