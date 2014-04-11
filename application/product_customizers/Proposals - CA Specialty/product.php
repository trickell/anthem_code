<?php

/**
 * Proposals - CA Specialty
 * Product Customizer
 *
 * @author PDeJarnett
 * @author Jmadrigal
 */
class CustomProduct extends ProposalsProductTemplate {
	private $db_riders = array(
		'GD2433' => 'Orthodontia Rider ($1,000 Maximum - Adult & Child)',
		'GD2311' => 'Orthodontia Rider ($1,500 Maximum - Adult & Child)',
		'GD2312' => 'Orthodontia Rider ($2,000 Maximum - Adult & Child)',
		'GD2432' => 'Orthodontia Rider ($1,000 Maximum - Child)',
		'GD2434' => 'Orthodontia Rider ($1,500 Maximum - Child)',
		'GD2436' => 'Orthodontia Rider ($2,000 Maximum - Child)',
		'GD2435' => 'Annual Maximum Carryover Rider',
		'GD2437' => 'Brush Biopsy Rider',
		'GD2438' => 'Posterior Composite Fillings Rider',
		'GD2439' => 'Dental Implant Rider',
	);

	//======================================================================
	// Configure the Primary Form
	//======================================================================

	public function configureForm($is_preview = FALSE, $is_send = FALSE) {
		// configure the pages of the wizard
		if(!$is_send) {
			$this->setupIAvenue();
			$this->setupBasicInfo();
			$this->setupFinancialSummary();
			$this->setupProposalInfo();
			$this->setupDentalBlue();
			$this->setupDentalNet();
			$this->setupBlueViewVision();
			$this->setupUpload();
			$this->setupPreview();
		}
		if(!$is_preview) {
			$body_text = "<p>Thank you for the opportunity to present the attached proposal.</p>

<p>According to a recent employer and broker LIMRA survey:</p>

<ul>
	<li>88% of employer groups would buy all lines of coverage from a single carrier if they could get a better price</li>
	<li>83% believe their employees will receive better customer service if they buy from a single carrier</li>
	<li>74% would buy from a single carrier in order to receive one bill</li>
	<li>70% would buy from a single carrier in order to only have to go to one website for all transactions</li>
	<li>67% would buy from a single carrier to streamline their enrollment</li>
</ul>

<p>Not only are we the nation's leading health benefits company, we also offer a comprehensive suite of dental, vision, life and disability insurance -- that offers employers <u>all</u> of the above.</p>

<p>Please let me know when we can discuss the details of our proposal and what other areas I can help with after your initial review.  I look forward to working with you on this opportunity!</p>";
			$this->setupSendProposal('Proposal', $body_text, 'California Specialty Proposal', 'modules');
		}
	}

	//----------------------------------------------------------------------

	private function setupBasicInfo() {
		$page = $this->formidable->addWizardPage('Basic Information');
		$page->add('group_name', 'Group Name', 'text', '', array('required'));
		$page->add('effective_date', 'Effective Date', 'date', '', array('required', 'valid_date'), array('field_after' => ' <em>mm/dd/yyyy</em>'));
		
		$page->add('hasmed', '^Does the group already have medical coverage with Anthem?', 'radios', 'yes', array('required'), array(
			'values' => array(
				'yes' => 'Yes',
				'no' => 'No'
			)
		));

		$page->addHeader('Proposal Content');
		$page->addNote('Select the content modules you want to include in the proposal. You must choose at least one module.');
		$page->add('modules', 'Modules', 'checkboxes', '', array('required'), array(
			'values' => array(
				'dentalblue' => 'Dental Blue',
				'dentalnet' => 'Dental Net',
				'bvvision' => 'Blue View Vision',
				'life' => 'Life',
				'disability' => 'Disability',
			),
			'separator' => '<br/>'
		));
	}

	//----------------------------------------------------------------------

	private function setupFinancialSummary() {
		$types = array(
			'dental' => array('Health + Dental', 1, 'modules=dentalblue,dentalnet'),
			'bvvision' => array('Health + Blue View Vision', 1, 'modules=bvvision'),
			'life' => array('Health + Life ($25K or more)', 1, 'modules=life'),
			'shortdis' => array('Health + Short Term Disability', '0.5', 'modules=disability'),
			'longdis' => array('Health + Long Term Disability', '0.5', 'modules=disability')
		);
		
		$page = $this->formidable->addWizardPage('One Solution Savings Financial Summary');
		$page->addNote("Please choose a financial summary and add the appropriate information.");
		$page->addWarning('Throughout the creation process, dollar signs should not be added to any numbers. Dollar signs will appear in the appropriate places in the final document.');
		
		$statementsGroup = $page->addFieldset(''); // empty fieldset
		$statements = $statementsGroup->addOptionalGroups('statement_type', 'statement_none', '', TRUE);
			$statements->setTypeRadio();
			$st_none = $statements->addGroup('statement_none', 'No Statement');
				$st_none->addNote('No statement will be included with this proposal.');
			$st1 = $statements->addGroup('statement_1', 'Statement 1');
				$st1->add('st1_totalmonthlypremium', 'Total Monthly Health Premium', 'currency', '', array('required'));
				$st1->add('st1_totalannualpremium', 'Total Annual Health Premium', 'currency', '0', '', array('readonly' => 'readonly'));
				$st1->addNote('Approximate Discounts Below');
				foreach($types as $name => $info) {
					// add these fields to track the values - they are rendered in the view.
					$st1->add("st1_h_{$name}_enabled", '', 'norender');
					$st1->add("st1_h_{$name}_cost", '', 'norender');
					$st1->add("st1_h_{$name}_annual_savings", '', 'norender');
					$st1->add("st1_h_{$name}_specialty_savings", '', 'norender');
				}
				$st1->addHTML($this->load_view('statement1_discounts_table', array('types' => $types)));

			$st2 = $statements->addGroup('statement_2', 'Statement 2');
				$st2->add('st2_totalmonthlypremium', 'Total Monthly Health Premium', 'currency', '', array('required'));
				$st2->add('st2_totalannualpremium', 'Total Annual Health Premium', 'currency', '0', '', array('readonly' => 'readonly'));
				$st2->addNote('Approximate Discounts Below');
				foreach($types as $name => $info) {
					// add these fields to track the values - they are rendered in the view.
					$st2->add("st2_h_{$name}_enabled", '', 'norender');
					$st2->add("st2_h_{$name}_annual_savings", '', 'norender');
				}
				$st2->addHTML($this->load_view('statement2_discounts_table', array('types' => $types)));
	}

