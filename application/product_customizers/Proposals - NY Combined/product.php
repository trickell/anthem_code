<?php

/**
 * Proposals - NY Combined
 * Product Customizer
 *
 * @author jmadrigal
 */
class CustomProduct extends ProposalsProductTemplate {

	private $approved_docs = array(
		// NY EBC
		4 => array(
			'NYCMB_11916NYMENZ.pdf' => 'Anthem Care Comparison Flyer',
			'NYCMB_MHP_Flyer_EBC.pdf' => 'Mental Health Parity Flyer',
			'NYCMB_10350NYBENZ.pdf' => 'Network Strength Flyer',
		),
		// NY EBCBS
		5 => array(
			'NYCMB_11916NYMEN.pdf' => 'Anthem Care Comparison Flyer',
			'NYCMB_MHP_Flyer_EBCBS.pdf' => 'Mental Health Parity Flyer',
			'NYCMB_10350NYBEN.pdf' => 'Network Strength Flyer',
		)
		
	);

	public $selected_options_field = 'products';

	private $products_order = array(
		'HMO', 'PPO', 'POS', 'EPO',
		'CDHP', 'Flexible Spending Account',
		'COBRA', 'Dental', 'Vision', 'Life',
		'Short/Long Term Disability', 'Productivity Solutions',
		'Behavioral Health/EAP', 'Pharmacy'
	);

	private $bcbs;
	private $prop_renew;

	//======================================================================
	// Configure the Primary Form
	//======================================================================

