<?php

define('KEY_PROPOSAL_FILES', 'proposal_files');
define('KEY_PROPOSAL_COMBINE_JOB', 'proposal_combine_job');
define('KEY_PROPOSAL_FILE_TO_SEND', 'proposal_file_to_send');

/**
 * 
 *
 * @author PDeJarnett
 * @author Jmadrigal
 *
 * @param Controller $CI
 * @param CI_DB_active_record $db
 * @property CI_Template $template
 * @param Products $products
 * @param ProductCustomizer $productcustomizer
 * @param DesignMerge $designmerge
 * @param Formidable $formidable
 */
abstract class ProposalsProductTemplate extends CustomProductTemplate {

	/** @var string $temp_dir Used to store temporary files for this proposal. */
	public $temp_dir = '';

	protected $jobname = '';

	public $selected_options_field = NULL;
	public $selected_options = '';

	//----------------------------------------------------------------------

	public function __construct($custom) {
		parent::__construct($custom);
		$this->CI->load->helper('debug');
	}

	//----------------------------------------------------------------------

	/**
	 * Overridden to create the temp folder
	 */
	public function lookupProduct($productId) {
		parent::lookupProduct($productId);
		$this->temp_dir = CPOD_TEMP_FILES . $this->CI->session->userdata('session_id') . '/proposals/' . $this->product->products_id . '/';
		if(!file_exists($this->temp_dir)) {
			mkdir($this->temp_dir, 0777, TRUE);
		}
	}

	//----------------------------------------------------------------------

	/**
	 * The root function for handling the page request.
	 * If you want to completely override the default functions, replace this
	 * method!
	 * 
	 * @param mixed $productId
	 */
	public function index() {
		// clear any previous proposal information
		$this->CI->session->unset_userdata(KEY_PROPOSAL_FILE_TO_SEND);
		$this->CI->session->unset_userdata(KEY_PROPOSAL_COMBINE_JOB);
		$this->CI->session->unset_userdata(KEY_PROPOSAL_FILES);

		$this->template->set_template('fancybox');
		$this->template->write('title', 'Build "'.$this->product->products_description.'"');

		$this->formidable->setFormValidateAjax(TRUE);
		$this->formidable->setWizardAutoSubmit(FALSE);
		$this->formidable->setWizardLastButtonLabel('Send Proposal');

		$this->formidable->setFormAttribute('id', 'proposal_form');

		$this->formidable->setValues($this->original_values);

		$this->configureForm();

		$this->renderForm();

		$skip_iAvenue = 'window.skip_iavenue = '.(empty($this->generated_product) ? 'false' : 'true').';';

		// add this at the end
		if(strpos($_SERVER['SERVER_NAME'], 'local') === 0) {
			// local server, need to call back to SmartDox
			$this->CI->include_js->embed('window.iavenue_url = "'.current_url().'/iavenue"; '.$skip_iAvenue);
		} else {
			// not local, use a local call
			$this->CI->include_js->embed('window.iavenue_url = "'.site_url('iavenue').'"; '.$skip_iAvenue);
		}
		$this->CI->include_js->custom('js/proposals.js', 'css/proposals.css');
		
		$this->template->render();
	}

	//----------------------------------------------------------------------

	/**
	 * Utility Method to handle common document uploads
	 * @param Formidable $form Form or subform to add the control to.
	 * @param string $id ID for the upload
	 * @param string $label override the default label handling
	 * @param array $options Additonal options
	 */
	protected function addDocumentUpload($form, $id, $label = FALSE, $options = array()) {
		$options['path'] = 'proposals/'.$this->product->products_id;
		$form->addDocumentUpload($id, $label, TRUE, 1, TRUE, $options);
	}

	//----------------------------------------------------------------------

	/**
	 * Utility Method to handle common document/excel uploads
	 * @param Formidable $form Form or subform to add the control to.
	 * @param string $id ID for the upload
	 * @param string $label override the default label handling
	 * @param array $options Additonal options
	 */
	protected function addDocumentAndExcelUpload($form, $id, $label = FALSE, $options = array()) {
		$options['types'] = 'doc|docx|pdf|rtf|rtx|xls|xlsx';
		$this->addDocumentUpload($form, $id, $label, $options);
	}

	//----------------------------------------------------------------------

