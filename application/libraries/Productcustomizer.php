<?php

/**
 * Helps in the process of getting a product configured for
 * DesignMerge.
 *
 * @author PDeJarnett
 * @author Jmadrigal
 */
class ProductCustomizer {

	/** @var Controller */
	public $CI;

	/** @var CI_DB_active_record */
	public $db;
	
	/** @var DesignMerge */
	private $designmerge;

	/** @var ProductResult $product */
	private $product;

	/** @var int $new_products_id */
	public $new_products_id = FALSE;

	/** @var bool $autosetFormNumber If TRUE, the formnumber will be updated as necessary. */
	public $autosetFormNumber = TRUE;

	//----------------------------------------------------------------------

	public function __construct($config = array()) {
		$this->CI =& get_instance();
		// we want our own DB object
		$this->db = clone_db();
		$this->CI->load->library('designmerge');
		$this->designmerge = $this->CI->designmerge;

		foreach($config as $k => $v) {
			$this->{$k} = $v;
		}
	}
	
	//----------------------------------------------------------------------

	/**
	 * Initializes the product passed in, based on the User's current config.
	 * $product can be either a product ID, or it can be the result of
	 * a product query.
	 *
	 * @param mixed $product
	 * @param int $account_type If provided, overrides the default account type for attributes lookup.
	 */
	public function init($product, $account_type = NULL) {
		// load the product
		$this->CI->load->library('products');
		$this->product = $this->CI->products->getProduct($product, 'pd.product_booklet_type, pd.products_template, p.required_product');
		if($this->product->base_products_id) {
			// only work from a base product
			$this->product = $this->CI->products->getProduct($this->product->base_products_id, 'pd.product_booklet_type, pd.products_template, p.required_product');
		}
		// reset the DM information
		$this->designmerge->reset();

		// configure default product info
		$this->designmerge->DM_Template = $this->getTemplate();
		$this->designmerge->Logo_1_Anthem = $this->getAnthemLogo();
		$this->designmerge->Logo_3_Dual = $this->getDualBrandLogo();
		$this->designmerge->ReservedH = empty($this->product->product_booklet_type) ? '' : $this->product->product_booklet_type;
		$this->designmerge->Form_Number = $this->product->products_name;

		$this->setAttributes($account_type);

		// reset the autosetFormNumber flag.
		$this->autosetFormNumber = TRUE;
	}

	private function _check_init() {
		if(empty($this->product) || empty($this->product->products_id)) {
			show_error('ProductCustomizer was not initialized!');
		}
	}

	//----------------------------------------------------------------------

	/**
	 * Starts the design merge process, and inserts the new product into the
	 * database.
	 *
	 * @param array $values Associative array or object containing key-value pairs of the submitted form.  Stored with the product for future reference.
	 * @return string|bool If FALSE, the product is built, otherwise, the name of the DesignMerge job.
	 */
	public function start($values = NULL) {
		if($this->product->required_product && !empty($this->product->products_pdfupload) &&  strpos($this->product->products_pdfupload, '.pdf') !== FALSE) {
			// special case for combining two static products
			$job = $this->combineRequiredProduct($this->product->products_pdfupload, $this->product->required_product);
			$this->createCustomProduct($job . '.pdf', $values, FALSE);

			return $job;

		} else {
			// The form number is set last, so that it can be modified if necessary
			if($this->autosetFormNumber) {
				$this->setFormNumber();
			}

			$pending = $this->designmerge->go();
			
			$this->createCustomProduct($this->designmerge->outputfile, $values, FALSE);

			if($pending) {
				return $this->designmerge->jobname;
			} else {
				return FALSE;
			}
		}
	}

	//----------------------------------------------------------------------