	//----------------------------------------------------------------------

	private function setupProposalInfo() {
		$page = $this->formidable->addWizardPage('Proposal Information');
		$page->add('group_name_2', 'Group Name', 'text', '', array('required'));
		$page->add('effective_date_2', 'Effective Date', 'date', '', array('required', 'valid_date'), array('field_after' => ' <em>mm/dd/yyyy</em>'));
		$page->add('number_of_employees', 'Number of Employees', 'number', '', array('required'));
		$group = $page->addGroup('', 'modules=dentalblue,dentalnet,bvvision', FALSE);
			$group->add('rate_tier', 'Rate Tier', 'radios', '3', '', array('values' => array('3' => '3 Tier', '4' => '4 Tier')));
			$group->add('billing_frequency', 'Billing Frequency', 'radios', 'Monthly Rates:', '', array('values' => array('Monthly Rates:' => 'Monthly', 'Bi-Weekly Rates:' => 'Bi-Weekly', 'Tenthly Rates:' => 'Tenthly')));
			$group->add('zip_code', 'Zip Code', 'zip', '', array('required', 'max_length' => 10, 'min_length' => 5), array('field_after' => '<br/><em>Ex: 12345 or 12345-0001</em>'));
			$group->add('sic', 'SIC', 'text', '', array('required'));
		$group = $page->addGroup('', 'modules=dentalblue,dentalnet,bvvision', FALSE);
			$group->addHeader('Year Rate Guarantees');
			$group1 = $group->addGroup('', 'modules=dentalblue,dentalnet', FALSE);
				$group1->add('dental_plans_years', 'Dental Plans', 'radios', '1', '', array('values' => array('1' => '1 Year', '2' => '2 Years')));
			$group1 = $group->addGroup('', 'modules=bvvision', FALSE);
				$group1->add('vision_plans_years', 'Vision Plans', 'radios', '1', '', array('values' => array('1' => '1 Year', '2' => '2 Years')));
	}

	//----------------------------------------------------------------------

	private function setupDentalBlue() {
		$page = $this->formidable->addWizardPage('Dental Blue Plans', '', 'modules=dentalblue');
		$page->addNote('Please configure your proposed Dental Blue plans.');
		$plansGroup = $page->addFieldset('Proposed Plans');
			$plans = $plansGroup->addOptionalGroups('dentalblue_plans', 'plan_1', '', TRUE);
			$db_opts = array('' => 'Select an Option');
			for($db=1; $db<=30; $db++) {
				$db_opts[$db] = $db;
			}
			$hundred_opts = createOptions('Select an Option', '100', '200', '300');
			for($i=1; $i<=4; $i++) {
				$plan = $plans->addGroup("plan_$i", "Proposed Plan $i", 'proposed_plan');
				$planTypes = $plan->addOptionalGroups("dentalblue_plan_{$i}_type", 'standard', '', TRUE);
				$planTypes->setTypeRadio();
				$standard = $planTypes->addGroup('standard', 'Standard Rate Quote');
					$standard->addMultifieldRow("dentalblue_plan_{$i}_name_row", 'Plan Name');
					$standard->addMultifieldItem("dentalblue_plan_{$i}_name_row", "dentalblue_plan_{$i}_dbnum", 'dropdown', '', array('required'), array('values' => $db_opts, FORM_HTML_FIELD_BEFORE => 'Plan&nbsp;', 'class' => 'dentalblue_plan_dbnum'));
					$standard->addMultifieldItem("dentalblue_plan_{$i}_name_row", "dentalblue_plan_{$i}_100num", 'dropdown', '', array('required'), array('values' => $hundred_opts, FORM_HTML_FIELD_BEFORE => '&nbsp;&nbsp;Network '));
				$custom = $planTypes->addGroup('custom', 'Custom Rate Quote via Form', 'dentalBlueCustomRate');
					$custom->addNote('Loading Custom Form...');
					$custom->addHTML('<span class="url" style="display:none">'.current_url()."/dentalblue_custom/{$i}</span>");

				$upload = $planTypes->addGroup('upload', 'Custom Rate Quote via Upload');
					$upload->addNote('Upload a custom rate quote in either Word (.doc, .docx) or Adobe PDF (.pdf) format.');
					$upload->add("dentalblue_plan_{$i}_upload_name", 'Plan Name', 'text', '', array('required'));
					$this->addDocumentUpload($upload, "dentalblue_plan_{$i}_upload");

				$riders = $plan->addOptionalGroups("dentalblue_plan_{$i}_addRiders", '', '', TRUE);
				$ridersGroup = $riders->addGroup('true', 'Include Dental Blue Riders', 'dentalblue_riders');
					$ridersGroup->add("dentalblue_plan_{$i}_riders", FALSE, 'checkboxes', '', array(), array('values' => $this->db_riders, 'wrap' => '<br/>'));

				$this->_addRates($plan, 'dentalblue', $i);
			}
	}

	//----------------------------------------------------------------------

