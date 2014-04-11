<?php

/**
 * Proposals - GA Integrated
 * Product Customizer
 *
 * @author jmadrigal
 */
class CustomProduct extends ProposalsProductTemplate {

	public $selected_options_field = 'products';

	private $prop_renew;

	//======================================================================
	// Configure the Primary Form
	//======================================================================

	public function configureForm($is_preview = FALSE, $is_send = FALSE) {
		// used several times within
		$this->CI->load->helper('file');

		// configure the pages of the wizard
		if(!$is_send) {
			// Disabled for now
			//$this->setupIAvenue();
			$this->setupBasicInfo();
			$this->setupFinancialSummary();
			$this->setupProposalInfo();
			$this->setupDental();
			$this->setupVision();
			$this->setupMedical();
			$this->setupLifeAndDisabilityUpload();
			$this->setupPreapproved();
			$this->setupUpload();
			$this->setupSort();
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
			$this->setupSendProposal('Proposal', $body_text, 'Georgia Combined Proposal', 'products');
		}
	}

	//----------------------------------------------------------------------

	private function _coverPreview($file) {
		$link = getDownloadURL(PDFSTATIC.$file);
		$link .= '?nocache=' . time();
		$preview_link = getPreviewPopupURL(PDFSTATIC.$file);
		$preview_link .= '?nocache=' . time();
		$anchor_options = array(
			'class' => 'previewPopup',
			'rel' => site_url($preview_link),
			'target' => '_blank'
		);
		$image_options = array(
			'width' => 24,
			'height' => 24
		);
		return icon_link($link, 'images/icons/pdf.png', '', $anchor_options, $image_options);
	}

	private function setupBasicInfo() {
		$page = $this->formidable->addWizardPage('Basic Information');
		$page->add('group_name', 'Group Name', 'text', '', array('required'));
		$page->add('effective_date', 'Effective Date', 'date', '', array('required', 'valid_date'), array('field_after' => ' <em>mm/dd/yyyy</em>'));
		$page->add('proposal_type', 'Type of Case', 'radios', '', array('required'), array(
			'values' => array(
				'proposal' => 'Proposal',
				'renewal' => 'Renewal'
			),
			'wrap' => TRUE
		));

		$page->addHeader('Cover Information');
		$covers = array();
		for($i=1; $i<=5; $i++) {
			$covers[$i] = "Cover #$i &nbsp;&nbsp;&nbsp; ".$this->_coverPreview("LG_proposal_covers_$i.pdf");
		}
		$page->add('cover_style', 'Select Cover Style', 'radios', '1', array('required'), array(
			'values' => $covers,
			'separator' => ''
		));

		$page->addHeader('Proposal/Renewal Content');

		$page->addNote('Select the content products you want to include in the proposal. You must choose at least one module.');
		$page->add('products', 'Products', 'checkboxes', '', array('required'), array(
			'values' => array(
				'dental' => 'Dental',
				'vision' => 'Vision',
				'medical' => 'Medical',
				'life' => 'Life',
				'disability' => 'Disability'
			),
			'wrap' => TRUE
		));
	}

	//----------------------------------------------------------------------

	private function setupFinancialSummary() {
		$types = array(
			'dental' => array('Medical + Dental', 1, 'products=dental'),
			'life' => array('Life', 1, 'products=life')
		);
		
		$page = $this->formidable->addWizardPage('One Solution Savings Financial Summary', array(), 'products=dental,life');
		$page->addNote("Please choose a savings statement and add the appropriate information.");
		
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
		$group = $page->addGroup('', 'products=dental,vision,medical', FALSE);
			//$group->add('billing_frequency', 'Billing Frequency', 'radios', 'Monthly Rates:', '', array('values' => array('Monthly Rates:' => 'Monthly', 'Bi-Weekly Rates:' => 'Bi-Weekly', 'Tenthly Rates:' => 'Tenthly')));
			$group->add('zip_code', 'Zip Code', 'zip', '', array('required', 'max_length' => 10, 'min_length' => 5), array('field_after' => ' <em>Ex: 12345 or 12345-0001</em>'));
			$group->add('sic', 'SIC', 'text', '', array('required'));
	}