	/**
	 * Used to add the iAvenue page to the proposal.
	 * This should be called first!
	 */
	protected function setupIAvenue() {
		$page = $this->formidable->addWizardPage('iAvenue', array('id'=>'iavenue_page'));
		$page->addNote("Please enter your opportunity ID to begin the proposal.<br/>If you prefer, you may click <b>Next</b> to continue without entering an Opportunity ID.");
		$page->addMultifieldRow('opportunity_id_row', 'Opportunity ID');
		$page->addMultifieldItem('opportunity_id_row', 'opportunity_id', 'text', '');
		$page->addMultifieldItem('opportunity_id_row', 'opportunity_id_button', 'inputbutton', 'Lookup');
		$page->addMultifieldItem('opportunity_id_row', 'opportunity_id_clear', 'inputbutton', 'Clear');
		$page->addHTML('<tr><td colspan="3" id="iavenue_response"></td></tr>');
	}

	//----------------------------------------------------------------------

	protected function setupPreview() {
		$page = $this->formidable->addWizardPage('Preview', array('id'=>'preview_page'));
		$page->addHTML('<tr><td colspan="3" id="preview_response">
			<div class="spinner"></div>
			<div class="message"></div>
			<div class="status"></div>
			</td></tr>');
	}

	//----------------------------------------------------------------------

	protected function setupSendProposal($subject, $body_text, $attachment_name, $selected_options_field = NULL) {
		$page = $this->formidable->addWizardPage('Send Proposal', array('id'=>'send_proposal'));
		$page->addNote('Your proposal is ready. Complete the form below and click <strong>Send</strong> to destribute this proposal!');
		$message = $page->addFieldset('');
		$message->addNote('Separate multiple email address in the To or Cc field with a comma (me@example.com, you@example.com).');
		$message->addEmailAddressField('message_to', 'To', '', TRUE, TRUE);
		$from = '';
		if(!empty($this->CI->user->default_email)) {
			$from = $this->CI->user->name . ' <'.$this->CI->user->default_email.'>';
		}
		$message->addEmailAddressField('message_from', 'From', $from, TRUE);
		$message->add('message_copy', 'Send a copy of this email to me', 'checkbox', true);
		$message->addEmailAddressField('message_cc', 'Cc', '', TRUE, TRUE, FALSE);
		$message->add('message_subject', 'Subject', 'text', $subject, array('required'));
		$message->add('message_body', FALSE, 'richtext', $body_text, array('required'), array('rows' => 16, 'field_before' => '<div><label id="message_body_label" for="message_body">Content:</label></div>'));
		$message->add('message_attach_name', 'Attachment Name', 'text', $attachment_name, array('required'), array('field_after' => '.pdf<br/><span class="size"></span><br/><p><em>Please note: any <abbr title="Invalid Characters: / \ ? % * : | &quot; &lt; &gt;">characters that are invalid</abbr> for a filename are automatically replaced with an underscore.</em></p>'));

		$this->selected_options_field = $selected_options_field;
	}

	//----------------------------------------------------------------------

	/**
	 * Nasty hack to allow local iavenue lookups
	 */
	public function iavenue() {
		nocache();
		$url = 'https://beta.smart-dox.com/smartdox/iavenue';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('opportunity_id' => $this->input->get_post('opportunity_id')));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$output = curl_exec($ch);
		curl_close($ch);
		$response = json_decode($output);
		if(is_null($response)) {
			echo($output);
		} else {
			send_json($response);
		}
	}

	//----------------------------------------------------------------------

	/**
	 * This AJAX method is where the preview is generated.
	 */
	public function preview_generate() {
		nocache();
		$this->CI->session->unset_userdata(KEY_PROPOSAL_FILE_TO_SEND);
		$this->CI->session->unset_userdata(KEY_PROPOSAL_COMBINE_JOB);
		$this->CI->session->unset_userdata(KEY_PROPOSAL_FILES);
		
		// set up form fields
		$this->configureForm(TRUE);

		// check form
		if(!$this->formidable->shouldSave()) {
			$ret = $this->formidable->getJsonResult();

		} else {
			$ret = $this->formidable->getJsonResult();
			// allow the subclass to begin generating the PDFs
			$files = $this->start_proposal_generation();
			if(is_string($files)) {
				// if the result is a string, assume an error
				$ret->error = $files;
			} else if(is_array($files)) {
				// if it is an array, then all PDFs were started correctly.
				$sess = array();
				foreach($files as $job => $desc) {
					if(is_int($job)) {
						$job = $desc;
					}
					$info = new stdClass();
					$info->desc = $desc;
					$info->progress = 0;
					$info->message = '';
					$sess[$job] = $info;
				}
				$this->CI->session->set_userdata(KEY_PROPOSAL_FILES, $sess);
			} else {
				// for anything else, assume there was a form error set.
				// re-get the result, to get new errors.
				$ret = $this->formidable->getJsonResult();
				if(empty($ret->error)) {
					// no errors?  Then set a default.
					$ret->error = 'An unexpected error has occurred.';
				}
			}
		}
		send_json($ret);
	}

