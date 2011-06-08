<?php

/**
 * Exports HTML for lesion CAD display.
 *
 * @author Soichiro Miki <smiki-tky@umin.ac.jp>
 */
class LesionCandDisplayPresenter extends DisplayPresenter
{
	public $imageWidth;
	public $imageHeight;

	protected function getImageSize($display_id)
	{
		global $DIR_SEPARATOR;
		$imgfile = $this->owner->pathOfCadResult() . $DIR_SEPARATOR .
			sprintf($this->params['resultImage'], $display_id);
		$img = @imagecreatefrompng($imgfile);
	    if($img)
		{
			$this->imageWidth  = imagesx($img);
			$this->imageHeight = imagesy($img);
			imagedestroy($img);
		}
	}

	protected function defaultParams()
	{
		return array_merge(
			parent::defaultParams(),
			array(
				'resultImage' => 'result%03d.png',
				'caption' => 'Lesion Classification'
			)
		);
	}

	/**
	 * This function will be called by template file.
	 * @param unknown_type $display_id
	 */
	public function resultImage($display_id)
	{
		global $DIR_SEPARATOR_WEB;
		return
			$this->owner->webPathOfCadResult() . $DIR_SEPARATOR_WEB .
			sprintf($this->params['resultImage'], $display_id);
	}

	public function show()
	{
		return $this->executeTemplate('lesion_cand_display_presenter.tpl');
	}

	public function extractDisplays($input)
	{
		$result = array();
		$count = 0;
		$pref = $this->owner->Plugin->userPreference();
		foreach ($input as $rec)
		{
			// Remember the size of the image
			if (!$this->imageWidth)
				$this->getImageSize($rec['sub_id']);
			$item = array(
				'display_id' => $rec['sub_id'],
				'location_x' => $rec['location_x'],
				'location_y' => $rec['location_y'],
				'location_z' => $rec['location_z'],
				'slice_location' => $rec['slice_location'],
				'volume_size' => $rec['volume_size'],
				'confidence' => $rec['confidence']
			);

			if ($pref['maxDispNum'] && ++$count > $pref['maxDispNum'])
			{
				$item['_hidden'] = true;
			}
			$result[$rec['sub_id']] = $item;
		}
		return $result;
	}
}

?>