	//----------------------------------------------------------------------

	private function setupDental() {
		$page = $this->formidable->addWizardPage('Dental Plans', '', 'products=dental');
		$page->addNote('Please configure your proposed Dental plans.');

		$options = $page->addFieldset('Common Options');
			$options->add('dental_year_guarantee', 'Year Rate Guarantee', 'radios', '1', '', array('values' => array('1' => '1 Year', '2' => '2 Years')));
			$options->add('dental_rate_tiers', '# of Rate Tiers', 'radios', '3', '', array('values' => array('3' => '3 Tiers', '4' => '4 Tiers' )));

		$static_files = get_filenames(PDFSTATIC);
		$templates = array();
		foreach($static_files as $file) {
			if(strpos($file, 'GAINT_Dental') !== 0 ||
					(strpos($file, 'GAINT_Dental_SG') === 0 && $this->CI->user->current->state->group == 'Large Group') ||
					(strpos($file, 'GAINT_Dental_LG') === 0 && $this->CI->user->current->state->group == 'Small Group')) {
				// ignore these files
				continue;
			}
			$filename_url = preg_replace('/^(GAINT_Dental(_SG|_LG)?)\s*/i', '$1/', $file);
			$templates[$filename_url] = preg_replace('/\.(docx?|xlsx?)$/', '', preg_replace('/^GAINT_Dental(_SG|_LG)?\s*/i', '', $file));
		}
		asort($templates);
		$templates = array('' => '(Please Select a Template)') + $templates;

		$plansGroup = $page->addFieldset('Proposed Plans');
			$plans = $plansGroup->addOptionalGroups('dental_plans', 'plan_1', '', TRUE);
			for($i=1; $i<=4; $i++) {
				$plan = $plans->addGroup("plan_$i", "Proposed Plan $i", 'proposed_plan');
				
				$plan->addHeader('Plan Templates');
				$plan->addNote('A selection of plan templates are available to choose from.  If necessary, please select one from the list below, click <strong>Download Template</strong>, and upload it with your modifications.');
				$plan->addMultifieldRow("dental_template_row_$i", FALSE, array('row_classes' => 'row_dental_template_row'));
					$plan->addMultifieldItem("dental_template_row_$i", "dental_template_$i", 'dropdown', '', '', array('values' => $templates, FORM_HTML_FIELD_AFTER => ' &nbsp; ', FORM_HTML_FIELD_BEFORE => '<img src="'.site_url('images/icons/doc.png').'"/> '));
					$plan->addMultifieldItem("dental_template_row_$i", "dental_template_{$i}_button", 'button', 'Download Template', NULL, array('class' => 'dental_template_button', 'onlyEnableIf' => "dental_template_$i"));

				$plan->addNote('Upload a custom rate quote in either Word (.doc, .docx), Excel (.xls, .xlsx), or Adobe PDF (.pdf) format.');
				$plan->add("dental_plan_{$i}_upload_name", 'Plan Name', 'text', '', array('required'));
				$this->addDocumentAndExcelUpload($plan, "dental_plan_{$i}_upload");
				$this->_addRates($plan->addFieldset(''), 'dental', $i);
			}
	}

	public function get_dental_template($prefix, $name) {
		$filename = make_path_safe($prefix.' '.$name, PDFSTATIC);
		if(file_exists($filename)) {
			$this->CI->load->helper('filedownload');
			sendFile($filename);
		} else {
			show_404();
		}
	}

	//----------------------------------------------------------------------

