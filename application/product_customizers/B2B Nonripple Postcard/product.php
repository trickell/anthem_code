<?php

define('B2B_NONRIPPLE_JOBNAME', 'b2b_nonripple_jobname');
define('B2B_NONRIPPLE_UPLOAD_PATH', 'b2b_nonripple_postcard');
define('B2B_NONRIPPLE_IMAGES_PATH', DESIGNMERGE_TEMPLATE.'Images/B2BNonripplePostcards/');
define('EMAIL_SEPARATOR', '-----------------------------------------------------------------');

// Hack from the PHP site to get str_getcsv() in PHP versions prior to 5.3...
if (!function_exists('str_getcsv')) {
  function str_getcsv($input, $delimiter=',', $enclosure='"', $escape=null, $eol=null) {
    $temp=fopen("php://memory", "rw");
    fwrite($temp, $input);
    fseek($temp, 0);
    $r=fgetcsv($temp, 4096, $delimiter, $enclosure);
    fclose($temp);
    return $r;
  }
}

/**
 * B2B Nonripple Postcard
 * Product Customizer
 *
 * @author FMilens
 * @author Jmadrigal
 */
class CustomProduct extends CustomProductTemplate {
  
  private $templates_array = array (
    'B2B_NR_BLU.indd' => 'Template Thumbnails-blue.jpg',
    'B2B_NR_GLD.indd' => 'Template Thumbnails-gold.jpg',
    'B2B_NR_GRN.indd' => 'Template Thumbnails-green.jpg',
    'B2B_NR_MAG.indd' => 'Template Thumbnails-magenta.jpg',
    'B2B_NR_ORG.indd' => 'Template Thumbnails-peach.jpg',
    'B2B_NR_YLW.indd' => 'Template Thumbnails-yellow.jpg',
  );  
  
	//======================================================================
	// Configure the Primary Form
	//======================================================================

	public function configureForm() {
		$this->template->add_css('css/proposals.css');
		$this->formidable->setFormAttribute('id', 'postcard_form');
		$this->formidable->setFormValidateAjax(TRUE);
		$this->formidable->setWizardAutoSubmit(FALSE);
		$this->formidable->setWizardLastButtonLabel('Place Order');
		$this->setupNewNonripplePostcard();
		$this->setupEnterCopyFront();
    $this->setupEnterCopyBack();			
    $this->setupPostcardColorScheme();
    $this->setupImageSelection();    
    $this->setupPreview();
    $this->setupFileUpload();
    $this->setupApproval();
    $this->setupCampaignSummary();
    
    if(isset($this->original_values->email_proof)) {
      $this->original_values->email_proof = FALSE;
    }
    $this->formidable->setValues($this->original_values);        
	}

	//----------------------------------------------------------------------

  private function getAnthemRegions() {
    $query = $this->db->where('anthem_state', 1)->order_by('display_name', 'asc')->get('anthem_regions');
    $results = array();
    foreach($query->result() as $row) {
      $results[$row->region_id] = $row->display_name; 
    }
    return $results;
  }
  
	private function setupNewNonripplePostcard() {		
		$page = $this->formidable->addWizardPage('New B2B Nonripple Postcard', array('id'=>'start_page'));
		$page->addNote('Please input your campaign specific information below.');		
		$page->add('campaign_name', 'Campaign Name', 'text', '', array('required'));
    $page->addNote('This will be appended to "Non-Ripple Postcard" and your State selection for reference once the product is created.');
		$page->add('anthem_region_id', 'State/Logo', 'dropdown', $this->CI->user->current->state->region->id, array('required'), array('values' => $this->getAnthemRegions()));		
    $page->addNote('This will dictate the Logo, Tagline, and Form #.');
    $page->add('phone_number', 'Response Phone Number', 'text', '', array('required', 'phone' => 'full'));
    $page->addNote('800-555-5555');
    $page->add('event_code', 'Postcard Event ID', 'text', '', array('required'));		
    $page->add('microsite_url', 'Microsite URL', 'text', '', array('required'));		
    $page->addNote('<br/>Please enter the return address information:');		
    $page->add('company_name', 'Company Name', 'text', '', array(''));
    $page->add('return_address_1', 'Address 1', 'text', '', array('required'));
    $page->add('return_address_2', 'Address 2', 'text', '', array());
    $page->add('return_address_city', 'City', 'text', '', array('required'));
    $page->addSimpleDropdown('return_address_state', 'State', form_state_options_by_code(), array('required'));	
    // $page->add('return_address_state', 'State:', 'text', '', array('required'));
    $page->add('return_address_zip', 'ZIP Code', 'text', '', array('required'));    
	}