	private function setupDentalNet() {
		$page = $this->formidable->addWizardPage('Dental Net', '', 'modules=dentalnet');
		$page->addNote('Please configure your proposed Dental Net plans.');
		$plansGroup = $page->addFieldset('Proposed Plans');
			$plans = $plansGroup->addOptionalGroups('dentalnet_plans', 'plan_1', '', TRUE);
			$dn_opts = array(
				'' => 'Select an Option',
				'2100' => 'Dental Net HMO 2100',
				'2200' => 'Dental Net HMO 2200',
				'2300' => 'Dental Net HMO 2300',
				'2400' => 'Dental Net HMO 2400',
				'2500' => 'Dental Net HMO 2500',
				'2600' => 'Dental Net HMO 2600',
				'2700' => 'Dental Net HMO 2700'
			);

			for($i=1; $i<=4; $i++) {
				$plan = $plans->addGroup("plan_$i", "Proposed Plan $i", 'proposed_plan');
				$planTypes = $plan->addOptionalGroups("dentalnet_plan_{$i}_type", 'standard', '', TRUE);
				$planTypes->setTypeRadio();
				$standard = $planTypes->addGroup('standard', 'Standard Rate Quote');
					$standard->add("dentalnet_plan_{$i}_name", 'Plan Name', 'dropdown', '', array('required'), array('values' => $dn_opts));
				$custom = $planTypes->addGroup('custom', 'Custom Rate Quote via Form', 'dentalnetCustomRate');
					$custom->addNote('Loading Custom Form...');
					$custom->addHTML('<span class="url" style="display:none">'.current_url()."/dentalnet_custom/{$i}</span>");

				$upload = $planTypes->addGroup('upload', 'Custom Rate Quote via Upload');
					$upload->addNote('Upload a custom rate quote in either Word (.doc, .docx) or Adobe PDF (.pdf) format.');
					$upload->add("dentalnet_plan_{$i}_upload_name", 'Plan Name', 'text', '', array('required'));
					$this->addDocumentUpload($upload, "dentalnet_plan_{$i}_upload");

				$this->_addRates($plan, 'dentalnet', $i);
			}
	}

	//----------------------------------------------------------------------

	private function setupBlueViewVision() {
		$page = $this->formidable->addWizardPage('Blue View Vision', '', 'modules=bvvision');
		$page->addNote('Please configure your proposed Blue View Vision plans.');
		$plansGroup = $page->addFieldset('Proposed Plans');
			$plans = $plansGroup->addOptionalGroups('bvvision_plans', 'plan_1', '', TRUE);
			$bvv_opts = createOptions(
				'Select an Option',
				'BVA1',
				'BVA2',
				'BVA3',
				'BVB1',
				'BVB2',
				'BVC1',
				'BVC2',
				'BVC3',
				'BVC4',
				'BVMOA4',
				'BVMOA6',
				'BVMOB4',
				'BVMOB6',
				'BVMOC4',
				'BVMOC6'
			);
			foreach($bvv_opts as $k => &$name) {
				$name = str_replace(array('BV', 'MO'), array('BV ', 'MO '), $name);
			}

			for($i=1; $i<=4; $i++) {
				$plan = $plans->addGroup("plan_$i", "Proposed Plan $i", 'proposed_plan');
				$planTypes = $plan->addOptionalGroups("bvvision_plan_{$i}_type", 'standard', '', TRUE);
				$planTypes->setTypeRadio();
				$standard = $planTypes->addGroup('standard', 'Standard Rate Quote');
					$standard->add("bvvision_plan_{$i}_name", 'Plan Name', 'dropdown', '', array('required'), array('values' => $bvv_opts));
				$custom = $planTypes->addGroup('custom', 'Custom Rate Quote via Form', 'bvvisionCustomRate');
					$custom->addNote('Loading Custom Form...');
					$custom->addHTML('<span class="url" style="display:none">'.current_url()."/bvvision_custom/{$i}</span>");

				$upload = $planTypes->addGroup('upload', 'Custom Rate Quote via Upload');
					$upload->addNote('Upload a custom rate quote in either Word (.doc, .docx) or Adobe PDF (.pdf) format.');
					$upload->add("bvvision_plan_{$i}_upload_name", 'Plan Name', 'text', '', array('required'));
					$this->addDocumentUpload($upload, "bvvision_plan_{$i}_upload");

				$this->_addRates($plan, 'bvvision', $i);
			}
	}

	//----------------------------------------------------------------------

	private function setupUpload() {
		$page = $this->formidable->addWizardPage('Upload Life & Disability Quote', array('id' => 'page_ldquote'), 'modules=life,disability');
		$page->addNote('Upload a custom <span id="ldquote_text">Life &amp; Disability</span> quote in either Word (<em>DOC</em>, <em>DOCX</em>), Excel (<em>XLS</em>, <em>XLSX</em>), or Adobe PDF (.pdf) format.');
		$this->addDocumentAndExcelUpload($page, "lifedisability_upload");
	}

	//----------------------------------------------------------------------

	private function _addRates($plan, $idbase, $i) {
		$plan->add("{$idbase}_plan_{$i}_empvol", FALSE, 'radios', 'Employer Paid', '', array('values' => array('Employer Paid' => 'Employer Paid', 'Voluntary' => 'Voluntary')));
		$plan->add("{$idbase}_plan_{$i}_t_emponly", 'Employee Only', 'currency', '', array('required'));
		$tier3 = $plan->addGroup('', 'rate_tier=3', FALSE);
			$tier3->add("{$idbase}_plan_{$i}_t3empplusone", 'Employee + 1', 'currency', '', array('required'));
		$tier4 = $plan->addGroup('', 'rate_tier=4', FALSE);
			$tier4->add("{$idbase}_plan_{$i}_t4empplusone", 'Employee + Spouse', 'currency', '', array('required'));
			$tier4->add("{$idbase}_plan_{$i}_t4emppluschild", 'Employee + Child(ren)', 'currency', '', array('required'));
		$plan->add("{$idbase}_plan_{$i}_t_empplusfam", 'Employee + Spouse + Child(ren)', 'currency', '', array('required'));
	}

	//======================================================================
	// Optional Custom Forms are loaded as needed
	//======================================================================