	private function setupVision() {
		$page = $this->formidable->addWizardPage('Vision Plans', '', 'products=vision');
		$page->addNote('Please configure your proposed Vision plans.');
		
		$letters = createOptions('Select One', 'A', 'B', 'C', 'D');
		$nums = createOptions('Select One', '5-0', '10-0', '10-10', '10-20', '20-20');

		$options = $page->addFieldset('Common Options');
			$options->add('vision_year_guarantee', 'Year Rate Guarantee', 'radios', '1', '', array('values' => array('1' => '1 Year', '2' => '2 Years')));
			$options->add('vision_rate_tiers', '# of Rate Tiers', 'radios', '3', '', array('values' => array('3' => '3 Tiers', '4' => '4 Tiers' )));
		$plansGroup = $page->addFieldset('Proposed Plans');
			$plans = $plansGroup->addOptionalGroups('vision_plans', 'plan_1', '', TRUE);

			for($i=1; $i<=4; $i++) {
				$plan = $plans->addGroup("plan_$i", "Proposed Plan $i", 'proposed_plan');
				$plan->addMultiDropdownRow("vision_plan_{$i}_name_row", 'Plan Name', array(
						"vision_plan_{$i}_letter" => array('values' => $letters, FORM_HTML_FIELD_BEFORE => '<strong>Blue View Vision</strong> &nbsp;'),
						"vision_plan_{$i}_nums" => array('values' => $nums, FORM_HTML_FIELD_BEFORE => '&nbsp;&nbsp;')
					), array('required'), array('class' => 'required'));
				$this->_addRates($plan, 'vision', $i);
			}
	}

	//----------------------------------------------------------------------

	private function setupMedical() {
		$page = $this->formidable->addWizardPage('Medical Plans', '', 'products=medical');
		$page->addNote('Please select which of the following standard medical plans you would like to propose.<br/>You can also add a non-standard plan to be included on its own, after the standard ones.');
		$plans_box = $page->addFieldset('');

		$sort_page = $this->formidable->addWizardPage('Medical Plans: Order', '', 'show_medical_plans_sort=true');
		$sort_page->addNote('For each group you\'ve selected, you can choose the order the plans should appear in the medical plans grid. (Plans will be listed left-to-right, with one group per page.)');
		$page->add('show_medical_plans_sort', FALSE, 'hidden', 'false');

		$base_path = DESIGNMERGE_TEMPLATE.'GAINT/Medical Plans/';

		$this->CI->load->helper('directory');

		$plangroups = directory_map($base_path);
		ksort($plangroups);
		$group = $this->CI->user->current->state->group;

		$optplans = $plans_box->addOptionalGroups('medical_plans', '', array('required'), TRUE);

		foreach($plangroups as $plangroup => $plans) {
			if(!is_dir($base_path.$plangroup) || strpos($plangroup, $group) === FALSE) {
				continue;
			}
			$name = preg_replace("/^$group\\s*/i", '', $plangroup);
			$id = preg_replace('/[^a-z0-9_]/i', '', $plangroup);
			$section = $optplans->addGroup($plangroup, $name);
			sort($plans);
			$plan_options = array();
			foreach($plans as $plan) {
				if(!is_string($plan)) {
					continue;
				}
				$plan_options[$plan] = preg_replace('/\.[a-z]*$/i', '', $plan);
			}
			$section->add("medical_plans_$id", FALSE, 'checkboxes', '', array('required'), array('values' => $plan_options, 'separator' => '', 'row_classes' => 'medical_plans'));

			$sort_group = $sort_page->addFieldset($name . ' Sort Order', array(), "medical_plans=$plangroup", FALSE);
			$sort_group->add("medical_plans_{$id}_order", FALSE, 'docsorter');
		}

		// add in the upload option
		$section = $optplans->addGroup('nonstandard', 'Non-Standard Custom Plan');
			$section->addNote('Upload a custom rate quote in either Word (.doc, .docx), Excel (.xls, .xlsx) or Adobe PDF (.pdf) format.  You can download the template for the medical plan below.');
			$download_link = '<tr><td colspan="3" class="docupload_link">'
							.	'<a href="'.current_url().'/get_medical_template/GA Non-standard grid.xls" target="_blank">'
							.		'<img src="'.site_url('images/icons/xls.png').'" width="24" height="24"/> GA Non-standard grid.xls'
							.	'</a>'
							.'</td></tr>';
			$section->addHTML($download_link);
			$this->addDocumentAndExcelUpload($section, "medical_plans_custom_upload");

		$sort_page->addFieldset('Non-Standard Custom Plan', array(), "medical_plans=nonstandard")->addNote("Your non-standard custom plan goes at the end.");
	}