	/**
	 * Inserts or updates a product in the database directly.
	 *
	 * This can be used for products that are not normally generated, such as
	 * packages or uploaded documents.
	 *
	 * @param string $pdffile The name of the PDF file to for this product.  (may be blank)
	 * @param array $values Associative array or object containing key-value pairs of the submitted form.  Stored with the product for future reference.
	 * @param bool $completed If TRUE, then mark the product as complete.  Also handles adding page counts if necessary.  Defaults to TRUE.
	 * @param string $append_to_name This string is added to the products name.  Defaults to ''.
	 * @return int Returns the generated product's ID number.
	 */
	public function createCustomProduct($pdffile, $values = NULL, $completed = TRUE, $append_to_name = '') {
		$this->_check_init();
		// The form number is set last, so that it can be modified if necessary
		if($this->autosetFormNumber) {
			$this->setFormNumber();
		}

		// get the database product name (<generated form number>_<customer id>)
		// remove everything after and including the first space.
		$product_name = preg_replace('/ .*/', '', $this->designmerge->formnumber);
		// append the customer (or company) id to make sure it's unique.
		$product_name .= '_' . $this->CI->user->getCurrentUserProperty('id') . $append_to_name;
		$cost_center = empty($this->CI->user->cost_centers) ? '' : $this->CI->user->cost_centers[0];
		// call the procedure with the bound parameters
        $this->db->query('CALL update_pod_product(?, ?, ?, ?, ?, ?);', array(
			$this->product->products_id,
			$this->CI->user->getCurrentUserProperty('id'),
			$product_name,
			$pdffile,
			$cost_center,
			$this->CI->user->current->state->code
		));

		/* @var $query CI_DB_result */
        $query = $this->db
					->select('p.products_id')
					->from(TABLE_PRODUCTS.' p')
					->join(TABLE_PRODUCTS_DESCRIPTION.' pd', 'p.products_id = pd.products_id')
					->where('p.cID', $this->CI->user->getCurrentUserProperty('id'))
					->where('p.for_state', $this->CI->user->current->state->id)
					->where('pd.products_sku', $product_name)
					->get();
        $new_products_id = $query->row()->products_id;

		// Store the submitted values
		if(!empty($values)) {
			if(!is_string($values)) {
				$values = json_encode($values);
			}
			// add the values to that new or updated row
			$this->db->update(
					TABLE_PRODUCTS,
					array('customized_values' => $values),
					array('products_id' => $new_products_id)
				);
		}
		
		if($completed) {
			if(file_exists(PDFDYNAMIC . $pdffile)) {
				// check and store the page count
				$page_count = get_pdf_page_count(PDFDYNAMIC . $pdffile);
				if ($page_count >= 1) {
					$this->db->update(TABLE_PRODUCTS,
							// SET
							array('pages' => $page_count),
							// WHERE
							array('products_id' => $new_products_id));
				}
			}

			// note that the product is completed
			$this->db->where('products_id', $new_products_id)
					->update(TABLE_PRODUCTS_DESCRIPTION, array('product_type' => 1));

			// track updated products
			$new_products = $this->CI->session->userdata(KEY_CATALOG_NEW_PRODUCTS);
			$new_products[] = $new_products_id;
			$this->CI->session->set_userdata(KEY_CATALOG_NEW_PRODUCTS, $new_products);
		}

		$this->new_products_id = $new_products_id;
		
		return $new_products_id;
	}

	//----------------------------------------------------------------------

	/**
	 * DesignMerge Field Mapping
	 * @see DesignMerge::map
	 * @param array|object $values
	 * @param array $mapConfig
	 */
	public function map($values, $mapConfig = NULL) {
		$this->designmerge->map($values, $mapConfig);
	}

	//----------------------------------------------------------------------

	/**
	 * Handles the final setting of the form number.
	 * It both handles automatic form number modification, as well as adding
	 * revision and other customized information.
	 *
	 * Also sets autosetFormNumber to FALSE, to prevent from running more than once.
	 */
	public function setFormNumber() {
		$this->_check_init();
		$fn = $this->designmerge->formnumber;
		if(strpos($fn, 'xx') !== FALSE) {
			$type = $this->CI->user->national_account ? 'national' : 'local';
			if($this->CI->input->get_post('anthem_region_id')) {
				// allow the form to override the default region id for logos
				$region_id = (int)$this->CI->input->get_post('anthem_region_id');
			} else {
				$region_id = $this->CI->user->getCurrentRegionId();
			}
			// we need to customize this for the URL
			$query = $this->db
						->select("{$type}_xx AS xx")
						->select("{$type}_x AS x")
						->from(TABLE_ANTHEM_REGIONS)
						->where('region_id', $region_id)
						->get();
			$replacements = $query->row();
			$fn = str_replace('xx', $replacements->xx, $fn);
			$fn = str_replace('x', $replacements->x, $fn);
			$fn = strtoupper($fn);
			
			if(!empty($this->product->products_revision)) {
				$fn .= ' ' . $this->product->products_revision;
			}
			$this->designmerge->formnumber = $fn;
		}

		$this->autosetFormNumber = FALSE;
	}

	//----------------------------------------------------------------------

