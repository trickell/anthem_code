<?php

define('B2B_BIRTHDAY_JOBNAME', 'b2b_birthday_jobname');
define('B2B_BIRTHDAY_JOBNAMES', 'b2b_birthday_jobnames');
define('B2B_BIRTHDAY_UPLOAD_PATH', 'b2b_birthday_postcard');
define('B2B_BIRTHDAY_IMAGES_PATH', DESIGNMERGE_TEMPLATE.'Images/B2BBirthdayPostcards/');
define('B2B_BIRTHDAY_PUBLIC_IMAGES_PATH', CPOD_SHARED_FILES.'b2bbirthdaypostcards/');
define('EMAIL_SEPARATOR', '-----------------------------------------------------------------');

define('LETTERSHOP',.18);
define('INSERTION',.33);
define('STAMPCOST',.44);
define('CARDPRICEID',29);
define('ENVPRICEID',30);

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

//
// Function for sorting the result data by month and year.  This really should
// be a static function in the class rather than being outside like this, but
// I'm just doing this to see if this will even work for now...
//
function Birthday_SortByMonthAndYearCallback($a, $b) {
  $a_year = (int)$a['sort_year'];
  $b_year = (int)$b['sort_year'];
  if($a_year > $b_year) {
    return 1;
  } else if($a_year < $b_year) {
    return -1;
  } else {
    $a_month = (int)$a['sort_month'];
    $b_month = (int)$b['sort_month'];
    if($a_month > $b_month) {
      return 1;
    } else if($a_month < $b_month) {
      return -1;
    } else {
      return 0;
    }       
  }
}

/**
 * B2B Birthday Postcard
 * Product Customizer
 *
 * @author FMilens
 * @author CoAuthor Jmadrigal 
 * Added seperate functions and modified functions for DesignMerge update
 */
class CustomProduct extends CustomProductTemplate {

  private $copy_and_image_array = array (
    'babies' => array(
      'name' => 'Babies',
	  'sex' => 'MF',
	  'age_min' => 0,
	  'age_max' => 2,
		'template' => '13701xxMENxxx.indd',
	  'images' => array('Lifestyle Home MF DV I Babies lying down 06 10 RF.jpg','Lifestyle Home MF DV I Sitting on couch 08 10 RF.jpg'),
      'custom_selections' => array (
        'hearing'     => 'Hearing',
        'weight'      => 'Weight, length, and head circumference',
        'hemoglobin'  => 'Hemoglobin or hematocrit; once between 9 and 12 months',
        'lead'        => 'Lead testing at ages 1 and 2 years',
        'age'         => 'Age-appropriate developmental/behavioral assessments'
      )
    ),
    'kids3_10' => array(
      'name' => 'Kids 3-10',
	  'sex' => 'MF',
	  'age_min' => 3,
	  'age_max' => 10,
		'template' => '13718xxMENxxx.indd',
		'images' => array('Lifestyle Active MF DV C Playing on Jungle Gym 06 10 RF.jpg','Lifestyle Home F DV C birthday celebration with hands in the air 08 10 RF.jpg'),
      'custom_selections' => array (
        'bloodpressure' => 'Blood pressure',
        'vision'        => 'Vision',
        'hearing'       => 'Hearing',
        'height'        => 'Height, weight, and body mass index',
        'age'           => 'Age-appropriate developmental/behavioral assessments'
      )
    ),
    'kids11_18' => array(
      'name' => 'Kids 11-18',
	  'sex' => 'MF',
	  'age_min' => 11,
	  'age_max' => 18,
		'template' => '13712xxMENxxx.indd',
		'images' => array('Lifestyle Leisure MF DV T Teens smiling for a camera phone.jpg','Lifestyle Leisure MF DV T  Walking together, laughing 08 10 RF.jpg'),
      'custom_selections' => array (
		'bloodpressure' => 'Blood pressure',
		'vision' => 'Vision',
		'hearing' => 'Hearing',
		'height' => 'Height, weight, and body mass index'
      )
    ),
    'men19_39' => array(
      'name' => 'Men 19-39',
	  'sex' => 'M',
	  'age_min' => 19,
	  'age_max' => 39,
		'template' => '13713xxMENxxx.indd',
		'images' => array('Lifestyle Active M DV A Guys lauging by a fence 08 10 RF.jpg','Lifestyle Leisure M DV A Friends having a good laugh 06 10 RF.jpg'),
      'custom_selections' => array (
        'bloodpressure' => 'Blood pressure',
        'height'        => 'Height, weight, and body mass index',
        'cholesterol'   => 'Cholesterol'
      )
    ),
    'women19_39' => array(
      'name' => 'Women 19-39',
	  'sex' => 'F',
	  'age_min' => 19,
	  'age_max' => 39,
		'template' => '13714xxMENxxx.indd',
		'images' => array('Lifestyle Active F DV A Women smiling arm in arm 08 10 RF.jpg','Lifestyle Leisure F DV A Women friends smiling together 06 10 RF.jpg'),
      'custom_selections' => array (
        'bloodpressure'   => 'Blood pressure',
        'height'          => 'Height, weight, and body mass index',
        'cholesterol'     => 'Cholesterol',
        'breastcancer'    => 'Breast cancer',
        'cervicalcancer'  => 'Cervical cancer',
        'chlamydia'       => 'Chlamydia'
      )
    ),
    'men_40plus' => array(
      'name' => 'Men 40+',
	  'sex' => 'M',
	  'age_min' => 40,
	  'age_max' => 150,
		'template' => '13717xxMENxxx.indd',
		'images' => array('Lifestyle Leisure M DV A Men laughing together 06 10 RF.jpg','Lifestyle Leisure M WT S Men having a good laugh.jpg'),
      'custom_selections' => array (
        'bloodpressure'     => 'Blood pressure',
        'height'            => 'Height, weight, and body mass index',
        'cholesterol'       => 'Cholesterol',
        'colorectalcancer'  => 'Colorectal cancer',
        'prostatecancer'    => 'Prostate cancer'
      )
    ),
    'women_40plus' => array(
      'name' => 'Women 40+',
	  'sex' => 'F',
	  'age_min' => 40,
	  'age_max' => 150,
		'template' => '13715xxMENxxx.indd',
		'images' => array('Lifestyle Leisure F DV A Ladies laughing together 08 10 RF.jpg','Lifestyle Leisure F DV A Women celebrating 06 10 RF.jpg'),
      'custom_selections' => array (
        'bloodpressure'     => 'Blood pressure',
        'height'            => 'Height, weight, and body mass index',
        'prenatal'          => 'Prenatal care, if pregnant',
        'cholesterol'       => 'Cholesterol',
        'breastcancer'      => 'Breast cancer',
        'colorectalcancer'  => 'Colorectal cancer',
        'cervicalcancer'    => 'Cervical cancer',
        'osteoporosis'      => 'Osteoporosis'
      )
    )
  );

