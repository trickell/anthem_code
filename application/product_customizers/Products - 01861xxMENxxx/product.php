<?php

/**
 * Customized Product - 01861xxMenxxx
 * Product Customizer
 *
 * @author QCao
 * @author John Madrigal
 */
 
class CustomProduct extends CustomProductTemplate {
	
    //======================================================================
    // Configure the Primary Form
    //======================================================================
    public function configureForm() {
        $this->template->add_css('css/proposals.css');
        $this->formidable->setFormAttribute('id', '01861xxMENxxx_form');
		$this->formidable->setFormValidate(TRUE);
        //$this->formidable->setWizardLastButtonLabel('Finish');
        $this->setupProductStep1();
        // $this->formidable->setValues($this->original_values); 
    }

	
    //----------------------------------------------------------------------

    private function setupProductStep1() {
    	
    	$reward_amount = array (
    		'custom' => '(Enter a custom value)',
    		'1' => '1',
    		'10' => '10',
    		'20' => '20',
    		'25' => '25',
    		'30' => '30',
    		'40' => '40',
    		'50' => '50',
    		'100' => '100',
    		'200' => '200');
    	
        //$page = $this->formidable->addWizardPage('Basic Information', array('id'=>'last_page'));
        $page = $this->formidable;
        $page->add('package_type', 'Select Package Type', 'radios', '0' , array('required'), array('values'=>array('0'=>'Direct', '1'=>'Points')));
        $page->add('reward_type', 'Select Reward Type', 'radios', '0' , array('required'), array('values'=>array('0'=>'Gift card', '1'=>'Health account deposit', '2'=>'Premium discount')));     
        $page->add('reward_amount_2', 'Select Reward Amount', 'dropdown','', array('required'),  array('values' => $reward_amount));
        $page->addNote('or');
        $page->add('reward_amount_1', 'Enter Custom Amount', 'text', '', array('required', 'numeric'));  
        $page->add('package', '', 'hidden', '');
        $page->add('reward', '', 'hidden', '');
        $page->add('reward_text', '', 'hidden', '');                      
    }

	//----------------------------------------------------------------------
	
	/**
	 *
	 */
	public function setValues() {
	
		$map = array(
			'Text_1' => 'package',
			'Text_2' => 'reward',
			'Text_5' => 'reward_amount_1',
			'Text_6' => 'reward_text',
			'Text_7' => ''
		);
		$this->productcustomizer->map($this->formidable->values, $map);
		
	}
}