	/**
	 * Load in the custom form for dentalblue
	 *
	 * @param int $i Index of the plan
	 */
	public function dentalblue_custom($i = 1) {
		$this->formidable->setValues($this->original_values);

		$this->formidable->add("dentalblue_plan_{$i}_custom_name", 'Plan Name');
		$this->formidable->addMultiDropdownRow("dentalblue_plan_{$i}_custom_annbenn_row", 'Annual Benefit Maximum', array(
				"dentalblue_plan_{$i}_custom_annbenn_year" => createOptions('Select Range', 'Calendar Year', 'Benefit Year'),
				"dentalblue_plan_{$i}_custom_annbenn_amt" => array('values' => createOptions('Select Amount', $this->amounts(500,5000,250)), 'field_after' => ' per insured person')
			), array('required'));

		$this->formidable->addMultiDropdownRow("dentalblue_plan_{$i}_custom_annded_row", 'Annual Deductible', array(
				"dentalblue_plan_{$i}_custom_annded_year" => createOptions('Select Range', 'Calendar Year', 'Benefit Year'),
				"dentalblue_plan_{$i}_custom_annded_amtperson" => array(
					'values' => createOptions('Select Amount',$this->amounts(0,75,25),$this->amounts(100,500)),
					'field_after' => ' per insured person / up to'
				),
				"dentalblue_plan_{$i}_custom_annded_amtfam" => array(
					'values' => createOptions('Select Amount',$this->amounts(0,300,75),$this->amounts(450,1500,150)),
					'field_after' => ' per family'
				)
			), array('required'));
		$waived = $this->formidable->addFieldset('Deductible Waived for Diagnostic and Preventative Services:');
			$waived->add("dentalblue_plan_{$i}_custom_waived_in", 'In-Network', 'radios', '', array('required'), array('values' => createOptions('Yes', 'No')));
			$waived->add("dentalblue_plan_{$i}_custom_waived_out", 'Out-of-Network', 'radios', '', array('required'), array('values' => createOptions('Yes', 'No')));

		$services = $this->formidable->addFieldset('Dental Services', array('dentalblueServices'));
			$services->addHTML($this->load_view('dentalblue_custom_services', array('customIndex' => $i)));

		echo $this->formidable->render(FALSE);
	}

	private function amounts($from, $to, $step = 50) {
		$ret = array();
		for($i = $from; $i <= $to; $i += $step) {
			$ret[] = '$'.number_format($i, 0);
		}
		return $ret;
	}

	//----------------------------------------------------------------------

	/**
	 * Load in the custom form for dentalnet
	 *
	 * @param int $id
	 */
	public function dentalnet_custom($i = 1) {
		$this->formidable->setValues($this->original_values);

		$this->formidable->add("dentalnet_plan_{$i}_custom_name", 'Plan Name');
		$this->formidable->addSimpleDropdown("dentalnet_plan_{$i}_custom_groupsize", 'Large Group Size', createOptions('Select One', '55-99', '51-250', '100-250', '250+'));
		$this->formidable->addSimpleDropdown("dentalnet_plan_{$i}_custom_annben", 'Annual Benefit Maximum', createOptions('Select One', 'Calendar Year', 'Benefit Year'));

		$services = $this->formidable->addFieldset('Dental Services', array('class' => 'dentalnetServices'));
			$services->addNote('Select the Member Copayment');
			$services->addSimpleDropdown("dentalnet_plan_{$i}_custom_officevisit", '<b>Office Visit</b> - in addition to any other applicable copayments.', createOptions('Select One', 'No Copayment', '$5'));
			$services->addNote('Restorative Services, for example:');
			$services->addSimpleDropdown("dentalnet_plan_{$i}_custom_filling", 'Filling, amalgam, two surfaces (2150)', createOptions('Select One', 'No Copayment', '$7', '$10'));
			$services->addSimpleDropdown("dentalnet_plan_{$i}_custom_surg", 'Oral surgery, e.g., tooth extraction, simple (7140)', createOptions('Select One', 'No Copayment', '$8', '$10'));
			$services->addSimpleDropdown("dentalnet_plan_{$i}_custom_endo", 'Endodontics, e.g., root canal, molar (3330)', createOptions('Select One', '$180', '$200', '$240', '$250', '$265'));
			$services->addSimpleDropdown("dentalnet_plan_{$i}_custom_perio", 'Periodontics, e.g., scaling and root planing, per quadrant (4341)', createOptions('Select One', '$20', '$25', '$50', '$60'));
			$services->addSimpleDropdown("dentalnet_plan_{$i}_custom_prosth1", 'Prosthodontics, e.g.', createOptions('Select One', '$100', '$150', '$200', '$230', '$275', '$300', '$350'), TRUE, array('label_after' => 'crown, porcelain fused to high noble metal (2750)'));
			$services->addSimpleDropdown("dentalnet_plan_{$i}_custom_prosth2", 'Prosthodontics, e.g.', createOptions('Select One', '$150', '$200', '$225', '$275', '$300', '$350'), TRUE, array('label_after' => 'denture, complete, upper or lower (5110/5120)'));
			$services->addSeparator();
			$services->addSimpleDropdown("dentalnet_plan_{$i}_custom_ortho", '<b>Orthodontic Services</b>', createOptions('Select One', 'Not Covered', '$1,450 per insured', '$1,600 per insured', '$1,850 per insured', '$2,150 per insured'));
			$services->addSimpleDropdown("dentalnet_plan_{$i}_custom_chad", 'Child only coverage / Adult and Child Coverage', createOptions('Select One', 'n/a', 'Child only', 'Adult and child'));
			$services->addSimpleDropdown("dentalnet_plan_{$i}_custom_ortholife", 'Orthodontic standard treatment services per lifetime', createOptions('Select One', 'n/a', 'up to 24 months'));
			$services->addSimpleDropdown("dentalnet_plan_{$i}_custom_preortho", 'Pre-orthodontic visit and treatment plan', createOptions('Select One', 'n/a', '$300 per insured'));
			$services->addSimpleDropdown("dentalnet_plan_{$i}_custom_orthoret", 'Orthodontic retention', createOptions('Select One', 'n/a', '$275 per insured'));

		echo $this->formidable->render(FALSE);
	}

	//----------------------------------------------------------------------