  private function setupEnterCopyFront() {  
    $page = $this->formidable->addWizardPage('Enter Copy (Front)');    
		// $page->addNote('Select one section of copy that you would like to include on the postcard.  After making a selection, you can preview and edit the copy below.  The copy selected will also drive the postcard headline.');	
		$page->add('front_headline', 'Headline', 'textarea', '', array('required'), array('rows' => '6'));
    $page->add('front_subhead', 'Subhead (optional)', 'textarea', '', array(), array('rows' => '4'));
    $page->add('front_call_to_action', 'Call to Action', 'textarea', '', array('required'), array('rows' => '3'));    
  }
  
  private function setupEnterCopyBack() {
    $page = $this->formidable->addWizardPage('Enter Copy (Back)');
    $page->add('back_headline', 'Headline', 'textarea', '', array('required'), array('rows' => '2'));
    $page->add('back_body_copy', 'Body Copy', 'richtext_reallySimple', '', array('required'), array('rows' => '12'));
    $page->add('back_call_to_action', 'Call to Action', 'textarea', '', array('required'), array('rows' => '4'));
  }  
  
  private function setupFileUpload() {
    $url = site_url('customize/product/'.$this->product->products_id.'/files/sample.csv');
    $page = $this->formidable->addWizardPage('File Upload', array(), '');        
    $page->addNote('To download a sample CSV for formatting, click <span class="redlink"><a href="'.$url.'">here</a></span>.  Please note that the uploaded file MUST be CSV format and the layout MUST be identical to the sample.  This SIC associated with each business will drive the image on the postcard.');
		$page->addDocumentUpload('postcard_csv', '^Choose a CSV file to upload', FALSE, 1, TRUE, array(
      'types' => 'csv',
      'path'  => B2B_NONRIPPLE_UPLOAD_PATH
    ));
    $page->addNote('The CSV preview will appear here once the file has been uploaded:');
    $page->addHTML('<tr><td colspan="3" id="preview_response">
			<div id="csv_preview" style="overflow:auto;"></div>			
			</td></tr>');      
    $page->add('uploaded_filename', '', 'hidden', '0', array());
  }
  
  private function setupPostcardColorScheme() {	      

    $templateslist = array();
		foreach($this->templates_array as $key => $value) {			
      $thumbnail_url = 'file/get/'.getDownloadID(CPOD_SHARED_FILES.'b2bnonripplepostcard/'.$value);      
			$templateslist[$key] = '<img src="'.site_url($thumbnail_url).'"/>';			
		}		
		
		// Create the page for the image
    $page = $this->formidable->addWizardPage('Select Postcard Color Scheme', array(), '');
		$page->addNote('Select the color scheme for the postcard:');
		
		// Create the image selector for the ad				
		$imagegroup = $page->addFieldset('', array(), '', FALSE);
		$imagegroup->add('postcard_template', FALSE, 'radios', 'no', array('required'), array(
				'values' => $templateslist
		));		
  }
  
	private function setupImageSelection() {    
  
    $mainhandle = opendir(B2B_NONRIPPLE_IMAGES_PATH);
    while(($category = readdir($mainhandle)) !== FALSE) {
          
      if($category != '.' && $category != '..' && is_dir(B2B_NONRIPPLE_IMAGES_PATH.$category)) {

        $key = str_replace(" ", "", $category);
        $imagelist = array();
        
        $handle = opendir(B2B_NONRIPPLE_IMAGES_PATH.$category);
        while(($file = readdir($handle)) !== FALSE) {
          if($file != '.' && $file != '..' && strpos($file, "._") === FALSE) {
            $thumbnail_url = 'file/get/'.getDownloadID(CPOD_SHARED_FILES.'b2bnonripplepostcard/'.$category.'/'.str_replace('.eps', '.jpg', $file));
            $imagelist[$file] = '<img src="'.site_url($thumbnail_url).'"/>';
          }
        }
        closedir($handle);
        
        $page = $this->formidable->addWizardPage('Select Images: '.$category, array('class' => 'imageSelection'), '');
        $page->addNote('Select the image for '.$category.':');
        $imagegroup = $page->addFieldset('', array(), '', FALSE);
        $imagegroup->add('image_'.$key, FALSE, 'radios', 'no', array('required'), array('values' => $imagelist));
      }
    }
    
    closedir($mainhandle);
	}		
	
	private function setupPreview() {
		$page = $this->formidable->addWizardPage('Preview',array('id'=>'preview_page'));
		$page->addNote('Click on the PDF icon below to preview a low-resolution version of this piece. 
      If you wish to make changes, click the Back button to return to the previous screens for editing 
      or click the Save & Close button to make changes at a later time. If you are satisfied with this 
      piece, click the Next button to proceed to distribution.');
   
		$page->addHTML('<tr><td colspan="3" id="preview_response">
			<div class="spinner"></div>
			<div class="message"></div>
			<div class="status"></div>
			</td></tr>');

    $page->add('email_proof', 'Check here if you would like to send an email of the proof.', 'checkbox', '', array(), array('no_label'=>true));    
    $group = $page->addGroup(NULL, 'email_proof'); 
		$group->addNote('To email this proof, please enter the requested information below.  An email will be sent to the specified recipient with the proof attached when your order is saved or submitted.');		    
		$group->add('email_from', 'From', 'text', $this->CI->user->default_email, array('required'), array());
    $group->add('email_to', 'To', 'text', '', array('required'), array());
    $group->add('email_subject', 'Subject', 'text', 'Nonripple Postcard Email Distribution', array('required'), array());
    $group->add('email_text', 'Text', 'textarea', '', array('required'), array());    
   
    // This is a hack to handle the save-and-close "feature"...
    $page->add('current_step', '', 'hidden', '0', array('required'));
    $page->add('temp_summary_filename', '', 'hidden', '0', array());
    $page->add('temp_summary_records', '', 'hidden', '0', array());
    $page->add('temp_summary_total_cost', '', 'hidden', '0', array());
    $page->add('temp_summary_unit_price', '', 'hidden', '0', array());
    $page->add('temp_summary_shipping_cost', '', 'hidden', '0', array());    
    $page->add('quantity', '', 'hidden', '0', array('required'));    
    $page->addHTML('<tr><td colspan="3" style="text-align:center;vertical-align:middle;">
      <br/><br/><br/>
      <input type="button" class="button valid" id="save_and_close_button" value="Save and Close" name="save_and_close_button">
      </td>
      </tr>');
	}
  
  private function setupApproval() {
    $page = $this->formidable->addWizardPage('Approval and Acknowledgement', array('id'=>'approval_page'));
    $page->addNote('<span style="color:red;">* You must acknowledge this notice before you proceed.</span>');	
    $page->add('approval_checkbox', 'By checking this box, I confirm that I have received all the necessary Marcom, Business Owner, and Legal approvals to print and mail this direct mail campaign.  If asked, I certify that I can provide documentation of these approvals.', 'checkbox', '', array('required'));       
  }
  
  private function setupCampaignSummary() {
	
		$page = $this->formidable->addWizardPage('Campaign Summary', array('id' => 'summary_page'), '');
		$page->addNote('Please review the summary below<span id="continueText"> and continue</span>.');
    $page->addHTML('<tr><td colspan="3">
      <table>
        <tr><td colspan="2"><b>Campaign Information:</b></td></tr>
        <tr>
          <td>Campaign:</td>
          <td><span id="summary_campaign"></span></td>
        </tr>
        <tr>
          <td>State:</td>
          <td><span id="summary_state"></span></td>
        </tr>
        <tr>        
          <td>Phone Number:</td>
          <td><span id="summary_phone"></span></td>
        </tr>
        <tr>
          <td>Postcard Event Code:</td>
          <td><span id="summary_event_code"></span></td>
        </tr>
        <tr>
          <td>Microsite URL:</td>
          <td><span id="summary_microsite_url"></span></td>
        </tr>
        <tr>
          <td>Return Address:</td>
          <td><span id="summary_return_address_1"></span><br/>
              <span id="summary_return_address_2"></span><br/>
              <span id="summary_return_address_city"></span>, <span id="summary_return_address_state"></span> <span id="summary_return_address_zip"></span><br/>
          </td>
        </tr>
      </table>
      <br/>
      <table>
        <tr><td colspan="2"><b>Letter CSV Information:</b></td></tr>
        <tr>
          <td>File Name:</td>
          <td><span id="summary_filename"></span></td>
        </tr>
        <tr>
          <td>Number of Records:</td>
          <td><span id="summary_records"></span></td>
        </tr>
        <tr>        
          <td>Estimated Total Production Cost:</td>
          <td><span id="summary_total_cost"></span></td>
        </tr>
        <tr>
          <td style="padding-left:30px;">Estimated Unit Price:</td>
          <td style="padding-left:30px;"><span id="summary_unit_price"></span></td>
        </tr>
        <tr>        
          <td style="padding-left:30px;">Estimated Postage:</td>
          <td style="padding-left:30px;"><span id="summary_shipping_cost"></span></td>
        </tr>
      </table>
      </td>
      </tr>
      <br/>');
      
    // Calculate the next business day 10 days ahead of us
    $day = mktime();      
    $count = 0;
    while($count < 10) {
      $day += 86400;
      switch(date("N", $day)) {
        case '6':
        case '7':            
          break;            
        default:
          $count++;            
          break;
      }
    }      
    $page->add('shipping_date', 'Shipping Date:', 'date', '', array('required', 'date_after' => date('m/d/Y', $day)));
    
    if(isset($this->original_values->current_step) && $this->original_values->current_step == '2') {
      $page->add('new_campaign_name', 'New Campaign Name', 'text', 'Copy of '.$this->original_values->campaign_name, array('required'));
      $page->addHTML('<tr><td colspan="3" style="text-align:center;vertical-align:middle;">
        <br/>
        <input type="button" class="button valid" id="clone_button" value="Create Copy of Product" name="clone_button">
        </td>
        </tr>');
    }
	}

  public function clone_postcard() {
  
    $values = $this->original_values;
    $values->campaign_name = $this->input->post('new_campaign_name'); // 'Copy of '.$values->campaign_name;
    $values->current_step = '1';
    
    $this->productcustomizer->init($this->product);
		$addon = '_'.dechex(time()/10);
		$id = $this->productcustomizer->createCustomProduct('', $values, TRUE, $addon);
    
    $new_title = 'B-to-B: Nonripple Postcard '.$values->campaign_name.' '.$values->return_address_state;
		$this->db->where('products_id', $id)->update(TABLE_PRODUCTS_DESCRIPTION, array('products_description' => $new_title));
    
    $result = new JsonObject();
    $result->url = site_url('customize/product/'.$id);
    send_json($result);
  }
  
	/**
	 * Generate the preview
	 */
  // Jmadrigal --- Added new code for help with DesignMerge
	public function preview_generate() {
            
    // Attempt to convert the body copy to InDesign tags...
    $document = new DOMDocument();
    $document->loadHTML($this->input->post('back_body_copy'));
    $document->normalizeDocument();
    $processor = new HtmlToIndesignConverter();
    $body = $processor->convert($document->documentElement);

		// initialize the general product settings, attributes, etc.
		$this->productcustomizer->init($this->product);		
    
		// map the user's selections onto the designmerge template.
		$this->productcustomizer->map($_POST, array(
			'DM_Template'      => $this->input->post('postcard_template'),
			'Image_1' 		     => 'DesignMerge/Templates/Images/B2BNonripplePostcards/Business Casual/'.$this->input->post('image_BusinessCasual'),
      'Image_2' 		     => 'DesignMerge/Templates/Images/B2BNonripplePostcards/Business Professional/'.$this->input->post('image_BusinessProfessional'),
      'Image_3' 		     => 'DesignMerge/Templates/Images/B2BNonripplePostcards/Food Service/'.$this->input->post('image_FoodService'),
      'Image_4' 		     => 'DesignMerge/Templates/Images/B2BNonripplePostcards/Health Care/'.$this->input->post('image_HealthCare'),
      'Image_5' 		     => 'DesignMerge/Templates/Images/B2BNonripplePostcards/Labor Construction/'.$this->input->post('image_LaborConstruction'),
 			'Image_6' 		     => 'DesignMerge/Templates/Images/B2BNonripplePostcards/Large Retail/'.$this->input->post('image_LargeRetail'),
      'Image_7' 		     => 'DesignMerge/Templates/Images/B2BNonripplePostcards/Manufacturing/'.$this->input->post('image_Manufacturing'),
      'Image_8' 		     => 'DesignMerge/Templates/Images/B2BNonripplePostcards/Small Retail/'.$this->input->post('image_SmallRetail'),
      'Image_9' 		     => 'DesignMerge/Templates/Images/B2BNonripplePostcards/Social Personal/'.$this->input->post('image_SocialPersonal'),
      'Image_10' 		     => 'DesignMerge/Templates/Images/B2BNonripplePostcards/Technical/'.$this->input->post('image_Technical'),
			'Text_1'			     => $this->input->post('front_headline'),
      'Text_2'           => $this->input->post('front_subhead'),
      'Text_3'           => $this->input->post('front_call_to_action'),
      'Text_4'           => $this->input->post('back_headline'),
      'Text_5'           => $this->convertToIndesign(
        $this->input->post('back_body_copy'), 
        '<ParaStyle:Non-Ripple body copy><CharStyle:Non-ripplebodycopy>',
        '<ParaStyle:Non-Ripple body bullet indent><CharStyle:Non-ripplebodycopybullets>'),
      'Text_6'           => $this->input->post('back_call_to_action'),
      'Text_7'           => $this->input->post('return_address_1'),
      'Text_8'           => $this->input->post('return_address_2'),
      'Text_9'           => $this->input->post('return_address_city').', '.$this->input->post('return_address_state').' '.$this->input->post('return_address_zip'),
      'Text_10'          => 'JOHN SMITH',
      'Text_11'          => '100 MAIN STREET',
      'Text_12'          => 'SUITE 200',
      'Text_13'          => 'LOUISVILLE, KY 40217',
      // 'Text_14'          => $this->input->post('back_subhead'),
      'Text_15'          => $this->input->post('company_name')
		));
    $this->productcustomizer->setFormNumber();
		$this->designmerge->go();
		
		// capture the design merge job name
		$jobname = $this->designmerge->jobname;
		$this->CI->session->set_userdata(B2B_NONRIPPLE_JOBNAME, $jobname);
    
		// send a basic response (no error can occur at this point)
		send_json(new JsonObject());
	}
	
  private function getPriceInformation($rows) {
    return $this->db->select('g.price as price')
      ->from('product_price p')
      ->join('product_price_grid g', 'p.pricing_id = g.price_id')
      ->where('p.pricing_desc', 'B2B Postcard')
      ->where('g.min <= ', $rows)
      ->where('g.max >= ', $rows)
      ->get()
      ->row();
  }
  
  /**
   * Generates a preview for an uploaded file.  The filename is passed in as 
   * part of an AJAX request.
   *
   */
  public function file_check() {

    $results = new JsonObject();	        
        
    // Generate the preview for the file contents
    $rows = 0;
    $results->filename = $this->getUploadPath($this->input->post('filename'));
    $handle = fopen($this->getUploadPath($this->input->post('filename')), 'r');    
    $results->error = '';
    $results->preview_html = '<table>';
    while(($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    
        // Check the header to see if the contents match the expected format
        if($rows == 0) {
          $expected = str_getcsv('VenderSourceCode,ListSourceCode,ListSourceKey,ProspectID,DUNSNumber,DM_EventCode,FU_DM_EventCode,FU_EM_EventCode,FU_OTM_EventCode,BusinessName,ContactName,ContactTitle,Address1,Address2,City,State,Zip5,Zip4,CarrierRoute,DeliveryPoint,Phone,EmployeeTotal,SIC,YearStarted,RenewalDate,IsEmailAvailable,EmailAddress,PURL');
          if($expected != $data) {
            $results->error = 'The uploaded CSV did not have the proper header format.';
            send_json($results);
            return;
          }
        } else {
          // Check that this SIC actually exists in the database
          $sic = trim($data[22]);
          if(strlen($sic) < 2) {
            $results->error .= 'Invalid SIC code '.$sic.' at line '.($rows).".\n";            
          } else {
            $query = $this->db->like('code', $sic, 'after')->get('standard_industrial_classification');
            if($query->row() == FALSE) {
              $results->error .= 'Unknown SIC code '.$sic.' at line '.($rows).".\n";               
            }          
          }
        }        
        
        // Add this row to the preview
        if($rows < 8) {
          $results->preview_html .= '<tr>';        
          for($i=0;$i<count($data);$i++) {
              $results->preview_html .= '<td>'.$data[$i].'</td>';
          }
          $results->preview_html .= '</tr>';
        }
        
        // Increment totals
        $rows++;
    }
    fclose($handle);    
    $results->preview_html .= '</table>';    
    
    // Decide if we had an error condition or not
    if(!$results->error) {
    
      // Look up the price in the pricing grid 
      $rows--;
      $priceData = $this->getPriceInformation($rows);
      
      // Set the other data
      // TODO:  Put this shared code in one place!!
      $total = $priceData->price * $rows;
      $results->row_count = $rows;
      $results->unit_price = '$'.$priceData->price;
      $results->total_price = '$'.number_format($total, 2);
      $results->shipping_cost = '$'.number_format($rows * 0.25,2);

    } else {
    
      // Clean out the preview since we had an error
      $results->preview_html = '';      
    }
    
    // All done
    send_json($results);
  }
  
	/**
	 * Check on the preview
	 */
	public function preview_check() {
		$response = new JsonObject();
		// get the design merge job name
		$jobname = $this->CI->session->userdata(B2B_NONRIPPLE_JOBNAME);
		if(!$jobname) {
			$response->setError('No Job');
		} else {
			// check on the job's progress
			$status = $this->designmerge->getStatus($jobname, NULL, TRUE);
			$response->complete = ($status->progress == 100);
			$response->message = $status->statusMessage . ' (' . $status->progress . '%)';
			if($response->complete) {
				// create a download link
				$response->pdf = site_url(getDownloadURL($status->filename, FALSE, FALSE));
				$base = str_replace('.pdf', '_HR.pdf', basename($status->filename));
				$response->hires = site_url(getDownloadURL(PDFHIGHRES.$base, FALSE, FALSE));
			}
		}
		send_json($response);
	}
	
	public function submit_postcard() {

		nocache();		
		$ret = new JsonObject();
		
    // Get the name of the output file from the job status object
    $jobname = $this->CI->session->userdata(B2B_NONRIPPLE_JOBNAME);
    $status = $this->designmerge->getStatus($jobname);
  
    // move the completed file to the dynamic folder
    $basename = basename($status->filename);
    rename($status->filename, PDFDYNAMIC . $basename);
    // $highres_link = site_url(getDownloadURL(PDFHIGHRES.str_replace('.pdf', '_HR.pdf', $basename), FALSE, FALSE));    
    
    // save the completed proposal
    $this->productcustomizer->init($this->product);
    $addon = '_'.dechex(time()/10);
    $id = $this->productcustomizer->createCustomProduct($basename, $_POST, TRUE, $addon);
    
    // hack to avoid multiple copies of an item in the db
    // TODO:  This is very bad -- why do we have to do this??
    if($this->generated_product != NULL) {
      $this->db->where('products_id', $this->generated_product->products_id)->limit(1)->delete('products');
    }
    
    // set new title
    $new_title = 'B-to-B: Nonripple Postcard ' . $this->input->post('campaign_name') . ' ' . $this->input->post('return_address_state');
    $this->db->where('products_id', $id)
        ->update(TABLE_PRODUCTS_DESCRIPTION, array('products_description' => $new_title));

    // Special handling for sending an email
    if($this->input->post('email_proof')) {
      
      $this->CI->load->library('email');
      $email =& $this->CI->email;
      
      $jobname = $this->CI->session->userdata(B2B_NONRIPPLE_JOBNAME);
      $status = $this->designmerge->getStatus($jobname);
      
      $email->clear(TRUE);
      $email->set_wordwrap(FALSE);
      $email->from($this->input->post('email_from'));
      $email->to($this->input->post('email_to'));
      $email->subject($this->input->post('email_subject'));
      $email->message($this->input->post('email_text'));     
      $email->attach(PDFDYNAMIC.$basename);
      $email->send();
    }
        
    // Special handling for actually submitting the order
    if($this->input->post('current_step') == '2') {
        
      $current_product = $this->products->getProduct($id);
        
      // Calculate the price
      // TODO:  Put this shared code in one place!!
      $quantity = $this->input->post('quantity');
      $priceData = $this->getPriceInformation($quantity);
      $total = $priceData->price * $quantity;
      $unit_price = '$'.$priceData->price;
      $total_price = '$'.number_format($total, 2);
      $shipping_cost = '$'.number_format($quantity * 0.25,2);    

      // Try to put an order in the database for this item
      // This was all borrowed/modified from the Free Quote code
      $this->db->insert('orders', array(
        'orders_name' => 'B2B - Nonripple Postcard Order',  
        'customers_id' => $this->CI->user->id,
        'customers_name' => $this->CI->user->firstname.' '.$this->CI->user->lastname,
        'customers_company' => 'Wellpoint',
        'customers_street_address' => '700 Locust Lane',
        'customers_street_address2' => '',
        'customers_city' => 'Louisville',
        'customers_postcode' => '40217', 
        'customers_state' => 'KY', 
        'customers_country' => 'USA', 
        'customers_email_address' => $this->CI->user->default_email,
        'customers_address_format_id' =>'2', 
        'delivery_name' => $this->CI->user->firstname.' '.$this->CI->user->lastname, 
        'delivery_company' => 'Wellpoint',
        'delivery_street_address' => '700 Locust Lane', 
        'delivery_street_address2' => '',
        'delivery_city' => 'Louisville',
        'delivery_postcode' => '40217', 
        'delivery_state' => 'KY', 
        'delivery_country' => 'USA',
        'delivery_address_format_id' => '2', 
        'billing_name' => '', 
        'billing_company' => '', 
        'billing_street_address' => '', 
        'billing_street_address2' => '', 
        'billing_suburb' => '', 
        'billing_city' => '',  
        'billing_postcode' => '', 
        'billing_state' => '',  
        'billing_country' => '', 
        'billing_address_format_id' => '', 
        'payment_method' => '', 
        'date_purchased' => 'now()', 
        'shipping_cost' => '',
        'shipping_method' => '',
        'orders_status' => '2', 
        'login_customer_code' => '',
        'future_ship_date' => date('m/d/Y', strtotime($this->input->post('shipping_date'))),
        'arrival_date' => date('m/d/Y', strtotime($this->input->post('shipping_date')))
      ));
      $insert_id = $this->db->insert_id();
      $this->db->insert('orders_products', array(
        'orders_id' => $insert_id,                                    
        'orders_entry' => 1,
        'products_id' => $current_product->products_id, 
        'products_model' => 'B2B Product', 
        'products_name' => $current_product->products_description, 
        'products_price' => $unit_price, 
        'final_price' => $total_price, 
        'products_tax' => '', 
        'products_quantity' => $quantity,
        'state_name' => '', 
        'products_sku' => $current_product->products_sku,   
        'progress_import' => 0,
        'reorderable' => 0
      ));
      
      // Copy the data file to a more permanent location on the filesystem
      $data_file = CPOD_PRIVATE_FILES."orders/B2B/Nonripple/{$insert_id}/".basename($this->input->post('uploaded_filename'));
      @mkdir(CPOD_PRIVATE_FILES."orders/B2B/Nonripple/{$insert_id}/", 0755, true);
      copy($this->input->post('uploaded_filename'), $data_file);        
      
      // Send a confirmation email
      $this->CI->load->library('email');
      $email =& $this->CI->email;
      $email->set_wordwrap(FALSE);
      $email->from('Anthem.Automation@fetter.us');
      $email->to($this->CI->user->default_email);      
      $email->cc('fetter2.us@gmail.com');
      $email->bcc('SmartDoxB2B@fettergroup.com');
      $email->subject('B2B Nonripple Postcard Order Notification');
      $email->message(
        'Anthem CPOD'.N.N.
        EMAIL_SEPARATOR.N.N.
        'Order No.: '.$insert_id.' Order by '.$this->CI->user->firstname.' '.$this->CI->user->lastname.N.N.
        'Order Date: '.strftime('%A, %d %B %G').N.N.
        'CSV Information'.N.N.
        EMAIL_SEPARATOR.N.N.
        'Product SKU: '.$current_product->products_sku.N.N. 
        'Product Description: '.$current_product->products_description.N.N. 
        'Postcard CSV: '.$this->input->post('postcard_csv').N.N.  
        'Number of Records: '.$quantity.N.N. 
        'Postcard Shipping Date: '.$this->input->post('shipping_date').N.N.
        EMAIL_SEPARATOR.N.N);
      $email->attach(PDFDYNAMIC.$basename);
      $email->send(); 
      
      // Send the internal email indicating an order was placed
      $message = '';
      $message .= 'Anthem CPOD'.N.N;     
      $message .= EMAIL_SEPARATOR.N.N;      
      $message .= 'Order No.: '.$insert_id.' Order by '.$this->CI->user->firstname.' '.$this->CI->user->lastname.N.N;
      $message .= 'Order Date: '.strftime('%A, %d %B %G').N.N;    
      $message .= 'Uploaded CSV: '.$data_file.N.N;      
      $message .= 'DesignMerge Data File:'.$this->CI->session->userdata(B2B_NONRIPPLE_JOBNAME);  
      
      $email->clear(TRUE);
      $email->set_wordwrap(FALSE);
      $email->from('Anthem.Automation@fetter.us');
      $email->to('SmartDoxB2B@fettergroup.com');       // 'SmartDoxB2B@fettergroup.com'     
      $email->subject('Nonripple Postcard Order Placed');      
      $email->message($message);
      $email->send();
    }
    
    send_json($ret);
	}  
  
  private function getUploadPath($path) {
		$sess_id = $this->CI->session->userdata('session_id');
		$upload_path = CPOD_TEMP_FILES . $sess_id . '/' . B2B_NONRIPPLE_UPLOAD_PATH;
		return make_path_safe($path, $upload_path);
	}
  
  private function convertToIndesign($text, $normal_styles='', $bullet_styles='') {
    $text = trim($text);
    if($text != '') {
      
      // Hacked fixes for IE 8 HTML
      // TODO:  Fix this because I know it's going to break someday...
      $text = str_replace("\r\n", '', $text);      
      $text = str_replace("\n", '', $text);
      $text = str_replace("<p>", '', $text);
      $text = str_replace("</p>", '<br/>', $text);
      $text = str_replace("<P>", '', $text);
      $text = str_replace("</P>", '<br/>', $text);
      $text = str_replace('‘', '&lsquo;', $text);
      $text = str_replace('’', '&rsquo;', $text);
      $text = str_replace('“', '&ldquo;', $text);
      $text = str_replace('”', '&rdquo;', $text);
      $text = str_replace(chr(160), ' ', $text);
      
      $document = new DOMDocument();
      $document->loadHTML($text);
      $document->normalizeDocument();
      $processor = new HtmlToIndesignConverter($normal_styles, $bullet_styles);      
      return $processor->convert($document->documentElement);
    
    } else {
      return '';
    }
  }
}

/**
 * Initial attempt at a converter between simple HTML and InDesign tags.  This was 
 * developed to support conversion of bold, italic, underline, and bulleted lists
 * from HTML to InDesign tags as part of the B2B postcard project(s).
 *
 * @author FMilens
 */
class HtmlToIndesignConverter {

	/**
	 * An array used as a stack to manage the current state of the converter.  We need
	 * to keep track of the current bold/italic/underline status and other information
	 * as we traverse the HTML DOM in order to know what InDesign tags we need to emit
	 * at a particular point in the conversion process.
	 */
	private $states = array(array('bold'=>false, 'italic'=>false, 'underline'=>false, 'list'=>false));
	
  private $wrote_newline_tag = FALSE;
  
  private $is_empty = FALSE;
  
  private $in_list = FALSE;
  
  private $bullet_styles_tags = '';
  
  private $normal_styles_tags = '';
  
  public function __construct($normal_styles_tags='', $bullet_styles_tags='') {
    $this->bullet_styles_tags = $bullet_styles_tags;
    $this->normal_styles_tags = $normal_styles_tags;
  }
  
	public function convert($root) {
		return $this->normal_styles_tags.$this->visit($root);
	}
	
	/**
	 *
	 *
	 */
	private function visit($node) {
  	$content = '';
    if($node != NULL) {
      switch(strtoupper($node->nodeName)) {
        case "#TEXT":          
          $this->wrote_newline_tag = FALSE;
          $content .= $this->write($node->nodeValue);
          break;
        case "B":
        case "STRONG":
          $this->wrote_newline_tag = FALSE;
          $this->enterState(array('bold'=>true));
          $content .= $this->processChildren($node);
          $this->leaveState();
          break;
        case "I":
        case "EM":
          $this->wrote_newline_tag = FALSE;
          $this->enterState(array('italic'=>true));
          $content .= $this->processChildren($node);
          $this->leaveState();
          break;
        case "U":
          $this->wrote_newline_tag = FALSE;
          $this->enterState(array('underline'=>true));
          $content .= $this->processChildren($node);
          $this->leaveState();
          break;
        case "BR":
          $currentState = $this->getCurrentState();
          if($this->wrote_newline_tag == FALSE && !$currentState['list']) {
            $this->wrote_newline_tag = TRUE;
            $content .= '<0x000D>';
          }
          break;
        case "UL":
          $this->enterState(array('list'=>true));
          $content .= $this->bullet_styles_tags;
          $content .= $this->processChildren($node);
          $content .= $this->normal_styles_tags;
          $this->leaveState();                    
          break;
        case "LI":
          $temporaryText = $this->processChildren($node);
          if($temporaryText != '') {
            $content .= $temporaryText; 
            $content .= '<0x000D>';
          }
          break;
        case "P":
          $this->wrote_newline_tag = FALSE;
          $content .= $this->processChildren($node);
          break;
        default:
          $content .= $this->processChildren($node);
          break;	
      }
    }
    return $content;
	}
	
	private function write($text) {
		
		$currentState = $this->getCurrentState();

    $typeface = 'Light';
    if($currentState['bold'] && $currentState['italic']) {
      $typeface = 'Bold Italic';
    } else if($currentState['bold']) {
      $typeface = 'Bold';
    } else if($currentState['italic']) {
      $typeface = 'Italic';
    }

    $tag = '';
    $tag .= '<cTypeface:'.$typeface.'>';
		$tag .= ($currentState['underline']) ? '<cUnderline:1>' : '<cUnderline:0>';				
    $tag .= $text;    
    $tag .= '<cUnderline:0>';
    $tag .= '<cTypeface:Light>';
    
		return $tag;
	}
	
	private function processChildren($node) {
		$content = '';
		if($node->childNodes) {
			foreach($node->childNodes as $child) {
				$content .= $this->visit($child);	
			}	
		}
		return $content;
	}
	
	/**
	 * Returns the current converter state as an associative array of settings.
	 */
	private function getCurrentState() {
		return $this->states[count($this->states) - 1];	
	}
	
	/**
	 * Creates a new state with the specified settings and pushes it on the states
	 * stack.  The previous state is copied and used as the default selections for
	 * the new state, and then the new state settings are copied over to create a
	 * new state on the top of the states stack.
	 */
	private function enterState($settings) {
		$newState = $this->getCurrentState();
		foreach($settings as $key => $value) {
			$newState[$key] = $value;	
		}
		array_push($this->states, $newState);
	}
	
	/**
	 * Leaves the current state by popping the top of the states stack.
	 */	
	private function leaveState() {
		array_pop($this->states);	
	}   
}