  private $months_array = array(
    '1' => 'January',
    '2' => 'February',
    '3' => 'March',
    '4' => 'April',
    '5' => 'May',
    '6' => 'June',
    '7' => 'July',
    '8' => 'August',
    '9' => 'September',
    '10' => 'October',
    '11' => 'November',
    '12' => 'December'
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
		$this->setupNewBirthdayPostcard();
		$this->setupCopyAndImageSelections();
    $this->setupPreview();
		$this->setupMonths();
		$this->setupFileUpload();
		$this->setupSummary();
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

	private function setupNewBirthdayPostcard() {

		$page = $this->formidable->addWizardPage('Birthday Card Wellness Campaign', array('id'=>'start_page'));
		$page->addNote('Please input your campaign specific information below.');

		$page->add('campaign_name', 'Campaign Name', 'text', '', array('required'));

		// display for non national account
		if (!$this->CI->user->national_account) {
			$page->add('anthem_region_id', 'State/Logo', 'dropdown', $this->CI->user->current->state->region->id, array('required'), array('values' => $this->getAnthemRegions(),'class'=>'notavailable'));
		}
		
		$page->add('campaign_type', 'Campaign Type', 'dropdown', '', array('required'), array('values' => array('standard' => 'Standard', 'custom' => 'Custom')));
		$logo = returnDuelBrandLogo();
		if (!$logo || ($logo == '')) {

			// disable selection
			$page->add('use_cobranding', '^Would you like to co-brand this campaign?', 'radios', '', array('required'), array('values' => array('yes' => 'Yes', 'no' => 'No'),'class'=>'notavailable'));
			$page->addHTML('<tr><td>&nbsp;</td><td colspan="2"><img src="'.site_url('../images/no-logo.jpg').'" width="100" height="100" /></td></tr>');
		} else {

			// Show logo
			$page->add('use_cobranding', '^Would you like to co-brand this campaign?', 'radios', 'yes', array('required'), array('values' => array('yes' => 'Yes', 'no' => 'No')));
			$group = $page->addGroup(array(), 'use_cobranding=yes');
				$group->addHTML('<tr><td>&nbsp;</td><td colspan="2"><img src="'.site_url('../'.$logo.'.jpg').'" /></td></tr>');
		}

		$page->addHeader('Envelope Selection');
		$page->add('custom_envelope', '^Would you like to include a custom envelope in this campaign?', 'radios', 'no', array('required'), array('values' => array('yes' => 'Yes', 'no' => 'No')));		
    $group = $page->addGroup(array(), 'custom_envelope=yes');
			$group->addHTML('<tr><td colspan="3" style="color: red;">Because you have chosen to include a custom envelope in this campaign, you will need to submit a MRM request for the special envelope.</td></tr>');    
      
		$group = $page->addGroup(array(), 'campaign_type=custom');
    $group->addHeader('Chart Selection');
		$group->add('include_chart', '^Would you like to include a chart in the cards?', 'radios', 'no', array('required'), array('values' => array('yes' => 'Yes', 'no' => 'No')));
            
		$page->addHeader('Return Address:');
		$page->add('address_1', 'Address 1', 'text', '', array('required'));
		$page->add('address_2', 'Address 2', 'text', '', array(''));
		$page->add('city', 'City', 'text', '', array('required'));
		$page->addSimpleDropdown('state', 'State',form_state_options_by_code(), array('required'));
		$page->add('zip', 'Zip', 'text', '', array('required'));
	}

	private function setupCopyAndImageSelections() {
		foreach($this->copy_and_image_array as $age_key => $age_data) {
			$page = $this->formidable->addWizardPage('Copy and Image Selection - '.$age_data['name'], array(), 'campaign_type=custom');
			$page->add($age_key.'_selections', '^<b>Deselect any screenings you would not like to include for this age band.</b>', 'checkboxes', array_keys($age_data['custom_selections']), array(''), array('values' => $age_data['custom_selections'], 'wrap' => true));

			  $imagelist = array();
			  foreach($this->copy_and_image_array[$age_key]['images'] as $key => $file) {
					$thumbnail_url = 'file/get/'.getDownloadID(B2B_BIRTHDAY_PUBLIC_IMAGES_PATH.$age_key.'/'.$file);
					$imagelist[$file] = '<img src="'.site_url($thumbnail_url).'"/>';
			  }

			$group_image = $page->addGroup(array(), 'campaign_type=custom');
				$group_image->addHTML('<tr><td colspan=3><div class="imageSelection">Select a photo for the cards in this age band.</div></td></tr>');
				reset($imagelist);
				$group_image->add('image_'.$age_key, FALSE, 'radios', key($imagelist), array('required'), array('values' => $imagelist, 'row_classes'=>'row_ad_image'));
				$group_image->addHTML('<tr><td colspan=3 height=50>&nbsp</td></tr>');
		}
	}

	private function setupMonths() {

		$page = $this->formidable->addWizardPage('Campaign Months', array(), '');

		$months = array();
		$today = date('d');
		$m = date('m');
		$y = date('Y');

    // Have to skip the current month first...
    $m = date('m',mktime(0,0,0,((int)$m)+1,1,$y));
    if ($m == 1) { $y++; $m++; }
    
		// Push into next month
		if ($today > 15) {
			$m = date('m',mktime(0,0,0,((int)$m)+1,1,$y));

			// if this pushing into next year...
			if ($m == 1) { $y++; $m++; }
		}

    $m = (int)$m;    
		for($x=1;$x<=12;$x++){
			$months[$m.'-'.$y] = $y.' - '.date('F',mktime(0,0,0,$m,1,$y));
			$m++;
			if ($m == 13) {
				$y++;
				$m = 1;
			}
		}

		$page->addNote("Caution: When the address file is uploaded, individuals with birthdays in the months selected are the only ones mailed a card.");
		$page->addNote('Select the months to include in the campaign.  We recommend a selection of 3 months or less.');
			$monthsgroup = $page->addFieldset('', array('id'=>'monthslist'), '', FALSE);
			$monthsgroup->add('months', FALSE, 'checkboxes', 'no', array('required'), array('values' => $months, 'wrap' => true));
		
		$page->addNote("Campaigns must be submitted by the 15th of the month prior to the month requested for mailing.  Example: December Birthdays need to be submitted on or before November 15th.");		
   }

  	private function setupPreview() {

		$page = $this->formidable->addWizardPage('Preview',array('id'=>'preview_page'));
		$page->addNote('Click on the PDF icon below to preview a low-resolution version of this piece. If you wish to '.
			'make any changes to the piece, click the Back button to return to the previous screens for editing. '.
			'If you are satisfied with this piece, click the Next button to proceed to distribution.');
		$page->addHTML('<tr><td colspan="3" id="preview_response">
			<div class="spinner"></div>
			<div class="message"></div>
			<div class="status"></div>
			</td></tr>');

	// This is a hack to handle the save-and-close "feature"... verbatum from Fredericks non ripple
    $page->add('current_step', '', 'hidden', '0', array('required'));
    $page->addHTML('<tr><td colspan="3" style="text-align:center;vertical-align:middle;">
      <br/><br/><br/>
      <input type="button" class="button valid" id="save_and_close_button" value="Save and Close" name="save_and_close_button">
      </td>
      </tr>');
	}

	private function setupFileUpload() {
		$page = $this->formidable->addWizardPage('File Upload', array('id'=>'upload_page'));

		$page->addNote('<div class="downloaddiv">To download a sample CSV template, click <span class="redlink"><a href="'.site_url('customize/product/'.$this->product->products_id.'/files/sample.csv').'">here</a></span>.  Please note that the uploaded file MUST be CSV format and the layout MUST be identical to the sample to ensure the accurate processing of the mailing data.</div>');
		$page->addDocumentUpload('postcard_csv', '^Choose a CSV file to upload', FALSE, 1, TRUE, array(
		  'types' => 'csv',
		  'path'  => B2B_BIRTHDAY_UPLOAD_PATH
		));

	    
    $page->add('uploaded_filename', '', 'hidden', '0', array());
    $page->add('approval_checkbox', 'By Checking this box, I certify that I have verified the accuracy of the names, address and birthdays of the data contained within the uploaded file.', 'checkbox', '', array('required'),array('no_label'=>true));
		$page->addNote('<span style="color:red;">* You must acknowledge this notice before you proceed.</span>');

		$page->addNote('The CSV preview will appear here once the file has been uploaded:');
		$page->addHTML('<tr><td colspan="3">
			<div id="csv_preview" style="overflow:auto;"></div>
			</td></tr>');
  }

  private function setupSummary() {
		
    $page = $this->formidable->addWizardPage('Summary',array('id'=>'summary_page'));
    
    // Hack to keep the summary HTML around for completed orders
    // We need to do this because we're not really storing it anywhere else...
    $page->add('summary_html', '', 'hidden', '0', array());
    
		$page->addHTML('<tr><td colspan="3"><div id="summary_response" style="overflow:auto;"><img src="../images/loading.gif"></div></td></tr>');

    /*
    if(isset($this->original_values->current_step) && $this->original_values->current_step != '2') {
      $group->add('confirmation_email', 'Order confirmation recipient:', 'text', $this->CI->user->default_email, array('required'), array());
    }
    */
    
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
    
    $new_title = 'Birthday Card Wellness Campaign '.$values->campaign_name.' '.$values->state;
		$this->db->where('products_id', $id)->update(TABLE_PRODUCTS_DESCRIPTION, array('products_description' => $new_title));
    
    $result = new JsonObject();
    $result->url = site_url('customize/product/'.$id);
    send_json($result);
  }
  
    // Jmadrigal --- added for ability to intergrate properly with the designmerge update
	public function preview_generate() {

		/*
		 * Going to store each job name into this array then return this to the browser... should have 7 files when done.
		 * The browser will monitor each when done will combine them into one.
		 * 
		 */
		$jobname = array();


		// Loop over age band categories
		foreach($this->copy_and_image_array as $grouping=>$values) {

			 /*
			  * gather selections
			  */
			$selection_array = array();
			if ($this->input->post('campaign_type') == 'standard') {
				$selection_image = $this->copy_and_image_array[$grouping]['images'][0];
				$selection_array = $this->copy_and_image_array[$grouping]['custom_selections'];
			} else {
				$selection_image = $this->input->post('image_'.$grouping);
				foreach($this->input->post($grouping.'_selections') as $key=>$value) {
					$selection_array[] = $this->copy_and_image_array[$grouping]['custom_selections'][$value];
				}
			}

			/*
			 * Gather Images, for the chart page we add  one to the selection_chart_rows (for the title row of the indesign page)
			 */
			$selection_chart_rows = 0;
			if ($this->input->post('include_chart') == 'yes') {
				$selection_chart_rows = count($selection_array) + 1;
				$selection_image_page = str_replace('.jpg','_grid.pdf',$selection_image);
			} else {
				$selection_image_page = str_replace('.jpg','_default.pdf',$selection_image);
			}

			// initialize the general product settings, attributes, etc.
			$this->productcustomizer->init($this->product);
      if($this->input->post('use_cobranding') == 'no') {
        $this->designmerge->Logo_3_Dual = '';
      }

      // Standard postcards shouldn't have any bullets or other text
      if($this->input->post('campaign_type') != 'standard') {
        $text_2 = implode("\n", $selection_array);      
        if($grouping == 'kids11_18') {
          $text_2 = str_replace("\n", ", ", $text_2);
          if(!strpos($text_2, 'and')) {
            $pos = strrpos($text_2, ",");
            if($pos != FALSE) {
              $text_2 = substr($text_2, 0, $pos).' and'.substr($text_2, $pos+1);
            }
          }          
          $text_2 = str_replace(", and", " and", $text_2);
          $text_2 = strtolower($text_2);
          $text_2 = 'During this visit, your doctor will check your '.$text_2.'. ';
        }
      } else {
        $text_2 = '';
      }
      
      // map the user's selections onto the designmerge template.      
			$this->productcustomizer->map($_POST, array(
				'DM_Template'     =>  $this->copy_and_image_array[$grouping]['template'],
				'DM_Priority'     => '1',
				'Image_1' 		    => str_replace('/script_files','', B2B_BIRTHDAY_IMAGES_PATH).$grouping.'/'.$selection_image,
				'Image_2'		      => str_replace('/script_files','', B2B_BIRTHDAY_IMAGES_PATH).$grouping.'/'.$selection_image_page,
				'Text_1'		      => 'Sample Name',
				'Text_2'          => $text_2,
				'Text_5'		      => (string) $selection_chart_rows,
				'Text_6'		      => 'Annual checkup',
				'Text_7'		      => (isset($selection_array[0])?$selection_array[0]:''),
				'Text_8'		      => (isset($selection_array[1])?$selection_array[1]:''),
				'Text_9'		      => (isset($selection_array[2])?$selection_array[2]:''),
				'Text_10'		      => (isset($selection_array[3])?$selection_array[3]:''),
				'Text_11'		      => (isset($selection_array[4])?$selection_array[4]:''),
        'Text_12'		      => (isset($selection_array[5])?$selection_array[5]:''),	
        'Text_13'		      => (isset($selection_array[6])?$selection_array[6]:''),
        'Text_14'		      => (isset($selection_array[7])?$selection_array[7]:'')	
			));
  		$this->productcustomizer->setFormNumber();
      $this->designmerge->formnumber = str_replace("nnnnn", substr($this->copy_and_image_array[$grouping]['template'],0,5), $this->designmerge->formnumber).'-'.($this->input->post('campaign_type')=='standard'?'G':'C').' 6/10';

			// Form number config
			// 13701 babies
			// 13718 kids
			// 13712 kids 11-18
			// 13713 men 19
			// 13714 women 19
			// 13717 men 40
			// 13715 women 40
			// 13701xxMENxxx-x
			// xxx = logo
			// x = G or C for custom
			$this->designmerge->noHighRes();
			$this->designmerge->go();

			// capture the design merge job name
			$jobname[] = $this->designmerge->jobname;

		} // Looping into next category

    // Generate the envelope at the end
    if($this->input->post('custom_envelope') == 'no') {
      $this->productcustomizer->init($this->product);
      if($this->input->post('use_cobranding') == 'no') {
        $this->designmerge->Logo_3_Dual = '';
      }
      $this->productcustomizer->map($_POST, array(
        'DM_Template'     =>  '15384MUMENMUB_Envelope.indd',
        'DM_Priority'     =>  '1',
        'Text_15'         => $this->input->post('address_1'),
        'Text_16'         => $this->input->post('address_2'),
        'Text_17'         => $this->input->post('city').', '.$this->input->post('state').' '.$this->input->post('zip'),
      ));
      $this->productcustomizer->setFormNumber();
      $this->designmerge->formnumber .= '-'.($this->input->post('campaign_type')=='standard'?'G':'C');
      $this->designmerge->noHighRes();
      $this->designmerge->go();
      $jobname[] = $this->designmerge->jobname;
    }
    
    // Store the job names
		$this->CI->session->set_userdata(B2B_BIRTHDAY_JOBNAME, $jobname);

		// send a basic response (no error can occur at this point)
		send_json(new JsonObject());
	}

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
          $expected = str_getcsv('Last Name,First Name,Address1,Address2,City,State,Zip,Gender (M / F),Month (MM),Day (DD),Year (YYYY)');
          if($expected != $data) {
            $results->preview_html = '';
            $results->error .= "The uploaded CSV did not have the proper header format.\n";
            send_json($results);
            return;
          }
        } else {        
          // Per-line validation of data goes here...
          if((int)$data[10] > (int)date("Y")) {
            $results->preview_html = '';
            $results->error .= 'Year column in row '.($rows + 1).' must be less than or equal to '.date('Y').".\n";            
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
    send_json($results);
	}

	private function calculateAge($birthday){
		return floor((time() - strtotime($birthday))/31556926);
	}

  private function calculateMailInMonths() {
    $mail_in_months = array();
    if (isset($_POST['months'])) {
			foreach($_POST['months'] as $value) {
				$parts = explode('-',$value);
				$mail_in_months[$parts[0]] = $parts[1];
			}
		}
    return $mail_in_months;
  }
  
  private function calculateSummary($filename, $mail_in_months) {
  		
    $results = new JsonObject();
		
		$first = true;
		$result = false;

		// Setup month data
		$result = array(
			'TOTALROWS' => 0,
			'UNUSEDROWS' => 0,
			'GOODROWS' => 0,
			'ROWS' => array(),
			'BADROWS' => array(),
			'months'=>array(),			
			'CAMPAIGN'=>array('total'=>0,'shipping'=>0));
		$category_keys = array_keys($this->copy_and_image_array);

		$handle = fopen($filename, 'r');		
		if ($handle) {
		 while(($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			$row = array();

			// Skip first line
			if ($first) {$first = false;continue;}

			// Define fields
			$row['lastname']	= $data[0];
			$row['firstname']	= $data[1];
			$row['address1']	= $data[2];
			$row['address2']	= $data[3];
			$row['city']		= $data[4];
			$row['state']		= $data[5];
			$row['zip']			= $data[6];
			$row['gender']		= strtoupper($data[7]);
			$row['month']		= $data[8];
			$row['day']			= $data[9];
			$row['year']		= $data[10];
			$row['category']	= false;

			// For ease do some formatting and re defining of variables
			$row['birthday']	= $row['month'].'/'.$row['day'].'/'.$row['year'];
			$row['age']			= $this->calculateAge($row['birthday']);

			// Define the category this record falls under
			foreach($this->copy_and_image_array as $category => $info) {
				if (stristr($info['sex'],$row['gender'])) {
					if (($row['age']>=$info['age_min']) && ($row['age']<=$info['age_max'])) {
						$row['category'] = $category;
						break;
					}
				}
			}

			// Good Recrods
			if ($row['category']) {
				$result['ROWS'][] = $row;

				if (array_key_exists($row['month'],$mail_in_months)) {

					// Add for statistics to month categories
					if (!isset($result['months'][$row['month']])) {
						$result['months'][$row['month']] = array();
						$result['months'][$row['month']]['categories'] = array();
						$result['months'][$row['month']]['records'] = 0;
            
            // Keep year and month around for sorting purposes
            $result['months'][$row['month']]['sort_month'] = $row['month'];
            $result['months'][$row['month']]['sort_year'] = $mail_in_months[$row['month']];
					}

					if (!isset($result['months'][$row['month']]['categories'][$row['category']])) {
						$result['months'][$row['month']]['categories'][$row['category']]['records'] = 1;
					} else {
						$result['months'][$row['month']]['categories'][$row['category']]['records']++;
					}

					$result['months'][$row['month']]['records']++;
					$result['GOODROWS']++;
				} else {					
					$result['UNUSEDROWS']++;
				}

			// Bad Records
			} else {
				$result['BADROWS'][] = $row;
			}
			$result['TOTALROWS']++;
		}
		fclose($handle);
		
		 // Price Calculations
		if (isset($result['months'])) {
		 foreach($result['months'] as $line=>$values) {
			 foreach($result['months'][$line] as $month => $month_values) {

				// skip over non months like records and categories
				// the && was added to the following line to prevent the total from getting jacked up due to the records variable.. bad planning on my part
				if (array_key_exists($line, $mail_in_months) && $month == 'categories') {

					// Individual category cost
					foreach($result['months'][$line]['categories'] as $cat=>$cat_values) {
						$records = $result['months'][$line]['categories'][$cat]['records'];
						$piece_cost = get_total_price(CARDPRICEID,$records);
						$env_cost = get_total_price(ENVPRICEID,$records);


						#$result['months'][$line]['categories'][$cat]['unit_letter_cost']	= ($piece_cost / $records);
						#$result['months'][$line]['categories'][$cat]['unit_env_cost']		= ($env_cost / $records);
						#$result['months'][$line]['categories'][$cat]['letter_total_cost']	= $piece_cost;
						#$result['months'][$line]['categories'][$cat]['env_total_cost']		= $env_cost;
						#$result['months'][$line]['categories'][$cat]['handling_cost']		= (LETTERSHOP + INSERTION) * $records;
						#$result['months'][$line]['categories'][$cat]['total']				= ((LETTERSHOP + INSERTION) * $records) + $piece_cost + $env_cost;
						#$result['months'][$line]['categories'][$cat]['shipping']			= STAMPCOST * $records;
					}

					// Run cost
					$records = $result['months'][$line]['records'];
					$piece_cost = get_total_price(CARDPRICEID,$records);
					$env_cost = get_total_price(ENVPRICEID,$records);


					$result['months'][$line]['unit_letter_cost']	= ($piece_cost / $records);
					$result['months'][$line]['unit_env_cost']		= ($env_cost / $records);
					$result['months'][$line]['letter_total_cost']	= $piece_cost;
					$result['months'][$line]['env_total_cost']		= $env_cost;
					#$result['months'][$line]['handling_cost']		= (LETTERSHOP + INSERTION) * $records;
					$result['months'][$line]['total']				= $piece_cost + $env_cost;
					$result['months'][$line]['shipping']			= (STAMPCOST + LETTERSHOP + INSERTION) * $records;

					// Campaign Totals
					$result['CAMPAIGN']['total'] += $result['months'][$line]['total'];
					$result['CAMPAIGN']['shipping'] += $result['months'][$line]['shipping'];
					
				}
			 }
		 }
    }
      uasort($result['months'], "Birthday_SortByMonthAndYearCallback");
      return $result;
    } else {
      return FALSE;
    }
  }  
  
	public function summary() {
  
    $mail_in_months = $this->calculateMailInMonths();
    $result = $this->calculateSummary($_POST['filename'], $mail_in_months);
    
    if($result !== FALSE) {		
    if (isset($result['months'])) {     
		 $results->html = '';
		 $results->html .= '<div class="summaryHead">Campaign Information</div>';
		 $results->html .= '<div class="summaryBody"><ul>
<li>Campaign: '.$_POST['campaign_name'].'</li>
<li>State: '.$_POST['state'].'</li>
<li>Co-Brand: '.ucwords($this->input->post('use_cobranding')).'</li>'.
'<li>Company: '.$this->getCurrentCompanyName().'</li>'.
'<li>Custom Envelope: '.ucwords($this->input->post('custom_envelope')).'</li>'.
'<li>Return Address:<br/><div class="addressblock">
'.$_POST['address_1'].'<br/>'
.($_POST['address_2']!=''?$_POST['address_2'].'<br/>':'')
.$_POST['city'].', '.$_POST['state'].' '.$_POST['zip']
.'</div></li></ul></div>';
		 $results->html .= '<div class="summaryHead">Campaign Totals</div>';
		 $results->html .= '<div class="summaryBody">
<ul>
<li>Number of Mailing Records: '.$result['GOODROWS'].'</li>
<li>Number of Unused Records: '.$result['UNUSEDROWS'].'</li>
<li>Number of Bad Records: '.count($result['BADROWS']).'</li>
<li>Number of Records Uploaded: '.$result['TOTALROWS'].'</li>
<li>Campaign Total Print Cost: $'.number_format($result['CAMPAIGN']['total'],2).'</li>
<li>Campaign Total Fulfillment &amp; Postage: $'.number_format($result['CAMPAIGN']['shipping'],2).'</li>
</ul>
</div>';

		 foreach($result['months'] as $line=>$values) {
			 if (array_key_exists($line,$mail_in_months)) {			 
				$results->html .= '<div class="summaryHead">'.$this->months_array[$line].' Mailing Information</div>';
				$results->html .= '<div class="summaryBody"><ul>
<li>Number of Records: '.$result['months'][$line]['records'].'</li>
<li>Estimated Cost</li>
<ul class="second">
 <li>Total Print: $'.number_format($result['months'][$line]['total'],2).'
  <ul class="third">
    <li>Unit: $'.number_format($result['months'][$line]['unit_letter_cost'],2).'/Letter</li>
    <li>Unit: $'.number_format($result['months'][$line]['unit_env_cost'],2).'/Envelope</li>
  </ul> 
 </li>
 <li>Fulfillment & Postage: $'.number_format($result['months'][$line]['shipping'],2).'</li>
</ul>
<li>Shipping Date: '.$line.'/1/'.$mail_in_months[$line].'</li>
<li>Quantities:
<ul class="second">';
				foreach($result['months'][$line]['categories'] as $cat=>$cat_values) {
					$results->html .= '<li>'.$this->copy_and_image_array[$cat]['name'].': '.$result['months'][$line]['categories'][$cat]['records'].'</li>';
				}

				$results->html .= '</ul></li></div>';
			}
		 }
    }

		} else {
			$results->error = 'Filename is "'.$filename."\"\n";
		}
		
		send_json($results);
	}

	public function preview_check() {
		$allComplete = true;
		$response = new JsonObject();

		// get the design merge job name
		$jobname = $this->CI->session->userdata(B2B_BIRTHDAY_JOBNAME);
		if(!$jobname) {
			$response->setError('No Job');
		} else {
			if (is_array($jobname)) {
				
				// check on the job's progress
        $files = array();
				foreach($jobname as $job) {
					$status = $this->designmerge->getStatus($job, NULL, TRUE);					
					if(($status->progress == 100)) {
						$files[] = $status->filename;
					} else {
						$allComplete = false;
					}
				}

				if ($allComplete) {
        	$this->CI->session->set_userdata(B2B_BIRTHDAY_JOBNAMES, $jobname);
					$result = $this->designmerge->combinePDFs($files);
					$this->CI->session->set_userdata(B2B_BIRTHDAY_JOBNAME, $result);					
				} else {        
          $response->message = 'Processed '.count($files).' of '.count($jobname).' items...';
        }
			} else {				
				$status = $this->designmerge->getStatus($jobname, NULL, TRUE);
				$response->complete = ($status->progress == 100);
				$response->message = $status->statusMessage . ' (' . $status->progress . '%)';
	
				if($response->complete) {
					// create a download link
					$response->pdf = site_url(getDownloadURL($status->filename, FALSE, FALSE));				
				}
			}
		}
		send_json($response);
	}

	public function submit_postcard() {

    $CI =& get_instance();
    
    // print_r($_POST);
    // die();    
          
		nocache();
		$ret = new JsonObject();

		// Get the name of the output file from the job status object
		$jobname = $this->CI->session->userdata(B2B_BIRTHDAY_JOBNAME);
		$status = $this->designmerge->getStatus($jobname);

		// move the completed file to the dynamic folder
		$basename = basename($status->filename);
		rename($status->filename, PDFDYNAMIC . $basename);
		$highres_link = site_url(getDownloadURL(PDFHIGHRES.str_replace('.pdf', '_HR.pdf', $basename), FALSE, FALSE));

		// save the completed proposal
		$this->productcustomizer->init($this->product);
		$addon = '_'.dechex(time()/10);
    $_POST['__jobnames'] = $this->CI->session->userdata(B2B_BIRTHDAY_JOBNAMES);
		$id = $this->productcustomizer->createCustomProduct($basename, $_POST, TRUE, $addon);

		// hack to avoid multiple copies of an item in the db
		// TODO:  This is very bad -- why do we have to do this??
		if($this->generated_product != NULL) {
		  $this->db->where('products_id', $this->generated_product->products_id)->limit(1)->delete('products');
		}

		// set new title
		$new_title = 'Birthday Card Wellness Campaign ' . $this->input->post('campaign_name') . ' ' . $this->input->post('state');
		$this->db->where('products_id', $id)
			->update(TABLE_PRODUCTS_DESCRIPTION, array('products_description' => $new_title));

    // Special handling for actually submitting the order
    if($this->input->post('current_step') == '2') {
        
      $current_product = $this->products->getProduct($id);
        
      // TODO:  Calculate the price and the email contents!!
      // Should be able to base this on the summary code
      
      // Jmadrigal -- Added in need for Mapping the Data
      // Try to put an order in the database for this item
      // This was all borrowed/modified from the Free Quote code
      $this->db->insert('orders', array(
        'orders_name' => 'B2B - Birthday Campaign Order',  
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
        'future_ship_date' => date('m/d/Y'),
        'arrival_date' => date('m/d/Y')
      ));
      $insert_id = $this->db->insert_id();
      $this->db->insert('orders_products', array(
        'orders_id' => $insert_id,                                    
        'orders_entry' => 1,
        'products_id' => $current_product->products_id, 
        'products_model' => 'B2B Product', 
        'products_name' => $current_product->products_description, 
        'products_price' => 1.00, 
        'final_price' => 1.00, 
        'products_tax' => '', 
        'products_quantity' => 2000,
        'state_name' => '', 
        'products_sku' => $current_product->products_sku,   
        'progress_import' => 0,
        'reorderable' => 0
      ));
      
      // Copy the data file to a more permanent location on the filesystem
      $data_file = CPOD_PRIVATE_FILES."orders/B2B/Birthday/{$insert_id}/".basename($this->input->post('uploaded_filename'));
      @mkdir(CPOD_PRIVATE_FILES."orders/B2B/Birthday/{$insert_id}/", 0755, true);
      copy($this->input->post('uploaded_filename'), $data_file);
        
      // Generate the email body for the email
      $mail_in_months = $this->calculateMailInMonths();
      $result = $this->calculateSummary($this->input->post('uploaded_filename'), $mail_in_months);      
      $message = '';
      
      $message .= 'Anthem CPOD'.N.N;     
      $message .= EMAIL_SEPARATOR.N.N;
      
      $message .= 'Order No.: '.$insert_id.' Order by '.$this->CI->user->firstname.' '.$this->CI->user->lastname.N.N;
      $message .= 'Order Date: '.strftime('%A, %d %B %G').N.N;      
      $message .= EMAIL_SEPARATOR.N.N;
      
      $message .= 'Campaign: '.$_POST['campaign_name'].N.N;
      $message .= 'State: '.$_POST['state'].N.N;
      $message .= 'Co-Brand: '.ucwords($this->input->post('use_cobranding')).N.N;
      $message .= 'Company: '.$this->getCurrentCompanyName().N.N;
      $message .= 'Custom Envelope: '.ucwords($this->input->post('custom_envelope')).N.N;
      $message .= 'Return Address:'.N.N;
      $message .= $_POST['address_1'].N.N;
      $message .= ($_POST['address_2'] != '' ? $_POST['address_2'] : '').N.N;
      $message .= $_POST['city'].', '.$_POST['state'].' '.$_POST['zip'].N.N;      
      $message .= EMAIL_SEPARATOR.N.N;
      
      $message .= 'Number of Records: '.$result['TOTALROWS'].N.N;
      $message .= 'Number of Mailing Records: '.$result['GOODROWS'].N.N;
      $message .= 'Number of Unused Records: '.$result['UNUSEDROWS'].N.N;
      $message .= 'Number of Bad Records: '.count($result['BADROWS']).N.N;
      $message .= 'Campaign Total Print Cost: $'.number_format($result['CAMPAIGN']['total'],2).N.N;
      $message .= 'Campaign Total Fulfillment & Postage: $'.number_format($result['CAMPAIGN']['shipping'],2).N.N;      
      $message .= EMAIL_SEPARATOR.N.N;
      
      foreach($result['months'] as $line=>$values) {
        if (array_key_exists($line, $mail_in_months)) {
          $message .= $this->months_array[$line].' Mailing Information'.N.N;
          $message .= 'Number of Records: '.$result['months'][$line]['records'].N.N;
          $message .= 'Estimated Total Print Cost: $'.number_format($result['months'][$line]['total'],2).N.N;
          $message .= 'Estimated Unit Cost: $'.number_format($result['months'][$line]['unit_letter_cost'],2).'/Letter'.N.N;
          $message .= 'Estimated Unit Cost: $'.number_format($result['months'][$line]['unit_env_cost'],2).'/Envelope'.N.N;
          $message .= 'Estimated Fulfillment & Postage: $'.number_format($result['months'][$line]['shipping'],2).N.N;          
          $message .= 'Quantities: '.N.N;
          foreach($result['months'][$line]['categories'] as $cat=>$cat_values) {
            $message .= '* '.$this->copy_and_image_array[$cat]['name'].': '.$result['months'][$line]['categories'][$cat]['records'].N.N;
          }
          $message .= N.N;
        }
      }
      
      // Send a confirmation email      
      $this->CI->load->library('email');
      $email =& $this->CI->email;
      $email->set_wordwrap(FALSE);
      $email->from('Anthem.Automation@fetter.us');
      $email->to($this->CI->user->default_email);      
      $email->cc('fetter2.us@gmail.com');
      $email->bcc('SmartDoxB2B@fettergroup.com');
      $email->subject('B2B Birthday Campaign Order Notification');      
      $email->message($message);
      $email->attach(PDFDYNAMIC.$basename);
      $email->send();
      
      // Send the internal email indicating an order was placed
      $message = '';
      $message .= 'Anthem CPOD'.N.N;     
      $message .= EMAIL_SEPARATOR.N.N;      
      $message .= 'Order No.: '.$insert_id.' Order by '.$this->CI->user->firstname.' '.$this->CI->user->lastname.N.N;
      $message .= 'Order Date: '.strftime('%A, %d %B %G').N.N;    
      $message .= 'Uploaded CSV: '.$data_file.N.N;      
      $message .= 'DesignMerge Data Files:'.N.N;
      $message .= 'Job Names: '.implode(', ', $this->CI->session->userdata(B2B_BIRTHDAY_JOBNAMES));      
      
      $email->clear(TRUE);
      $email->set_wordwrap(FALSE);
      $email->from('Anthem.Automation@fetter.us');
      $email->to('SmartDoxB2B@fettergroup.com');       // 'SmartDoxB2B@fettergroup.com'     
      $email->subject('Birthday Campaign Order Placed');      
      $email->message($message);
      $email->send();      
    }    
      
		send_json($ret);	
	}

	private function getUploadPath($path) {
		$sess_id = $this->CI->session->userdata('session_id');
		$upload_path = CPOD_TEMP_FILES . $sess_id . '/' . B2B_BIRTHDAY_UPLOAD_PATH;
		return make_path_safe($path, $upload_path);
	}
  
  private function getCurrentCompanyName() {
    $CI =& get_instance();
    return $CI->user->current->company->name;
  }
}