<?php

/**
 * Customized Product - MxxBR167x
 * Product Customizer
 *
 * @author JMadrigal
 */
 
class CustomProduct extends CustomProductTemplate {
	
    //======================================================================
    // Configure the Primary Form
    //======================================================================
    public function configureForm() {
        $this->template->add_css('css/proposals.css');
        $this->formidable->setFormAttribute('id', 'MxxBR167x_form');
		$this->formidable->setFormValidate(TRUE);
        //$this->formidable->setWizardLastButtonLabel('Finish');

        $this->setupProductStep1();
        $this->setupProductStep2();
        $this->setupProductStep3();

                $this->formidable->setValues($this->original_values);
    }

	
    //----------------------------------------------------------------------

    // Step 1 : General Info
    private function setupProductStep1() {
    	
    	$care_coverage = array (
    		'100' => '100%',
    		'80' => '80%');
    	
        $page = $this->formidable->addWizardPage('Basic Information', array('id'=>'last_page'));
        // $page = $this->formidable;

        $page->addNote('Enter the required general information for this product. Click Next to continue.');
        $page->addHeader("General Information");

        $page->add('plan_name', 'Plan Name:', 'text', '' , array('required') );
        $page->add('care_coverage', 'Enter Preventive Care Coverage:', 'dropdown','', array('required'),  array('values' => $care_coverage));   
        $page->add('rx_intergrate', 'Is Rx Integrated:', 'radios', '0' , array('required'), array('values'=>array('0'=>'Yes', '1'=>'No')));
    }

    // Step 2 : Health Programs
    private function setupProductStep2() {

        $page = $this->formidable->addWizardPage('Health Programs');

        $page->addNote("Choose the 360 Health Programs offered with this product and, if applicable, enter the 24/7 NurseLine Phone Number in the format shown. Click Next to continue.");
        $page->addHeader("What 360 Health Programs are offered");

        $page->add('health_step1','Health Coaching Program', 'checkbox', '1' );
        $page->add('health_step2','Healthy Lifestyles: Tobacco-Free', 'checkbox', '2' );
        $page->add('health_step3','Healthy Lifestyles: Healthy Weight', 'checkbox', '3' );
        $page->add('health_step4','24/7 NurseLine', 'checkbox', '3' );
        $page->add('health_step5','24/7 NurseLine Phone', 'text', '', null, array('onlyEnableIf' => 'health_step4') );
    }

    // Step 3 : Rewards Info
    private function setupProductStep3() {

        $noteEntry = "Choose the rewards information that you would like included with this product. If rewards are not available, remove the check box from the first box to deselect all options. When you have finished making selections, click Create Proof to preview your product. Make any desired changes after viewing the proof. Click Create Document when finished to generate your customized product. ";
        $page = $this->formidable->addWizardPage('Rewards Information');

        $page->addNote($noteEntry);
        $page->addHeader("Rewards Information");

        $page->add('rewards_step1','Are Rewards Available', 'checkbox', '', null,  array('no_label' => TRUE) );

        $fs = $page->addFieldSet('', null, 'rewards_step1');

        $fs->add('rewards_step2','MyHealth Assessment', 'checkbox', '', null, array('no_label' => TRUE) );
        $fs->add('rewards_step20','Amount', 'text', '$50', null, array('onlyEnableIf' => 'rewards_step2') );

        $fs->add('rewards_step3','Enroll in Health Coaching', 'checkbox', '', null, array('no_label' => TRUE, 'onlyEnableIf' => 'health_step1', 'uncheckOnDisable' => 'true') );
        $fs->add('rewards_step30','Amount', 'text', '$100', null, array('onlyEnableIf' => 'rewards_step3'));

        $fs->add('rewards_step4','Graduate Health Coaching', 'checkbox', '', null, array('no_label' => TRUE, 'onlyEnableIf' => 'health_step1', 'uncheckOnDisable' => 'true') );
        $fs->add('rewards_step40','Amount', 'text', '$200', null, array('onlyEnableIf' => 'rewards_step4'));

        $fs->add('rewards_step5','Healthy Lifestyles: Tobacco-Free', 'checkbox', '', null, array('no_label' => TRUE, 'onlyEnableIf' => 'health_step2', 'uncheckOnDisable' => 'true') );
        $fs->add('rewards_step50','Amount', 'text', '$50', null, array('onlyEnableIf' => 'rewards_step5'));

        $fs->add('rewards_step6','Healthy Lifestyles: Healthy Weight', 'checkbox', '', null, array('no_label' => TRUE, 'onlyEnableIf' => 'health_step3', 'uncheckOnDisable' => 'true'));
        $fs->add('rewards_step60','Amount', 'text', '$50', null, array('onlyEnableIf' => 'rewards_step6'));
    }

	//----------------------------------------------------------------------
	
	/**
	 *
	 */
	public function setValues() {
	
		$map = array(
                    // Step 1 : General Info
			'Text_1' => 'plan_name',
			'Text_5' => 'care_coverage',
			'Text_9' => 'rx_intergrate',

                    // Step 2 : Health Programs
                        'Text_14' => 'health_step1',
                        'Text_16' => 'health_step2',
                        'Text_18' => 'health_step3',
                        'Text_20' => 'health_step4',
                        'Text_21' => 'health_step5',

                    // Step 3 : Rewards Info
                        'Text_22' => 'rewards_step1',   # Are there rewards?

                        'Text_26' => 'rewards_step2',   # My Health
                        'Text_27' => 'rewards_step20',
                        'Text_28' => 'rewards_step3',   # Enroll in Health Coaching
                        'Text_29' => 'rewards_step30',
                        'Text_30' => 'rewards_step4',   # Graduate Health Coaching
                        'Text_31' => 'rewards_step40',
                        'Text_33' => 'rewards_step5',   # HL : Tobacco Free
                        'Text_34' => 'rewards_step50',
                        'Text_35' => 'rewards_step6',   # HL : Healthy Weight
                        'Text_36' => 'rewards_step60'
		);
		$this->productcustomizer->map($this->formidable->values, $map);
		
	}
}