	/**
	 * Load in the custom form for blue vision
	 *
	 * @param int $id
	 */
	public function bvvision_custom($i = 1) {
		$this->formidable->add("bvvision_plan_{$i}_custom_name", 'Plan Name');
		$this->formidable->addSimpleDropdown("bvvision_plan_{$i}_custom_year", FALSE, createOptions('Each calendar year', 'Every two years'), TRUE, array('field_after' => ' you may receive any one of the following lens options:'));

		$opts = createOptions('Covered in full', '$15 copay; then covered in full', '$25 copay; then covered in full');

		$group = $this->formidable->addGroup(array('class' => 'bvvisionServices'));
			$group->addSimpleDropdown("bvvision_plan_{$i}_custom_single", '&nbsp;&bull;&nbsp;Standard plastic single vision lenses <em>(1 pair)</em>', $opts);
			$group->addSimpleDropdown("bvvision_plan_{$i}_custom_bifocal", '&nbsp;&bull;&nbsp;Standard plastic bifocal lenses <em>(1 pair)</em>', $opts);
			$group->addSimpleDropdown("bvvision_plan_{$i}_custom_trifocal", '&nbsp;&bull;&nbsp;Standard plastic trifocal lenses <em>(1 pair)</em>', $opts);

		echo $this->formidable->render(FALSE);
	}

	
	
	//======================================================================
	// Product Generation
	//======================================================================

	/**
	 * @see ProposalsProductTemplate
	 */
	protected function start_proposal_generation() {
		$this->CI->load->helper('number');
		$values = $this->formidable->get();
		// hack to add in any custom form values
		foreach($_POST as $k => $v) {
			if(!isset($values->{$k})) {
				$values->{$k} = $v;
			}
		}
		$files = array_merge(
			(array)$this->generateCover($values),
			(array)$this->generateStatements($values),
			(array)$this->generateDentalInsert($values),
			(array)$this->generateDentalBlue($values),
			(array)$this->generateDentalNet($values),
			(array)$this->generateBlueViewVision($values),
			(array)$this->generateLifeAndDisability($values)
		);
		if(in_array(FALSE, $files)) {
			// an error occurred
			return 'An error occurred while generating one of your products.';
		}
		return $files;
	}

	//----------------------------------------------------------------------

	private function generateCover($values) {
		return $this->build_workflow(
			$values, '1-cover', 'CAproposal_1.indd',
			array(
				'Text_1' => 'group_name',
				'Text_2' => 'effective_date'
			),
			'Proposal Cover'
		);
	}

	//----------------------------------------------------------------------

	private function generateStatements($values) {
		// The two statements can share the map
		$map = array(
			// total premiums
			'$Text_8' => 'st1_totalmonthlypremium',
			'$Text_28' => 'st1_totalannualpremium'
		);

		$total_percent = 0;
		$total_money = 0;
		// table data
		if((in_array('dentalblue', $values->modules) || in_array('dentalnet', $values->modules)) && $values->st1_h_dental_enabled) {
			$map['Text_9'] = '=Health + Dental';
			$map['Text_10'] = '=1%';
			$map['$Text_11'] = 'st1_h_dental_annual_savings';
			$map['$Text_12'] = 'st1_h_dental_cost';
			$map['%Text_13'] = 'st1_h_dental_specialty_savings';
			$total_percent += 1;
			$total_money += floatval($values->st1_h_dental_annual_savings);
		}
		if(in_array('bvvision', $values->modules) && $values->st1_h_bvvision_enabled) {
			$map['Text_14'] = '=Health + Blue View Vision';
			$map['Text_15'] = '=1%';
			$map['$Text_16'] = 'st1_h_bvvision_annual_savings';
			$map['$Text_17'] = 'st1_h_bvvision_cost';
			$map['%Text_18'] = 'st1_h_bvvision_specialty_savings';
			$total_percent += 1;
			$total_money += floatval($values->st1_h_bvvision_annual_savings);
		}
		if(in_array('life', $values->modules) && $values->st1_h_life_enabled) {
			$map['Text_19'] = '=Health + Life<cPosition:Superscript>*<cPosition:> ($25K or More)<cPosition:Superscript>2<cPosition:>';
			$map['Text_20'] = '=1%';
			$map['$Text_21'] = 'st1_h_life_annual_savings';
			$map['$Text_22'] = 'st1_h_life_cost';
			$map['%Text_23'] = 'st1_h_life_specialty_savings';
			$total_percent += 1;
			$total_money += floatval($values->st1_h_life_annual_savings);
		}
		if(in_array('disability', $values->modules)) {
			if($values->st1_h_shortdis_enabled) {
				$map['Text_24'] = '=Health + Short Term Disability<cPosition:Superscript>*<cPosition:>';
				$map['Text_25'] = '=0.50%';
				$map['$Text_26'] = 'st1_h_shortdis_annual_savings';
				$map['$Text_27'] = 'st1_h_shortdis_cost';
				$map['%Text_36'] = 'st1_h_shortdis_specialty_savings';
				$total_percent += 0.5;
				$total_money += floatval($values->st1_h_shortdis_annual_savings);
			}
			if($values->st1_h_longdis_enabled) {
				$map['Text_29'] = '=Health + Long Term Disability<cPosition:Superscript>*<cPosition:>';
				$map['Text_30'] = '=0.50%';
				$map['$Text_31'] = 'st1_h_longdis_annual_savings';
				$map['$Text_32'] = 'st1_h_longdis_cost';
				$map['%Text_33'] = 'st1_h_longdis_specialty_savings';
				$total_percent += 0.5;
				$total_money += floatval($values->st1_h_longdis_annual_savings);
			}
		}

		// total savings
		$map['%Text_34'] = '='.$total_percent;
		$map['$Text_35'] = '='.$total_money;

		switch($values->statement_type) {
			case 'statement_1':
				return $this->build_workflow($values, '2-stmt1', 'CAproposal_2.indd', $map, 'Statement 1');
			case 'statement_2':
				return $this->build_workflow($values, '2-stmt2', 'CAproposal_3.indd', $map, 'Statement 2');
			default:
				return;
		}
	}

	//----------------------------------------------------------------------

	private function generateDentalInsert($values) {
		$file = FALSE;
		if(in_array('dentalblue', $values->modules)) {
			if(in_array('dentalnet', $values->modules)) {
				$file = 'DENTAL_BLUE_AND_DENTAL_NET';
			} else {
				$file = 'DENTAL_BLUE_ONLY';
			}
		} else if(in_array('dentalnet', $values->modules)) {
			$file = 'DENTAL_NET';
		}
		if($file) {
			$file = $this->findStatic($file);
			if($file == FALSE) {
				return FALSE;
			}
			return array($file => 'Dental Overview');
		} else {
			// no dental sections
			return;
		}
	}