	public function get_medical_template() {
		$filename = DESIGNMERGE_TEMPLATE . 'GAINT/Medical Plans/GA Non-standard grid.xls';
		if(file_exists($filename)) {
			$this->CI->load->helper('filedownload');
			sendFile($filename);
		} else {
			show_404();
		}
	}

	//----------------------------------------------------------------------

	private function setupLifeAndDisabilityUpload() {
		$page = $this->formidable->addWizardPage('Upload Life & Disability Quote', array('id' => 'page_ldquote'), 'products=life,disability');
		$page->addNote('Upload a custom <span id="ldquote_text">Life &amp; Disability</span> quote in either Word (<em>DOC</em>, <em>DOCX</em>), Excel (<em>XLS</em>, <em>XLSX</em>), or Adobe PDF (.pdf) format.');
		$this->addDocumentAndExcelUpload($page, "lifedisability_upload");
	}


	//----------------------------------------------------------------------

	private function setupPreapproved() {
		$page = $this->formidable->addWizardPage('Pre-approved Documents', array('id' => 'approvedDocsPage'));

		$page->addNote('Select from the following pre-approved documents to insert them into the final PDF.<br/>(You will be able to choose custom documents and change the insert order next.)');

		$approved_docs = array();
		$static_files = get_filenames(PDFSTATIC);
		foreach($static_files as $file) {
			if(stripos($file, 'GAINT_Preapproved') !== 0) {
				continue;
			}
			$name = str_ireplace(array('GAINT_Preapproved ', '.pdf'), '', $file);
			$approved_docs[$file] = '&nbsp;&nbsp;'.$this->_coverPreview($file)." &nbsp; $name";
		}

		$page->add("approved_docs", FALSE, 'checkboxes', '', '', array(
			'values' => $approved_docs,
			'wrap' => TRUE
		));
	}

	//----------------------------------------------------------------------

	private function setupUpload() {
		$page = $this->formidable->addWizardPage('Exhibits');
		$page->addNote('Click Browse to select documents to upload (financial, savings calculator, benefit summary, etc).  You may upload Word (<em>DOC</em>, <em>DOCX</em>), Excel (<em>XLS</em>, <em>XLSX</em>) or PDF files.');
		$page->addDocumentUpload('upload_docs', FALSE, TRUE, 50, FALSE, array('path' => 'proposals/'.$this->product->products_id, 'types' => 'doc|docx|pdf|rtf|rtx|xls|xlsx'));
	}

	//----------------------------------------------------------------------

	private function setupSort() {
		$page = $this->formidable->addWizardPage('Exhibits Sort Order', array('id' => 'documentSortPage'), 'show_docs_list=true');
		$page->addNote('Click and drag the documents to organize them in the order they should appear in the final document.');
		$page->add('docs_list', FALSE, 'docsorter');
		$page->add('show_docs_list', FALSE, 'hidden', 'false');
	}

	//----------------------------------------------------------------------

	/**
	 * @param Formidable $plan
	 * @param string $idbase
	 * @param int $i
	 */
	private function _addRates($plan, $idbase, $i) {
		$plan->add("{$idbase}_plan_{$i}_empvol", FALSE, 'radios', 'Employer Paid', '', array('values' => array('Employer Paid' => 'Employer Paid', 'Voluntary' => 'Voluntary')));
		$plan->add("{$idbase}_plan_{$i}_t_emponly", 'Employee Only', 'currency', '', array('required'));
		$tier3 = $plan->addGroup('', $idbase.'_rate_tiers=3', FALSE);
			$tier3->add("{$idbase}_plan_{$i}_t3empplusone", 'Employee + 1', 'currency', '', array('required'));
		$tier4 = $plan->addGroup('', $idbase.'_rate_tiers=4', FALSE);
			$tier4->add("{$idbase}_plan_{$i}_t4empplusone", 'Employee + Spouse', 'currency', '', array('required'));
			$tier4->add("{$idbase}_plan_{$i}_t4emppluschild", 'Employee + Child(ren)', 'currency', '', array('required'));
		$plan->add("{$idbase}_plan_{$i}_t_empplusfam", 'Employee + Spouse + Child(ren)', 'currency', '', array('required'));
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

		$this->prop_renew = $values->proposal_type == 'proposal' ? 'Proposal' : 'Renewal';

		$files = array_merge(
			(array)$this->generateCover($values),
			(array)$this->generateContent($values),
			(array)$this->generateStatements($values),
			(array)$this->generateDental($values),
			(array)$this->generateVision($values),
			(array)$this->generateMedical($values),
			(array)$this->generateLifeAndDisability($values),
			(array)$this->generateExhibits($values)
		);
		if(in_array(FALSE, $files)) {
			print_r($files);
			die();
			// an error occurred
			return 'An error occurred while generating one of your products.';
		}
		return $files;
	}

