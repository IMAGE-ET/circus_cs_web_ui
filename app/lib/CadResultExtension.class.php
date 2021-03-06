<?php

/**
 * CadResultExtension is the base class which adds some functionality
 * to the CAD result page.
 * @author Soichiro Miki <smiki-tky@umin.ac.jp>
 */
class CadResultExtension extends CadResultElement
{
	// Methods for views
	public function head() { return ''; }
	public function beforeBlocks() { return ''; }
	public function afterBlocks() { return ''; }
	public function tabs() { return array(); }
}