	//----------------------------------------------------------------------

	/**
	 * Start generating the proposal based on the input values.
	 *
	 * The returned array /should/ be in the form of:
	 * array(
	 *     'jobname_or_pdf' => 'Descriptive Name for User'
	 * )
	 *
	 * But this format is also accepted
	 * array(
	 *     'jobname_or_pdf'
	 * )
	 *
	 * Job names should have been returned from a DesignMerge method.
	 * PDFs can either be relative to PDFDYNAMIC or PDFSTATIC, or absolute paths (starting with '/').
	 *
	 * The order of the returned array is used when combining the PDFs!
	 *
	 * @return mixed Array of job names and static files to combine, FALSE if a form error occurs, or a string error message
	 */
	abstract protected function start_proposal_generation();

	//----------------------------------------------------------------------

	/**
	 * Utility function to find the most recent revision of a static file.
	 *
	 * This method searches from $max down to 1, for products named
	 *   $file-$i.pdf
	 * Under PDFSTATIC.  The highest numbered one found is returned.
	 *
	 * If none are found, then it looks for $file.pdf.
	 *
	 * If nothing is found, it returns FALSE.
	 *
	 * @param string $file Base file name (.pdf is automatically appended if needed)
	 * @param int $max Maximum number of items to search for (5)
	 * @return bool|string File name if found, or FALSE otherwise.
	 */
	protected function findStatic($file, $max = 5) {
		$file = str_replace('.pdf', '', $file);
		$found = FALSE;
		for($i=$max; $i>0; $i--) {
			if(file_exists(PDFSTATIC . $file . '-' . $i . '.pdf')) {
				$file .= '-' . $i . '.pdf';
				$found = TRUE;
				break;
			}
		}
		if(!$found) {
			if(file_exists(PDFSTATIC . $file . '.pdf')) {
				$file .= '.pdf';
			} else {
				$file = FALSE;
			}
		}
		return $file;
	}

	//----------------------------------------------------------------------

	/**
	 * Nice utility function to help with spinning off workflows.
	 *
	 * @param array $values The array of form values.
	 * @param mixed $workflow Number or string to append to the jobname (ie: 1 => jobname-w1, 2-statement => jobname-w2-statement)
	 * @param string $template Override the product's template
	 * @param array $mapConfig Optional mapping passed to DesignMerge::map
	 * @return string The final designmerge jobname
	 */
	protected function build_workflow($values, $workflow = 1, $template = NULL, $mapConfig = NULL, $job_label = FALSE) {
		$this->productcustomizer->init($this->product);
		if(empty($this->jobname)) {
			$this->jobname = $this->designmerge->jobname;
		}
		if(is_null($mapConfig)) {
			$mapConfig = array();
		}
		if(!empty($template)) {
			$mapConfig['DM_Template'] = '=' . $template;
		}
		$this->designmerge->map($values, $mapConfig);
		$this->designmerge->jobname = $this->jobname . '-w' . $workflow;
		$this->productcustomizer->setFormNumber();
		$this->designmerge->noHighRes();
		$this->designmerge->go();
		// Note: we have to return the jobname AFTER design merge completes,
		// because it changes once the product starts.
		if($job_label) {
			return array($this->designmerge->jobname => $job_label);
		} else {
			return $this->designmerge->jobname;
		}
	}

	//----------------------------------------------------------------------