	//----------------------------------------------------------------------

	private function generateCover($values) {
		//return array('LG_proposal_covers_'.$values->cover_style.'.pdf' => 'Proposal Cover');
		return $this->build_workflow(
			$values, '1-cover', 'LG_proposal_covers_'.$values->cover_style.'.indd',
			array(
				'Text_1' => 'group_name',
				'/Text_2' => 'effective_date'
			),
			'Proposal Cover'
		);
	}

	//----------------------------------------------------------------------

	private function generateContent($values) {
		return $this->build_workflow(
			$values, '1-cover', "GAINT {$this->prop_renew} Content.indd",
			array(
				'Text_5' => 'group_name',
				'/Text_2' => 'effective_date'
			),
			'Proposal Content'
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
		if(in_array('dental', $values->products) && $values->st1_h_dental_enabled) {
			$map['Text_9'] = '=Medical + Dental';
			$map['Text_10'] = '=1%';
			$map['$Text_11'] = 'st1_h_dental_annual_savings';
			$map['$Text_12'] = 'st1_h_dental_cost';
			$map['%Text_13'] = 'st1_h_dental_specialty_savings';
			$total_percent += 1;
			$total_money += floatval($values->st1_h_dental_annual_savings);
		}
		if(in_array('life', $values->products) && $values->st1_h_life_enabled) {
			$map['Text_14'] = '=Life';
			$map['Text_15'] = '=1%';
			$map['$Text_16'] = 'st1_h_life_annual_savings';
			$map['$Text_17'] = 'st1_h_life_cost';
			$map['%Text_18'] = 'st1_h_life_specialty_savings';
			$total_percent += 1;
			$total_money += floatval($values->st1_h_life_annual_savings);
		}

		// total savings
		$map['%Text_34'] = '='.$total_percent;
		$map['$Text_35'] = '='.$total_money;

		switch($values->statement_type) {
			case 'statement_1':
				return $this->build_workflow($values, '2-stmt1', 'GAINT Calculator 1.indd', $map, 'Statement 1');
			case 'statement_2':
				return $this->build_workflow($values, '2-stmt2', 'GAINT Calculator 2.indd', $map, 'Statement 2');
			default:
				return;
		}
	}

	//----------------------------------------------------------------------

	private function generateDental($values) {
		$this->CI->load->helper('number');
		if(!in_array('dental', $values->products)) {
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
			if(in_array('plan_'.$plan, $values->dental_plans)) {
				$name = $values->{"dental_plan_{$plan}_upload_name"};
				$mapConfig['Text_'.$index] = 'Proposed Plan '.$plan . ' <0x2014> ' . $name . ' - ' . $values->{"dental_plan_{$plan}_empvol"};
				$mapConfig['Text_'.($index+3)] = "billing_frequency";
				$rates = 'Employee: ' . currency_format($values->{"dental_plan_{$plan}_t_emponly"});
				$tier3 = $values->dental_rate_tiers != 4;
				if($tier3) {
					$rates .= "\nEmployee + 1: " . currency_format($values->{"dental_plan_{$plan}_t3empplusone"});
					$rates .= "\nEmployee + Spouse + Child(ren): " . currency_format($values->{"dental_plan_{$plan}_t_empplusfam"});
				} else {
					$rates .= "\nEmployee + Spouse: " . currency_format($values->{"dental_plan_{$plan}_t4empplusone"});
					$rates .= "\nEmployee + Child(ren): " . currency_format($values->{"dental_plan_{$plan}_t4emppluschild"});
					$rates .= "\nEmployee + Spouse + Child(ren): " . currency_format($values->{"dental_plan_{$plan}_t_empplusfam"});
				}
				$mapConfig['Text_'.($index+4)] = $rates;
				$index += 5;
			}
		}
		//$files = $this->build_workflow($values, '-4-dbsum', 'CAproposal_5-3.indd', $mapConfig, 'Dental Rates');
		$files = array();
		for($plan=1; $plan<=4; $plan++) {
			if(in_array('plan_'.$plan, $values->dental_plans)) {
				$filename =  make_path_safe($values->{"dental_plan_{$plan}_upload"});
				$files[$filename] = 'Dental Proposed Plan '.$plan;
			}
		}
		return $files;
	}

	//----------------------------------------------------------------------

	private function generateVision($values) {
		if(!in_array('vision', $values->products)) {
			// no vision section
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
		$this->_addTierTitles($values, $mapConfig, $values->vision_rate_tiers);
		$plans = array(
			'1' => 95,
			'2' => 102,
			'3' => 109,
			'4' => 116
		);
		$tier3 = $values->vision_rate_tiers != 4;
		foreach($plans as $plan => $index) {
			if(in_array('plan_'.$plan, $values->vision_plans)) {
				$name = 'Blue View Vision '.$values->{"vision_plan_{$plan}_letter"}.' '.$values->{"vision_plan_{$plan}_nums"};
				$mapConfig['Text_'.$index] = $name;
				$mapConfig['Text_'.($index+1)] = "vision_plan_{$plan}_empvol";
				$mapConfig['Text_'.($index+2)] = "billing_frequency";
				$mapConfig['$Text_'.($index+3)] = "vision_plan_{$plan}_t_emponly";
				$mapConfig['$Text_'.($index+4)] = $tier3 ? "vision_plan_{$plan}_t3empplusone" : "vision_plan_{$plan}_t4empplusone";
				$mapConfig['$Text_'.($index+5)] = $tier3 ? "vision_plan_{$plan}_t_empplusfam" : "vision_plan_{$plan}_t4emppluschild";
				$mapConfig['$Text_'.($index+6)] = $tier3 ? "" : "vision_plan_{$plan}_t_empplusfam";
			} else {
				for($i=0; $i<7; $i++) {
					$mapConfig['Text_'.($index+$i)] = '';
				}
			}
		}
		//$files = $this->build_workflow($values, '-4-bvsum', 'CAproposal_7.indd', $mapConfig, 'Blue View Vision Summary');
		$files = array();
		for($plan=1; $plan<=4; $plan++) {
			if(in_array('plan_'.$plan, $values->vision_plans)) {
				$filename = 'GAINT Blue View Vision '.$values->{"vision_plan_{$plan}_letter"}.' '.$values->{"vision_plan_{$plan}_nums"}.'.pdf';
				$filename = make_path_safe($filename);
				$filename = $this->findStatic($filename);
				if($filename == FALSE) {
					$files[] = FALSE;
				} else {
					$files[$filename] = 'Vision Proposed Plan '.$plan;
				}
			}
		}
		return $files;
	}

	//----------------------------------------------------------------------

	private function generateMedical($values) {
		if(!in_array('medical', $values->products)) {
			// no vision section
			return;
		}
		
		$files = array();

		$base_path = DESIGNMERGE_TEMPLATE.'GAINT/Medical Plans/';

		$plangroups = directory_map($base_path);
		ksort($plangroups);
		$group = $this->CI->user->current->state->group;

		$medical_index = 1;

		foreach($plangroups as $plangroup => $plans) {
			// first, determine if a specific plan group is in use...
			if(!is_dir($base_path.$plangroup)
					|| strpos($plangroup, $group) === FALSE
					|| !in_array($plangroup, $values->medical_plans)) {
				continue;
			}
			$name = preg_replace("/^$group\\s*/i", '', $plangroup);
			$id = preg_replace('/[^a-z0-9_]/i', '', $plangroup);

			// OK, plangroup is in use, now group the plans into sets of 6
			$selected_plans_index = 1;
			$selected_plans = array(1 => array());
			foreach(explode('|', $values->{"medical_plans_{$id}_order"}) as $planvals) {
				list($file, $plan_name) = explode(':', $planvals);
				if(!in_array($file, $plans)) {
					// validate input here
					$files[] = FALSE;
					continue;
				}
				if(count($selected_plans[$selected_plans_index]) == 6) {
					$selected_plans_index++;
					$selected_plans[$selected_plans_index] = array();
				}
				$selected_plans[$selected_plans_index][$plan_name] = $file;
			}

			// now queue up a workflow for each page
			foreach($selected_plans as $page_index => $planset) {
				$contd = '';
				if($page_index > 1) {
					$contd = ' (cont\'d)';
				}
				$map = array(
					'Text_13' => "=$name$contd",
					'Text_14' => 'effective_date'
				);
				$index = 1;
				foreach($planset as $plan_name => $file) {
					$map['Text_'.$index] = $plan_name;
					$map['Text_'.($index+1)] = file_get_contents("{$base_path}{$plangroup}/{$file}");
					$index += 2;
				}

				$jobname = $this->build_workflow($values, "5-med-$medical_index-$page_index", 'GAINT Grid Template.indd', $map);
				$files[$jobname] = "Medical Plans - $name $page_index";
			}

			$medical_index++;
		}
		
		if(in_array('nonstandard', $values->medical_plans)) {
			// now add in the uploaded doc
			$files[$values->medical_plans_custom_upload] = 'Medical Plans - Non-Standard Custom Plan';
		}

		return $files;
	}
	
	//----------------------------------------------------------------------

	private function generateLifeAndDisability($values) {
		$files = array();
		if(in_array('life', $values->products) || in_array('disability', $values->products)) {
			$nomed = '';
			//if($values->hasmed == 'no') {
			//	$nomed = '_NOMED';
			//}
			if(in_array('life', $values->products)) {
				if(in_array('disability', $values->products)) {
					$file = 'LIFE_AND_DISABILITY'.$nomed;
					$name = 'Life & Disability';
				} else {
					$file = 'LIFE';
					$name = 'Life';
				}
			} else if(in_array('disability', $values->products)) {
				$file = 'DISABILITY'.$nomed;
				$name = 'Disability';
			}
			$file = $this->findStatic($file);
			if($file == FALSE) {
				return FALSE;
			}
			//$files[$file] = $name . ' Overview';
			$files[$values->lifedisability_upload] = $name . ' Quote';
		}
		return $files;
	}

	//----------------------------------------------------------------------

	private function _addTierTitles($values, &$mapConfig, $rate_tiers) {
		$mapConfig['Text_140'] = 'Employee Only: ';
		if($rate_tiers == 4) {
			$mapConfig['Text_141'] = 'Employee + Spouse: ';
			$mapConfig['Text_142'] = 'Employee + Child(ren): ';
			$mapConfig['Text_143'] = 'Employee + Spouse + Child(ren): ';
		} else {
			$mapConfig['Text_141'] = 'Employee + 1: ';
			$mapConfig['Text_142'] = 'Employee + Spouse + Child(ren): ';
			$mapConfig['Text_143'] = '';
		}
	}

	//----------------------------------------------------------------------

	private function generateExhibits($values) {
		$files = array();
		if(!empty($values->docs_list) && $values->docs_list != 'false') {
			$sortedFiles = explode('|', $values->docs_list);
			foreach($sortedFiles as $sortedFile) {
				if(strpos($sortedFile, ':') !== FALSE) {
					$parts = explode(':', $sortedFile);
					$files[$parts[0]] = $parts[1];
				} else {
					$files[$sortedFile] = $sortedFile;
				}
			}
		}
		return $files;
	}

}