	/**
	 * Looks up the template for the current product.
	 * 
	 * @return string The template path, relative to DESIGNMERGE_TEMPLATE
	 */
	public function getTemplate() {
		$this->_check_init();
		if(strlen($this->product->products_template) > 3) {
			// customized name
			$name = $this->product->products_template;
		} else {
			// default name
			$name = $this->product->products_name . '.indd';
		}
		if(CPOD_TYPE != 'live') {
			// check in beta folder
			if(file_exists(DESIGNMERGE_TEMPLATE . 'beta/' . $name)) {
				$name = 'beta/'.$name;
			}
		}
		return $name;
	}

	//----------------------------------------------------------------------

	/**
	 * Returns the AnthemLogo for the current user settings and product.
	 *
	 * @return string
	 */
	public function getAnthemLogo() {
		$this->_check_init();
		$account_type_code = $this->CI->user->national_account ? 'n' : 'l'; // N for National, L for Local!
		if($this->CI->input->get_post('anthem_region_id')) {
			// allow the form to override the default region id for logos
			$region_id = (int)$this->CI->input->get_post('anthem_region_id');
		} else {
			$region_id = $this->CI->user->getCurrentRegionId();
		}
		/* @var $q CI_DB_result */
		$q = $this->db->select('anthem_logo_eps')
					->from(TABLE_ANTHEM_LOGOS.' al')
					->join(TABLE_ANTHEM_LOGOS_TO_PRODUCTS.' al2p', 'al.anthem_logo_type_id = al2p.anthem_logos_type_id')
					->where('al2p.products_id', $this->product->products_id)
					->where('al2p.account_type', $account_type_code)
					->where('al.region_id', $region_id)
					->get();
		if($q->num_rows() != 1) {
			return '';
		} else {
			$file = $q->row()->anthem_logo_eps;
			if(!file_exists(CPOD_LOGOS_BASE.$file)) {
				#mail('damien.burns@fettergroup.com,phil.dejarnett@fettergroup.com','SmartDox Missing Image',"Image missing: $file\nFor product:{$this->product->products_id}\nRegion: {$this->CI->user->getCurrentRegionId()}\nAccount Type Code:$account_type_code");
			}
			return $file;
		}
	}

	//----------------------------------------------------------------------

	/**
	 * Returns the Dual Brand logo for the current company, if necessary.
	 * 
	 * @return string
	 */
	public function getDualBrandLogo() {
		$this->_check_init();
		$file = '';
		// TODO: handle dual brand being disabled
		if($this->CI->user->national_account && $this->CI->user->current->dual_brand && isset($this->CI->user->current->company)) {
			/* @var $q CI_DB_result */
			$q = $this->db->select('logos_eps')
					->from(TABLE_COMPANY_LOGOS)
					->where('customers_id', $this->CI->user->current->company->id)
					->get();
			if($q->num_rows() == 1) {
				if(!file_exists(CPOD_LOGOS_BASE.$file)) {
					#mail('damien.burns@fettergroup.com,phil.dejarnett@fettergroup.com','SmartDox Missing Image',"Company Logo Image missing: $file\nFor product:{$this->product->products_id}\nRegion: {$this->CI->user->getCurrentRegionId()}\nAccount Type Code:$account_type_code\nCompany ID:{$this->CI->user->current->company->id}");
				}
				$file = $q->row()->logos_eps;
			}
		}
		return $file;
	}

	//----------------------------------------------------------------------

	/**
	 * Sets up any database-driven attributes for the given product.
	 *
	 * @param int $account_type If provided, override the user's account type.
	 * @param int $region_id If provided, override the user's region.
	 */
	public function setAttributes($account_type = NULL, $region_id = NULL) {
		$this->_check_init();
		if(is_null($account_type)) {
			if($this->CI->input->get_post('account_type')) {
				// magic auto-setting of the account type based on input.
				$account_type = (int)$this->CI->input->get_post('account_type');
			} else {
				$account_type = $this->CI->user->account_type->id;
				if(isset($this->CI->user->current->account_type)) {
					$account_type = $this->CI->user->current->account_type->id;
				}
			}
		} else {
			$account_type = (int)$account_type;
		}
		if(is_null($region_id)) {
			if($this->CI->input->get_post('anthem_region_id')) {
				// allow the form to override the default region id for logos
				$region_id = (int)$this->CI->input->get_post('anthem_region_id');
			} else {
				$region_id = $this->CI->user->getCurrentRegionId();
			}
		}

		/* @var $query CI_DB_result */
		$query = $this->db
					->select('av.attribute_value')
					->select('a.dm_field')
					->from(TABLE_ATTRIBUTES_TO_PRODUCTS.' a2p')
					->join(TABLE_ATTRIBUTE_TYPES.' at', 'at.attribute_type_id = a2p.attribute_type_id')
					->join(TABLE_ATTRIBUTES.' a', 'a.attribute_id = at.attribute_id')
					->join(TABLE_ATTRIBUTE_VALUES.' av', 'av.attribute_type_id = a2p.attribute_type_id')
					->where('a2p.products_id', $this->product->products_id)
					->where('a2p.account_type', $account_type)
					->where('av.region_id', $region_id)
					->get();

		foreach($query->result() as $row) {
			$this->designmerge->cleanLineBreaks($row->attribute_value, $row->dm_field);
		}
	}