	//----------------------------------------------------------------------

	private function generateDentalBlue($values) {
		$this->CI->load->helper('number');
		if(!in_array('dentalblue', $values->modules)) {
			// no dental blue section
			return;
		}
		// build summary page
		$mapConfig = array(
			'Text_1' => 'group_name',
			'Text_2' => 'effective_date',
			'Text_6' => 'number_of_employees',
			'Text_36' => 'dental_plans_years',
			'Text_37' => 'zip_code',
			'Text_38' => 'sic',
		);
		$index = 39;
		for($plan=1; $plan<=4; $plan++) {
			if(in_array('plan_'.$plan, $values->dentalblue_plans)) {
				$name = '';
				switch($values->{"dentalblue_plan_{$plan}_type"}) {
					case "standard":
						$name = 'Dental Blue Plan&#174; ' . $values->{"dentalblue_plan_{$plan}_dbnum"} . ' Network ' . $values->{"dentalblue_plan_{$plan}_100num"};
						break;
					case "custom":
						$name = $values->{"dentalblue_plan_{$plan}_custom_name"};
						break;
					case "upload":
						$name = $values->{"dentalblue_plan_{$plan}_upload_name"};
						break;
				}

				$mapConfig['Text_'.$index] = 'Proposed Plan '.$plan . ' <0x2014> ' . $name . ' - ' . $values->{"dentalblue_plan_{$plan}_empvol"};
				if(!empty($values->{"dentalblue_plan_{$plan}_addRiders"}) && !empty($values->{"dentalblue_plan_{$plan}_riders"})) {
					//$riders = '<cTypeface:Bold>Dental Blue Riders:<cTypeface:>';
					$first = TRUE;
					$riders = '';
					foreach($values->{"dentalblue_plan_{$plan}_riders"} as $rider) {
						if(isset($this->db_riders[$rider])) {
							if($first) {
								$first = FALSE;
							} else {
								$riders .= "\n";
							}
							$riders .= $this->db_riders[$rider];
						}
					}
					$mapConfig['Text_'.($index+1)] = 'Dental Blue Riders:';
					$mapConfig['Text_'.($index+2)] = $riders;
				}
				$mapConfig['Text_'.($index+3)] = "billing_frequency";
				$rates = 'Employee: ' . currency_format($values->{"dentalblue_plan_{$plan}_t_emponly"});
				$tier3 = $values->rate_tier != 4;
				if($tier3) {
					$rates .= "\nEmployee + 1: " . currency_format($values->{"dentalblue_plan_{$plan}_t3empplusone"});
					$rates .= "\nEmployee + Spouse + Child(ren): " . currency_format($values->{"dentalblue_plan_{$plan}_t_empplusfam"});
				} else {
					$rates .= "\nEmployee + Spouse: " . currency_format($values->{"dentalblue_plan_{$plan}_t4empplusone"});
					$rates .= "\nEmployee + Child(ren): " . currency_format($values->{"dentalblue_plan_{$plan}_t4emppluschild"});
					$rates .= "\nEmployee + Spouse + Child(ren): " . currency_format($values->{"dentalblue_plan_{$plan}_t_empplusfam"});
				}
				$mapConfig['Text_'.($index+4)] = $rates;
				$index += 5;
			}
		}
		$files = $this->build_workflow($values, '-4-dbsum', 'CAproposal_5-3.indd', $mapConfig, 'Dental Blue Rates');
		for($plan=1; $plan<=4; $plan++) {
			$files = array_merge($files, (array)$this->generateDentalBluePlan($values, $plan));
		}
		$files = array_merge($files, (array)$this->generateDentalBlueRiders($values));
		return $files;
	}

	private function generateDentalBluePlan($values, $plan) {
		if(!in_array('plan_'.$plan, $values->dentalblue_plans)) {
			return;
		}
		switch($values->{"dentalblue_plan_{$plan}_type"}) {
			case "standard":
				$filename = make_path_safe('DB' . $values->{"dentalblue_plan_{$plan}_dbnum"}.'_'.$values->{"dentalblue_plan_{$plan}_100num"}.'_SB');
				$filename = $this->findStatic($filename);
				if($filename == FALSE) {
					return FALSE;
				}
				break;
			case "custom":
				$mapConfig = array(
					'Text_43' => "dentalblue_plan_{$plan}_custom_name",
					'Text_1'  => "dentalblue_plan_{$plan}_custom_annbenn_year",
					'Text_2'  => "dentalblue_plan_{$plan}_custom_annbenn_amt",
					'Text_3'  => "dentalblue_plan_{$plan}_custom_annded_year",
					'Text_4'  => "dentalblue_plan_{$plan}_custom_annded_amtperson",
					'Text_5'  => "dentalblue_plan_{$plan}_custom_annded_amtfam",
					'Text_6'  => "dentalblue_plan_{$plan}_custom_waived_in",
					'Text_7'  => "dentalblue_plan_{$plan}_custom_waived_out",
					'Text_44' => "dentalblue_plan_{$plan}_custom_diaprev_in",
					'Text_8'  => "dentalblue_plan_{$plan}_custom_diaprev_out",
					'Text_9'  => "dentalblue_plan_{$plan}_custom_filling_in",
					'Text_10' => "dentalblue_plan_{$plan}_custom_filling_out",
					'Text_11' => "dentalblue_plan_{$plan}_custom_surg_in",
					'Text_12' => "dentalblue_plan_{$plan}_custom_surg_out",
					'Text_13' => "dentalblue_plan_{$plan}_custom_endo_in",
					'Text_14' => "dentalblue_plan_{$plan}_custom_endo_out",
					'Text_15' => "dentalblue_plan_{$plan}_custom_perio_in",
					'Text_16' => "dentalblue_plan_{$plan}_custom_perio_out",
					'Text_17' => "dentalblue_plan_{$plan}_custom_prosth_in",
					'Text_18' => "dentalblue_plan_{$plan}_custom_prosth_out",
					'Text_19' => "dentalblue_plan_{$plan}_custom_ortho_in",
					'Text_20' => "dentalblue_plan_{$plan}_custom_ortho_out",
					'Text_21' => "dentalblue_plan_{$plan}_custom_chad_in",
					'Text_22' => "dentalblue_plan_{$plan}_custom_chad_out",
					'Text_23' => "dentalblue_plan_{$plan}_custom_orthomax_in",
					'Text_24' => "dentalblue_plan_{$plan}_custom_orthomax_out",
				);
				$index = 25;
				foreach(array('rest', 'surg', 'peri', 'endo', 'prosth', 'ortho') as $name) {
					if(isset($values->{"dentalblue_plan_{$plan}_custom_wait_{$name}"})) {
						$mapConfig['Text_'.$index] = "dentalblue_plan_{$plan}_custom_wait_{$name}";
						$mapConfig['Text_'.($index+1)] = "dentalblue_plan_{$plan}_custom_wait_{$name}_in";
						$mapConfig['Text_'.($index+2)] = "dentalblue_plan_{$plan}_custom_wait_{$name}_out";
						$index += 3;
					}
				}
				$filename = $this->build_workflow($values, '-5-dbplan'.$plan, 'CAproposal_5B.indd', $mapConfig);
				break;
			case "upload":
				$filename = make_path_safe($values->{"dentalblue_plan_{$plan}_upload"});
				break;
			default:
				return FALSE;
		}
		return array($filename => 'Dental Blue Proposed Plan '.$plan);
	}

