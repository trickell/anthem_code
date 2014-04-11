<?php

// @author jmadrigal

class CustomProduct extends SimpleCustomProduct {

	private $localGeorgiaMap = array(
		'Form_Number' => array('Insured_Type', array(
			'FI' => 'EGASH1290GFI Rev. 6/10',
			'SI' => 'EGASH1290GSI Rev. 6/10'
		)),
		'Text_3' => array('Insured_Type', array(
			'FI' => 'Fully Insured plans',
			'SI' => 'Self Insured plans'
		)),
		'Text_4' => array('Insured_Type', array(
			'FI' => 'Points only (points cannot be redeemed for any rewards) ',
			'SI' => 'Points and rewards program'
		)),
		'Text_5' => array('Insured_Type', array(
			'FI' => '<ParaStyle:blue><CharStyle:blue2>Points only (points cannot be redeemed for any rewards)',
			'SI' => "<ParaStyle:blue><CharStyle:blue2>Points and rewards program<ParaStyle:white><CharStyle:white2><0x000D>Customized redemption center"
		)),
	);

	private $nationalGeorgiaMap = array(
		'Form_Number' => array('Insured_Type', array(
			'FI' => 'EANSH1290NGFI Rev. 6/10',
			'SI' => 'EANSH1290NGSI Rev. 6/10'
		)),
		'Text_3' => array('Insured_Type', array(
			'FI' => 'Fully Insured plans',
			'SI' => 'Self Insured plans'
		)),
		'Text_4' => array('Insured_Type', array(
			'FI' => 'Points only (points cannot be redeemed for any rewards)',
			'SI' => 'Points and rewards program'
		)),
		'Text_5' => array('Insured_Type', array(
			'FI' => '<ParaStyle:blue><CharStyle:blue2>Points only (points cannot be redeemed for any rewards)',
			'SI' => "<ParaStyle:blue><CharStyle:blue2>Points and rewards program<ParaStyle:white><CharStyle:white2><0x000D>Customized redemption center"
		)),
	);

	public function index() {
		// If the product is NOT being generated for the Georgia Anthem region,
		// then we actually don't have to do anything.
		if($this->CI->user->getCurrentRegionId() != 15 || $this->CI->user->national_account) {
			$this->template->set_template('fancybox');
			// force the product to be generated
			$this->generateProduct();

			$this->CI->include_js->embed('setTimeout(closeFancybox, 1000);');
			$this->template->write('title', 'Building "'.$this->product->products_description.'"');
			$this->template->write('content', 'Please wait while we automatically start the generation for this product.');
			$this->template->render();
		} else {
			// render the default view
			parent::index();
		}
	}

	/**
	 * Overridden to allow setting of the correct map.
	 */
	public function setValues() {
		if($this->CI->user->getCurrentRegionId() == 15 && !$this->CI->user->national_account) {
			$this->map = $this->CI->user->national_account ? $this->nationalGeorgiaMap : $this->localGeorgiaMap;
		}
		parent::setValues();
	}

}