	//----------------------------------------------------------------------

	/**
	 * Returns information on the current pending products for the
	 * current user.
	 *
	 * This method also handles "completing" a product that is in-progress.
	 *
	 * Default returned object has:
	 * $morePending		bool TRUE if there is still more pending
	 * $inProgress		PendingProductInfo[] Array of pending products;
	 *
	 * @param int $product_id If passed, only returns the status for the
	 *							requested product.
	 * @return object
	 */
	public function getPendingProducts($product_id = NULL) {
		
		$this->designmerge->cleanOldJobs();

		// the return object to JSONify
		$ret = new stdClass();
		$in_progress = array();


		// Get the pending product to check
		$my_ids = array($this->CI->user->id);
		if(!empty($this->CI->user->current->company)) {
			$my_ids[] = $this->CI->user->current->company->id;
		}
		$this->db->select(
				'p.products_pdfupload,
				 p.products_id,
				 p.base_products_id,
				 p.products_date_added,
				 p.products_revision,
				 p.cID,
				 p.for_state,
				 p.pages,
				 p.required_product AS `required_being_added`,
				 pb.required_product,
				 pb.products_pdfupload AS `base_pdfupload`,
				 pd.products_description,
				 pd.products_name,
				 pd.products_note,
				 pd.product_type,
				 (cf.products_id IS NOT NULL) AS favorite,
				 pd.products_sku')
				->from(TABLE_PRODUCTS_DESCRIPTION.' pd')
				->join(TABLE_PRODUCTS.' p', 'p.products_id = pd.products_id')
				->join(TABLE_PRODUCTS.' pb', 'pb.products_id = p.base_products_id')
				->join(TABLE_CUSTOMERS_FAVORITES.' cf', '(cf.products_id = p.products_id AND cf.customers_id = '.$this->CI->user->getCurrentUserProperty('id').')', 'LEFT OUTER');
		if(is_null($product_id)) {
			$this->db
				->where('pd.product_type', 2)
				->where('p.products_status', 1)
				->where_in('p.cID', $my_ids);
		} else {
			$this->db
					->where('p.products_id', $product_id);
		}
		$query = $this->db->get();

		$morePending = FALSE;

		/* @var $product ProductResult */
		foreach($query->result() as $product) {
			$job = str_replace('.pdf', '', $product->products_pdfupload);

			if($product->product_type == 2) {
				if(file_exists(PDFDYNAMIC . $product->products_pdfupload)) {
					// alrady completed, since the file was already moved
					$progress = 100;
					$statusMessage = 'Finished';
					$pageCount = $product->pages;
					if($pageCount == 0) {
						$pageCount = get_pdf_page_count(PDFDYNAMIC . $product->products_pdfupload);
						if ($pageCount >= 1) {
							$this->db->update(TABLE_PRODUCTS,
									// SET
									array('pages' => $pageCount),
									// WHERE
									array('products_id' => $product->products_id));
						}
					}

					// note that the product is completed
					$this->db->where('products_id', $product->products_id)
							->update(TABLE_PRODUCTS_DESCRIPTION, array('product_type' => 1));
				} else {
					$status = $this->designmerge->getStatus($job, $product);
					if($status->progress == 100) {
						if(file_exists($status->filename)) {
							// move the file for this product
							$filename = basename($status->filename);
							rename($status->filename, PDFDYNAMIC.$filename);
							if(CPOD_TYPE == 'live') {
								// make a link to this product, to completely
								// defeat the purpose of SmartDox.
								// Wheeeeeee.
								// remove anything that is after the first _ or space
								$name = preg_replace('/([ _].*)/', '', $product->products_name);
								$name = preg_replace('/[\/\\\\\?\%\*\:\|"\<\>]+/', '_', $name);
								$linked_product = '/script_files/MarketingLinks/'.$name.'.pdf';
								if(file_exists($linked_product)) {
									unlink($linked_product);
								}
								link(PDFDYNAMIC.$filename, $linked_product);
							}
						}

						// check and store the page count
						if ($status->pageCount >= 1) {
							$this->db->update(TABLE_PRODUCTS,
									// SET
									array('pages' => $status->pageCount),
									// WHERE
									array('products_id' => $product->products_id));
						}

						// note that the product is completed
						$this->db->where('products_id', $product->products_id)
								->update(TABLE_PRODUCTS_DESCRIPTION, array('product_type' => 1));
					} else if($status->progress > -1) {
						$morePending = TRUE;
					}
					$progress = $status->progress;
					$statusMessage = $status->statusMessage;
					$pageCount = $status->pageCount;
				}

				// track updated products
				$new_products = $this->CI->session->userdata(KEY_CATALOG_NEW_PRODUCTS);
				$new_products[] = $product->products_id;
				$this->CI->session->set_userdata(KEY_CATALOG_NEW_PRODUCTS, $new_products);
			} else {
				// already complete
				$progress = 100;
				$statusMessage = 'Finished';
				$pageCount = $product->pages;
			}

			// check for required products added to generated materials
			if($product->required_product && (empty($product->base_pdfupload) || strpos($product->base_pdfupload, '.pdf') === FALSE)) {
				if($progress == 100) {
					if($product->required_being_added) {
						// truly completed the product
						$this->db->where('products_id', $product->products_id)
								->update(TABLE_PRODUCTS_DESCRIPTION, array('product_type' => 1));
						$this->db->where('products_id', $product->products_id)
								->update(TABLE_PRODUCTS, array('required_product' => 0));
						$statusMessage = 'Finished';
					} else {
						// handle adding the required product
						$job = $this->combineRequiredProduct($product->products_pdfupload, $product->required_product);
						// we're not really complete, need to add required products
						$this->db->where('products_id', $product->products_id)
								->update(TABLE_PRODUCTS_DESCRIPTION, array('product_type' => 2));
						$this->db->where('products_id', $product->products_id)
								->update(TABLE_PRODUCTS, array('required_product' => 1, 'products_pdfupload' => $job.'.pdf'));
						$progress = 99;
						$statusMessage = 'Adding required addendum materials...';
						$morePending = TRUE;
					}
				} else if($product->required_being_added) {
					// fake the progress to be *almost* complete
					$progress = 99;
					$statusMessage = 'Adding required addendum materials...';
					$morePending = TRUE;
				}
			}

			$in_progress[] = new PendingProductInfo($product, $progress, $statusMessage, $pageCount);
		}

		if(is_null($product_id)) {
			$this->CI->session->set_userdata(KEY_CATALOG_HAS_PENDING, $morePending);

			$ret->morePending = $morePending;
			$ret->inProgress = $in_progress;

			return $ret;
		} else {
			if(count($in_progress) == 0) {
				return FALSE;
			} else {
				return $in_progress[0];
			}
		}
	}

	//----------------------------------------------------------------------

	/**
	 * Method to add a combine a required product into a finished static or
	 * dynamic product.
	 * 
	 * @param string $pdfupload The static or dynamic PDF filename
	 * @param string $required_product_id The ID of the required product
	 * @return string The new job name for the combine process.
	 */
	private function combineRequiredProduct($pdfupload, $required_product_id) {
		// special case for combining two static products
		$required = $this->db->select('products_pdfupload')->from(TABLE_PRODUCTS)->where('products_id', $required_product_id)->get()->row()->products_pdfupload;

		$low_src = FALSE;
		foreach(array(PDFDYNAMIC, PDFSTATIC, DESIGNMERGE_CONVERT2PDF_OUT) as $dir) {
			if(file_exists($dir . $pdfupload)) {
				$low_src = $dir;
				break;
			}
		}
		if($low_src === FALSE) {
			show_error('Unable to find the low-res source for the file: '.$pdfupload);
		}

		// combine low-res
		$job = $this->designmerge->combinePDFs(
					$low_src . $pdfupload,
					PDFSTATIC . $required
				);

		// combine hi-res in the background (we don't monitor this)
		$this->designmerge->combinePDFsAndMove(
				array(
					PDFHIGHRES . str_replace('.pdf', '_HR.pdf', $pdfupload),
					PDFHIGHRES . str_replace('.pdf', '_HR.pdf', $required)
					),
				PDFHIGHRES . $job . '_HR.pdf'
				);

		return $job;
	}

}