	public function configureForm($is_preview = FALSE, $is_send = FALSE) {
		// configure the pages of the wizard
		if(!$is_send) {
			// Disabled per user request
			//$this->setupIAvenue();
			$this->setupBasicInfo();
			$this->setupCoverLetter();
			$this->setupFinancialSummary();
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
			$this->setupSendProposal('Proposal', $body_text, 'Proposal', 'products');
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
		$selected_region = (int)$this->CI->user->getCurrentRegionId();
		if($selected_region != 5) {
			// ensure that the region is 4 or 5.
			$selected_region = 4;
		}
		$page->add('anthem_region_id', 'Branding', 'radios', $selected_region, array('required'), array(
			'values' => array(
				4 => 'Empire BlueCross',
				5 => 'Empire BlueCross BlueShield'
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

		$page->addHeader('Cover Letter');
		$page->add('cover_letter', 'Include a cover letter', 'checkbox');

		// put the selected products on their own page, so they aren't missed
		$page2 = $this->formidable->addWizardPage('Products Included');
		$page2->addNote('Select the products included in this proposal');
		$page2->add('products', '', 'checkboxes', '', array('required'), array(
			'values' => createOptionsColumns(2, $this->products_order),
			'separator' => ''
		));
	}

	//----------------------------------------------------------------------

	private function setupCoverLetter() {
		$wizpage = $this->formidable->addWizardPage('Cover Letter Content', '', 'cover_letter=true');
		$page = $wizpage->addFieldset('');
		$page->addNote('Please enter the following information to customize your cover letter.');
		$page->add('cover_delivery_date', 'Delivery Date', 'date', '', array('required', 'valid_date'), array('field_after' => ' <em>mm/dd/yyyy</em>'));

		$broker = $page->addFieldset('Broker Information');
			$broker->add('cover_broker', '^Is this going to a Broker or to a Group Benefits Administrator?', 'radios', 'Group Benefits Administrator', array('required'), array('values' => createOptions('Group Benefits Administrator', 'Broker')));
			$broker_name = $broker->addGroup(array(), 'cover_broker=Broker');
				$this->_addNameFields($broker_name, 'cover_broker', 'Broker Name');

		$salesrep = $page->addFieldset('Sales Rep. Info');
			$this->_addNameFields($salesrep, 'salesrep', 'Name');
			$salesrep->add('salesrep_title', 'Title', 'text', '');
			//$salesrep->add('salesrep_email', 'Email', 'text', '', array('required', 'valid_email'));
			//$salesrep->add('salesrep_agency', 'Address', 'dropdown', '', array('required'), array('values' => array('test' => 'test')));
		
		$recip = $page->addFieldset('Recipient Information');
			$this->_addNameFields($recip, 'cover_recip', 'Name');
			//$recip->add('cover_recip_title', 'Title', 'text', '', array('required'));
			$recip->add('cover_recip_email', 'Email', 'text', '', array('required', 'valid_email'));
			$recip->addHeader('Address');
			$recip->add('cover_recip_addr1', 'Street 1', 'text', '', array('required'));
			$recip->add('cover_recip_addr2', 'Street 2');
			$recip->add('cover_recip_addr_city', 'City', 'text', '', array('required'));
			$recip->add('cover_recip_addr_state', 'State', 'dropdown', '', array('required'), array('values' => form_state_options()));
			$recip->add('cover_recip_addr_zip', 'Zip Code', 'zip', '', array('required', 'max_length' => 10, 'min_length' => 5), array('field_after' => '<br/><em>Ex: 12345 or 12345-0001</em>'));

		/*
		$ccgroup = $page->addFieldset('');
			$ccgroup->add('cover_enable_cc', FALSE, 'radios', 'no', array('required'), array(
					FORM_HTML_FIELD_BEFORE => '<strong>Would you like to Cc anyone on the cover letter?</strong>&nbsp;&nbsp;',
					'values' => array(
						'yes' => 'Yes',
						'no' => 'No'
					)
				));
			$ccgroup2 = $ccgroup->addGroup('', 'cover_enable_cc=yes');
				$ccgroup2->add('cover_cc', 'Name', 'text', '', array('required'));
		 */

		$additional = $page->addFieldset('Additional Content');
			$additional->addNote(
				'<p>You can use this text field to enter any required language that you need to add to this letter, such as mandated or regulatory change information.</p>'
				.'<p>Please note that you are responsible for ensuring the accuracy and legal approval for any content you add.</p>'
				.'<p>Also, please check the final PDF to ensure that the additional content fits; a 1500 character limit has been added to help.</p>'
			);
			$additional->add('cover_additional', FALSE, 'textarea', '', array('max_length' => 1500));
	}

	//----------------------------------------------------------------------

	private function setupFinancialSummary() {
		$types = array(
			'dental' => array('Health + Dental', 1, 'products=Dental'),
			'life' => array('Health + Life', 1, 'products=Life'),
			'shortdis' => array('Health + STD', '0.5', 'products=Short/Long Term Disability'),
			'longdis' => array('Health + LTD', '0.5', 'products=Short/Long Term Disability'),
			'bvvision' => array('Health + Blue View Vision', '0.5', 'products=Vision'),
		);
		
		$page = $this->formidable->addWizardPage('Empire Savings Calculator', array(), 'products=Dental,Life,Short/Long Term Disability,Vision');
		$page->addNote("Please choose a savings statement and add the appropriate information.");
		
		$statementsGroup = $page->addFieldset(''); // empty fieldset
		$statements = $statementsGroup->addOptionalGroups('statement_type', 'statement_none', '', TRUE);
			$statements->setTypeRadio();
			$st_none = $statements->addGroup('statement_none', 'No Statement');
				$st_none->addNote('No statement will be included with this proposal.');
			$st1 = $statements->addGroup('statement_1', 'Statement 1');
				$st1->add('st1_totalmonthlypremium', 'Total Monthly Health Premium', 'currency', '', array('required'));
				$st1->add('st1_totalannualpremium', 'Total Annual Health Premium', 'currency', '0', '', array('readonly' => 'readonly'));
				$st1->addNote('Approximate Discounts Below<br/><strong>You may uncheck any discounts that are not available to prevent them from showing in the final output.</strong>');
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
				$st2->addNote('Approximate Discounts Below<br/><strong>You may uncheck any discounts that are not available to prevent them from showing in the final output.</strong>');
				foreach($types as $name => $info) {
					// add these fields to track the values - they are rendered in the view.
					$st2->add("st2_h_{$name}_enabled", '', 'norender');
					$st2->add("st2_h_{$name}_annual_savings", '', 'norender');
				}
				$st2->addHTML($this->load_view('statement2_discounts_table', array('types' => $types)));
	}


	//----------------------------------------------------------------------

	private function setupPreapproved() {
		$page = $this->formidable->addWizardPage('Pre-approved Documents', array('id' => 'approvedDocsPage'));

		$page->addNote('Select from the following pre-approved documents to insert them into the final PDF.<br/>(You will be able to choose custom documents and change the insert order next.)');

		foreach($this->approved_docs as $region => $files) {
			$approved_docs = array();
			foreach($files as $file => $name) {
				$approved_docs[$file] = '&nbsp;&nbsp;'.$this->_coverPreview($file)." &nbsp; $name";
			}
			$group = $page->addGroup('', "anthem_region_id=$region", FALSE);
			$group->add("approved_docs_$region", FALSE, 'checkboxes', '', '', array(
				'values' => $approved_docs,
				'wrap' => TRUE
			));
		}
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

	private function _addNameFields($page, $field, $label, $required = TRUE) {
		$rules = $required ? array('required') : NULL;
		$classes = 'name_row';
		if($required) {
			$classes .= ' required';
		}
		$page->addMultifieldRow("{$field}_name_row", $label, array('row_classes' => $classes));
			$page->addMultifieldItem("{$field}_name_row", "{$field}_firstname", 'text', 'First Name', $rules, array('class' => 'blink', 'title' => 'First Name'));
			$page->addMultifieldItem("{$field}_name_row", "{$field}_lastname", 'text', 'Last Name', $rules, array('class' => 'blink', 'title' => 'Last Name'));
	}
	
	
	//======================================================================
	// Product Generation
	//======================================================================

	/**
	 * @see ProposalsProductTemplate
	 */
	protected function start_proposal_generation() {
		$values = $this->formidable->get();
		// hack to add in any custom form values
		foreach($_POST as $k => $v) {
			if(!isset($values->{$k})) {
				$values->{$k} = $v;
			}
		}

		$this->bcbs = $values->anthem_region_id == 5 ? 'BCBS' : 'BC';
		$this->prop_renew = $values->proposal_type;

		$files = array_merge(
			(array)$this->generateCover($values),
			(array)$this->generateCoverLetter($values),
			(array)$this->generateContent($values),
			(array)$this->generateStatements($values),
			(array)$this->generateExhibits($values)
		);
		if(in_array(FALSE, $files)) {
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

	private function generateCoverLetter($values) {
		if(!isset($values->cover_letter) || $values->cover_letter != 'true') {
			return;
		}
		$template = 'NYCMB '.$this->bcbs.' '.ucfirst($this->prop_renew).' Cover Letter.indd';
		$map = array(
			'/Text_1' => 'cover_delivery_date',
			'Text_2' => $values->cover_recip_firstname.' '.$values->cover_recip_lastname,
			'Text_3' => 'group_name',
			'Text_5' => 'group_name',
			'Text_30' => 'cover_additional'
		);
		if($values->cover_broker == 'Broker') {
			$map['Text_6'] = 'We are pleased to respond to '.$values->cover_broker_firstname.' '.$values->cover_broker_lastname.'&#8217;s request for a proposal.';
		}
		$recip_addr = $values->cover_recip_addr1;
		if($values->cover_recip_addr2) {
			$recip_addr .= N . $values->cover_recip_addr2;
		}
		$state = $this->db->select('zone_code')->from(TABLE_ZONES)->where('zone_id', $values->cover_recip_addr_state)->get()->row();
		if(!$state) {
			show_error('Invalid state selected');
		} else {
			$state = $state->zone_code;
		}
		$recip_addr .= N . $values->cover_recip_addr_city.', '.$state.' '.$values->cover_recip_addr_zip;
		$map['Text_4'] = $recip_addr;
		$map['Text_8'] = $values->salesrep_firstname.' '.$values->salesrep_lastname .
						N . $values->salesrep_title;

		// get products, re-grouped into 3 columns
		$prods = array();
		foreach($this->products_order as $prod) {
			if(in_array($prod, $values->products)) {
				$prods[] = $prod;
			}
		}
		// split into thirds
		$colheight = ceil(count($prods)/3);
		$prods = array_chunk($prods, $colheight);
		while(count($prods) < 3) {
			$prods[] = array();
		}
		// Odd numbers because we had to modify this feature several times in
		// the InDesign file.
		$map['Text_7'] = implode(N, $prods[0]);
		$map['Text_10'] = implode(N, $prods[1]);
		$map['Text_11'] = implode(N, $prods[2]);
		return $this->build_workflow($values, '2-coverletter', $template, $map, 'Proposal Cover Letter');
	}

	//----------------------------------------------------------------------

	private function generateContent($values) {
		$template = 'NYCMB '.$this->bcbs.' '.ucfirst($this->prop_renew).' Content.indd';
		$map = array(
			'Text_5' => 'group_name'
		);
		$idx = 9;
		foreach($this->products_order as $prod) {
			if(!in_array($prod, $values->products)) {
				continue;
			}
			$filename = preg_replace('/[^a-z0-9]/i', '', $prod) . '.txt';
			$map['*Text_'.$idx] = file_get_contents(DESIGNMERGE_TEMPLATE.'NY Combined Snippets/'.$this->bcbs.' '.ucfirst($this->prop_renew).'/'.$filename);
			$idx++;
		}
		$map['DM_Output_Preset'] = 'slow_preset';
		return $this->build_workflow($values, '3-content', $template, $map, 'Proposal Content');
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
		if(in_array('Dental', $values->products) && $values->st1_h_dental_enabled) {
			$map['Text_9'] = '=Health + Dental';
			$map['Text_10'] = '=1%';
			$map['$Text_11'] = 'st1_h_dental_annual_savings';
			$map['$Text_12'] = 'st1_h_dental_cost';
			$map['%Text_13'] = 'st1_h_dental_specialty_savings';
			$total_percent += 1;
			$total_money += floatval($values->st1_h_dental_annual_savings);
		}
		if(in_array('Life', $values->products) && $values->st1_h_life_enabled) {
			$map['Text_14'] = '=Health + Life<cPosition:Superscript>*<cPosition:> ($25K or More)<cPosition:Superscript>2<cPosition:>';
			$map['Text_15'] = '=1%';
			$map['$Text_16'] = 'st1_h_life_annual_savings';
			$map['$Text_17'] = 'st1_h_life_cost';
			$map['%Text_18'] = 'st1_h_life_specialty_savings';
			$total_percent += 1;
			$total_money += floatval($values->st1_h_life_annual_savings);
		}
		if(in_array('Short/Long Term Disability', $values->products)) {
			if($values->st1_h_shortdis_enabled) {
				$map['Text_19'] = '=Health + STD<cPosition:Superscript>*<cPosition:>';
				$map['Text_20'] = '=0.5%';
				$map['$Text_21'] = 'st1_h_shortdis_annual_savings';
				$map['$Text_22'] = 'st1_h_shortdis_cost';
				$map['%Text_23'] = 'st1_h_shortdis_specialty_savings';
				$total_percent += 0.5;
				$total_money += floatval($values->st1_h_shortdis_annual_savings);
			}
			if($values->st1_h_longdis_enabled) {
				$map['Text_24'] = '=Health + LTD<cPosition:Superscript>*<cPosition:>';
				$map['Text_25'] = '=0.50%';
				$map['$Text_26'] = 'st1_h_longdis_annual_savings';
				$map['$Text_27'] = 'st1_h_longdis_cost';
				$map['%Text_36'] = 'st1_h_longdis_specialty_savings';
				$total_percent += 0.5;
				$total_money += floatval($values->st1_h_longdis_annual_savings);
			}
		}
		if(in_array('Vision', $values->products) && $values->st1_h_bvvision_enabled) {
			$map['Text_29'] = '=Health + Blue View Vision';
			$map['Text_30'] = '=0.50%';
			$map['$Text_31'] = 'st1_h_bvvision_annual_savings';
			$map['$Text_32'] = 'st1_h_bvvision_cost';
			$map['%Text_33'] = 'st1_h_bvvision_specialty_savings';
			$total_percent += 0.5;
			$total_money += floatval($values->st1_h_bvvision_annual_savings);
		}

		// total savings
		$map['%Text_34'] = '='.$total_percent;
		$map['$Text_35'] = '='.$total_money;

		switch($values->statement_type) {
			case 'statement_1':
				return $this->build_workflow($values, '2-stmt1', 'NYCMB Calculator 1.indd', $map, 'Statement 1');
			case 'statement_2':
				return $this->build_workflow($values, '2-stmt2', 'NYCMB Calculator 2.indd', $map, 'Statement 2');
			default:
				return;
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