	/**
	 * This AJAX method is used to check on the status of the proposal
	 * components.
	 */
	public function preview_check() {
		nocache();
		// set up the return object
		$ret = new ProposalPreviewCheck();

		if($this->CI->session->userdata(KEY_PROPOSAL_COMBINE_JOB)) {
			// already combining, check on that progress
			$status = $this->designmerge->getStatus($this->CI->session->userdata(KEY_PROPOSAL_COMBINE_JOB));
			if($status->progress == 100) {
				$new_name = $this->temp_dir . basename($status->filename);
				$success = rename($status->filename, $new_name);
				if($success) {
					$this->CI->session->set_userdata(KEY_PROPOSAL_FILE_TO_SEND, $new_name);
					chmod($new_name, 0777);
					$ret->complete = TRUE;
					$ret->pdf = site_url(getDownloadURL($new_name, TRUE, FALSE));
					$ret->message = 'Your proposal has been built!'
							. '<p class="submessage">Please <a href="'.$ret->pdf.'" target="_blank">download the PDF</a> and verify the final proposal, then click Next to distribute it.'
							. '<br/>If you need to make modifications, please click Back, or you can jump to a section by clicking the name above.</p>';
					$this->CI->load->helper('number');
					$ret->size = 'Attachment Details: ' . byte_format(filesize($new_name)) . ' &mdash; ' . $status->pageCount . ' pages';
				} else {
					$ret->error = 'Unable to move the completed file.';
				}
			} else if($status->progress == -1) {
				$ret->error = $status->statusMessage;
			}
		} else {

			$files = $this->CI->session->userdata(KEY_PROPOSAL_FILES);
			
			// track to see if any file is still being generated
			$stillGenerating = FALSE;
			// track to see if any file has an error (progress = -1)
			$progress_error = FALSE;
			// track full file names in case we are ready to combine
			$files_to_combine = array();

			// we need to check each file to see it's status
			// info is by-ref so we can update it directly
			foreach($files as $job => &$info) {
				if($info->progress == -1) {
					// this item already had a progress error
					$progress_error = TRUE;
				} else {
					// Even if completed, we need to add these items to the
					// files_to_combine list.
					$finish = FALSE;
					if($job[0] == '/' && file_exists($job)) {
						// found a hard-linked file
						$finish = $job;
					} else if(file_exists($this->temp_dir . $job)) {
						// found an already processed file
						$finish = $this->temp_dir . $job;
					} else if(file_exists($this->temp_dir . $job . '.pdf')) {
						// found an already processed file (with a PDF extension)
						$finish = $this->temp_dir . $job . '.pdf';
					} else if(file_exists(PDFDYNAMIC . $job)) {
						// found a dynamic PDF file
						$finish = PDFDYNAMIC . $job;
					} else if(file_exists(DESIGNMERGE_FOLDER_OUT . $job)) {
						// found a design merge output PDF file
						$finish = DESIGNMERGE_FOLDER_OUT . $job;
					} else if(file_exists(PDFSTATIC . $job)) {
						// found a static PDF
						$finish = PDFSTATIC . $job;
					} else {
						// must be being generated in some manner (incl. convert-to-PDF)
						$c = $this->designmerge->getStatus($job, NULL, TRUE);
						if($c == FALSE) {
							$ret->error = "An unexpected error has occurred: Job '$job' not found!";
						} else {
							// use DM results
							$info->progress = $c->progress;
							$info->message = $c->statusMessage;
							if($c->progress == -1) {
								// errored somehow
								$progress_error = TRUE;
							} else if($c->progress < 100) {
								// if not completed, then we have to wait some more.
								$stillGenerating = TRUE;
							} else {
								if($c->combine_pdfs || $c->convert_to_pdf) {
									// move to temp
									$new_name = $this->temp_dir . $job . '.pdf';
								} else {
									// move to the dynamic pdfs folder
									$new_name = PDFDYNAMIC . $job . '.pdf';
								}
								if(!file_exists($new_name)) {
									$success = FALSE;
									if(file_exists($c->filename)) {
										// move to the temp folder
										$success = rename($c->filename, $new_name);
									}
									if(!$success) {
										$progress_error = TRUE;
										$info->progress = -1;
										$info->message = 'Error moving completed file.';
									} else {
										$files_to_combine[] = $new_name;
									}
								} else {
									$files_to_combine[] = $new_name;
								}
							}
						}
					}
					// handle simple files that are already completed
					if($finish) {
						$info->progress = 100;
						$info->message = 'Finished';
						$files_to_combine[] = $finish;
					}
				}
			}


			if($stillGenerating) {
				$ret->message = 'Generating Files...';
			} else {
				// Everything is generated.
				if($progress_error) {
					// can't combined when an error occurred.
					$ret->error = 'Unable to continue: there was an error generating one or more of your files.';
				} else {
					// start combining.
					$ret->message = 'Combining Files...';
					$ret->combining = TRUE;
					$combine_job = $this->designmerge->combinePDFs($files_to_combine);
					$this->CI->session->set_userdata(KEY_PROPOSAL_COMBINE_JOB, $combine_job);

					// we don't need to track the files anymore...
					$this->CI->session->unset_userdata(KEY_PROPOSAL_FILES);
				}
			}

			// track the files list.
			$this->CI->session->userdata(KEY_PROPOSAL_FILES, $files);
			$ret->files = $files;
		}
		
		send_json($ret);
	}