	private function generateDentalBlueRiders($values) {
		$riders = array();
		$files = array();
		for($plan=1; $plan<=4; $plan++) {
			if(in_array('plan_'.$plan, $values->dentalblue_plans)) {
				if(!empty($values->{"dentalblue_plan_{$plan}_addRiders"}) && !empty($values->{"dentalblue_plan_{$plan}_riders"})) {
					foreach($values->{"dentalblue_plan_{$plan}_riders"} as $rider) {
						$riders[] = $rider;
					}
				}
			}
		}
		if(!empty($riders)) {
			foreach($this->db_riders as $rider => $label) {
				if(in_array($rider, $riders)) {
					$filename = 'DB_' . $rider;
					$filename = $this->findStatic($filename);
					if($filename == FALSE) {
						return FALSE;
					}
					$files[$filename] = "Dental Blue Rider $rider";
				}
			}
		}
		return $files;
	}

	//----------------------------------------------------------------------

	private function generateDentalNet($values) {
		if(!in_array('dentalnet', $values->modules)) {
			// no dental net section
			return;
		}
		// build summary page
		$mapConfig = array(
			'Text_1' => 'group_name',
			'Text_2' => 'effective_date',
			'Text_6' => 'number_of_employees',
			'Text_36' => 'dental_plans_years',
			'Text_37' => 'zip_code',
			'Text_38' => 'sic',
		);
		$this->_addTierTitles($values, $mapConfig);
		$plans = array(
			'1' => 39,
			'2' => 46,
			'3' => 53,
			'4' => 60
		);
		$tier3 = $values->rate_tier != 4;
		foreach($plans as $plan => $index) {
			if(in_array('plan_'.$plan, $values->dentalnet_plans)) {
				$name = '';
				switch($values->{"dentalnet_plan_{$plan}_type"}) {
					case "standard":
						$name = 'Dental Net&#174; 2000 Series Plan ' . $values->{"dentalnet_plan_{$plan}_name"};
						break;
					case "custom":
						$name = $values->{"dentalnet_plan_{$plan}_custom_name"};
						break;
					case "upload":
						$name = $values->{"dentalnet_plan_{$plan}_upload_name"};
						break;
				}
				$mapConfig['Text_'.$index] = $name;
				$mapConfig['Text_'.($index+1)] = "dentalnet_plan_{$plan}_empvol";
				$mapConfig['Text_'.($index+2)] = "billing_frequency";
				$mapConfig['$Text_'.($index+3)] = "dentalnet_plan_{$plan}_t_emponly";
				$mapConfig['$Text_'.($index+4)] = $tier3 ? "dentalnet_plan_{$plan}_t3empplusone" : "dentalnet_plan_{$plan}_t4empplusone";
				$mapConfig['$Text_'.($index+5)] = $tier3 ? "dentalnet_plan_{$plan}_t_empplusfam" : "dentalnet_plan_{$plan}_t4emppluschild";
				$mapConfig['$Text_'.($index+6)] = $tier3 ? "" : "dentalnet_plan_{$plan}_t_empplusfam";
			} else {
				for($i=0; $i<7; $i++) {
					$mapConfig['Text_'.($index+$i)] = '';
				}
			}
		}
		$files = $this->build_workflow($values, '-4-dnsum', 'CAproposal_6.indd', $mapConfig, 'Dental Net Summary');
		for($plan=1; $plan<=4; $plan++) {
			$files = array_merge($files, (array)$this->generateDentalNetPlan($values, $plan));
		}
		return $files;
	}

	private function generateDentalNetPlan($values, $plan) {
		if(!in_array('plan_'.$plan, $values->dentalnet_plans)) {
			return;
		}
		switch($values->{"dentalnet_plan_{$plan}_type"}) {
			case "standard":
				$filename = 'Dental_Net_HMO_'.make_path_safe($values->{"dentalnet_plan_{$plan}_name"});
				$filename = $this->findStatic($filename);
				if($filename == FALSE) {
					return FALSE;
				}
				break;
			case "custom":
				$mapConfig = array(
					'Text_43' => "dentalnet_plan_{$plan}_custom_name",
					'Text_44' => "effective_date",
					'Text_2'  => "dentalnet_plan_{$plan}_custom_groupsize",
					'Text_3'  => "dentalnet_plan_{$plan}_custom_annben",
					'Text_4'  => "dentalnet_plan_{$plan}_custom_officevisit",
					'Text_5'  => "dentalnet_plan_{$plan}_custom_filling",
					'Text_6'  => "dentalnet_plan_{$plan}_custom_surg",
					'Text_7'  => "dentalnet_plan_{$plan}_custom_endo",
					'Text_8'  => "dentalnet_plan_{$plan}_custom_perio",
					'Text_9'  => "dentalnet_plan_{$plan}_custom_prosth1",
					'Text_10' => "dentalnet_plan_{$plan}_custom_prosth2",
					'Text_11' => "dentalnet_plan_{$plan}_custom_ortho",
					'Text_12' => "dentalnet_plan_{$plan}_custom_chad",
					'Text_13' => "dentalnet_plan_{$plan}_custom_ortholife",
					'Text_14' => "dentalnet_plan_{$plan}_custom_preortho",
					'Text_15' => "dentalnet_plan_{$plan}_custom_orthoret",
				);
				$filename = $this->build_workflow($values, '-5-dnplan'.$plan, 'CAproposal_6B.indd', $mapConfig);
				break;
			case "upload":
				$filename = make_path_safe($values->{"dentalnet_plan_{$plan}_upload"});
				break;
			default:
				return FALSE;
		}
		return array($filename => 'Dental Net Proposed Plan '.$plan);
	}