	public function send_proposal() {
		nocache();
		// set up form fields
		$this->configureForm(FALSE, TRUE);
		if($this->formidable->validate()) {
			$ret = $this->formidable->getJsonResult();
			$this->CI->load->library('email');
			$this->CI->load->helper('html_to_plain');
			$email =& $this->CI->email;
			
			// configure email
			$email->set_wordwrap(FALSE);
			$email->set_mailtype('html');

			// set email addresses
			$email->from($this->input->post('message_from'));
			$email->to($this->input->post('message_to'));
			$cc = trim($this->input->post('message_cc'));
			if($this->input->post('message_copy')) {
				if(!empty($cc)) {
					$cc .= ', ';
				}
				$cc .= $this->input->post('message_from');
			}
			if(!empty($cc)) {
				$email->cc($cc);
			}

			// set content
			$email->subject($this->input->post('message_subject'));
			$body = $this->input->post('message_body');
			$email->message($body);
			// sets a nice-looking plain-text alternative to the original message.
			$email->set_alt_message(html_to_plain($body));

			// Set the attachment
			$attachment = $this->CI->session->userdata(KEY_PROPOSAL_FILE_TO_SEND);
			$folder = dirname($attachment);
			$send_name = preg_replace('/[\/\\\\\?\%\*\:\|"\<\>]+/', '_', $this->input->post('message_attach_name'));
			$renamed_attachment = make_path_safe($send_name.'.pdf', $folder);
			if(file_exists($renamed_attachment)) {
				unlink($renamed_attachment);
			}
			$success = file_exists($attachment) && link($attachment, $renamed_attachment);
			if(!$success) {
				log_message('error', "Unable to link attachment from $attachment to $renamed_attachment");
				$ret->error = 'Server error setting attachment.';
			} else {
				$email->attach($renamed_attachment);
				if(!$email->send()) {
					$ret->error = 'Unable to send your message.  Please try again.';
				} else {
					// move the completed file to the dynamic folder
					$basename = basename($attachment);
					rename($attachment, PDFDYNAMIC . $basename);
					// save the completed proposal
					$this->productcustomizer->init($this->product);
					$addon = '_'.dechex(time()/10);
					$id = $this->productcustomizer->createCustomProduct($basename, $_POST, TRUE, $addon);
					// set new title
					$new_title = 'Proposal for: ' . $this->input->post('group_name') . ' ('.date('D, M j Y g:i A T').')';
					$this->db->where('products_id', $id)
							->update(TABLE_PRODUCTS_DESCRIPTION, array('products_description' => $new_title));

					// store in the log
					if(!empty($this->selected_options_field)) {
						$this->selected_options = $this->input->post($this->selected_options_field);
						if($this->selected_options === FALSE) {
							$this->selected_options = '';
						}
					}
					if(is_array($this->selected_options)) {
						$this->selected_options = implode(', ', $this->selected_options);
					}
					$this->db->insert(TABLE_PROPOSALS_LOG, array(
						'products_id' => $id,
						'user_id' => $this->CI->user->id,
						'email_to' => $this->input->post('message_to'),
						'email_cc' => $this->input->post('message_cc'),
						'email_from' => $this->input->post('message_from'),
						'selected_options' => $this->selected_options
					));
				}
			}
		} else {
			$ret = $this->formidable->getJsonResult();
		}
		send_json($ret);
	}

}

class ProposalPreviewCheck {
	public $error = FALSE;
	public $complete = FALSE;
	public $combining = FALSE;
	public $message = '';
	public $files = FALSE;
	public $pdf = FALSE;
	public $size = '';
}