	//----------------------------------------------------------------------

	private function generateBlueViewVision($values) {
		if(!in_array('bvvision', $values->modules)) {
			// no blue view vision section
			return;
		}
		// build summary page
		$mapConfig = array(
			'Text_1' => 'group_name',
			'Text_2' => 'effective_date',
			'Text_6' => 'number_of_employees',
			'Text_36' => 'vision_plans_years',
			'Text_37' => 'zip_code',
			'Text_38' => 'sic',
		);
		$this->_addTierTitles($values, $mapConfig);
		$plans = array(
			'1' => 95,
			'2' => 102,
			'3' => 109,
			'4' => 116
		);
		$tier3 = $values->rate_tier != 4;
		foreach($plans as $plan => $index) {
			if(in_array('plan_'.$plan, $values->bvvision_plans)) {
				$name = '';
				switch($values->{"bvvision_plan_{$plan}_type"}) {
					case "standard":
						$name = str_replace(array('BV', 'MO'), array('BV ', 'MO '), $values->{"bvvision_plan_{$plan}_name"});
						break;
					case "custom":
						$name = $values->{"bvvision_plan_{$plan}_custom_name"};
						break;
					case "upload":
						$name = $values->{"bvvision_plan_{$plan}_upload_name"};
						break;
				}
				$mapConfig['Text_'.$index] = $name;
				$mapConfig['Text_'.($index+1)] = "bvvision_plan_{$plan}_empvol";
				$mapConfig['Text_'.($index+2)] = "billing_frequency";
				$mapConfig['$Text_'.($index+3)] = "bvvision_plan_{$plan}_t_emponly";
				$mapConfig['$Text_'.($index+4)] = $tier3 ? "bvvision_plan_{$plan}_t3empplusone" : "bvvision_plan_{$plan}_t4empplusone";
				$mapConfig['$Text_'.($index+5)] = $tier3 ? "bvvision_plan_{$plan}_t_empplusfam" : "bvvision_plan_{$plan}_t4emppluschild";
				$mapConfig['$Text_'.($index+6)] = $tier3 ? "" : "bvvision_plan_{$plan}_t_empplusfam";
			} else {
				for($i=0; $i<7; $i++) {
					$mapConfig['Text_'.($index+$i)] = '';
				}
			}
		}
		$files = $this->build_workflow($values, '-4-bvsum', 'CAproposal_7.indd', $mapConfig, 'Blue View Vision Summary');
		for($plan=1; $plan<=4; $plan++) {
			$files = array_merge($files, (array)$this->generateBlueViewVisionPlan($values, $plan));
		}
		return $files;
	}

	private function generateBlueViewVisionPlan($values, $plan) {
		if(!in_array('plan_'.$plan, $values->bvvision_plans)) {
			return;
		}
		switch($values->{"bvvision_plan_{$plan}_type"}) {
			case "standard":
				$filename = make_path_safe($values->{"bvvision_plan_{$plan}_name"});
				$filename = $this->findStatic($filename);
				if($filename == FALSE) {
					return FALSE;
				}
				break;
			case "custom":
				$mapConfig = array(
					'Text_10' => "bvvision_plan_{$plan}_custom_year",
					'Text_11' => "bvvision_plan_{$plan}_custom_single",
					'Text_12' => "bvvision_plan_{$plan}_custom_bifocal",
					'Text_13' => "bvvision_plan_{$plan}_custom_trifocal",
				);
				$filename = $this->build_workflow($values, '-5-bvplan'.$plan, 'CAproposal_7B.indd', $mapConfig);
				break;
			case "upload":
				$filename = make_path_safe($values->{"bvvision_plan_{$plan}_upload"});
				break;
			default:
				return FALSE;
		}
		return array($filename => 'Blue View Vision Proposed Plan '.$plan);
	}

	//----------------------------------------------------------------------

	private function generateLifeAndDisability($values) {
		$files = array();
		if(in_array('life', $values->modules) || in_array('disability', $values->modules)) {
			$nomed = '';
			if($values->hasmed == 'no') {
				$nomed = '_NOMED';
			}
			if(in_array('life', $values->modules)) {
				if(in_array('disability', $values->modules)) {
					$file = 'LIFE_AND_DISABILITY'.$nomed;
					$name = 'Life & Disability';
				} else {
					$file = 'LIFE';
					$name = 'Life';
				}
			} else if(in_array('disability', $values->modules)) {
				$file = 'DISABILITY'.$nomed;
				$name = 'Disability';
			}
			$file = $this->findStatic($file);
			if($file == FALSE) {
				return FALSE;
			}
			$files[$file] = $name . ' Overview';
			$files[$values->lifedisability_upload] = $name . ' Quote';
		}
		return $files;
	}

	//----------------------------------------------------------------------

	private function _addTierTitles($values, &$mapConfig) {
		$mapConfig['Text_140'] = 'Employee Only: ';
		if($values->rate_tier == 4) {
			$mapConfig['Text_141'] = 'Employee + Spouse: ';
			$mapConfig['Text_142'] = 'Employee + Child(ren): ';
			$mapConfig['Text_143'] = 'Employee + Spouse + Child(ren): ';
		} else {
			$mapConfig['Text_141'] = 'Employee + 1: ';
			$mapConfig['Text_142'] = 'Employee + Spouse + Child(ren): ';
			$mapConfig['Text_143'] = '';
		}
	}

}