<?php

/**
 * 
 *
 * @author PDeJarnett
 * @author Jmadrigal
 *
 * @param Controller $CI
 * @param CI_DB_active_record $db
 * @property CI_Template $template
 * @property CI_Input $input
 * @param Products $products
 * @param ProductCustomizer $productcustomizer
 * @param DesignMerge $designmerge
 * @param Formidable $formidable
 */
abstract class CustomProductTemplate {

	/** @var stdClass Custom information */
	public $custom = NULL;

	/** @var ProductResult The (base) product passed in. */
	public $product = NULL;

	/** @var ProductResult If the passed-in ID was not a base product, this represents that product. */
	public $generated_product = NULL;

	/** @var array */
	public $original_values = array();

	//----------------------------------------------------------------------

	public function __construct($custom) {
		$this->custom = $custom;
		$this->CI =& get_instance();
		$this->db = $this->CI->db;
		foreach(array('template', 'products', 'productcustomizer', 'designmerge', 'formidable', 'input') as $lib) {
			$this->CI->load->library($lib);
			$this->{$lib} = $this->CI->{$lib};
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
	public function processRequest($productId, $command = '') {
		
		$this->lookupProduct($productId);

		if(!empty($command)) {
			// prevent from loading methods that are part of CustomProductTemplate
			if(method_exists("CustomProductTemplate", $command)) {
				show_404();
			}

			if(method_exists(get_class($this), $command) && is_callable(array($this, $command))) {
				$args = func_get_args();
				array_shift($args);
				array_shift($args);
				call_user_func_array(array($this, $command), $args);
				return;
			} else {
				show_404();
			}
		}

		$this->index();
	}

	//----------------------------------------------------------------------

	public function index() {
		$this->template->set_template('fancybox');
		$this->template->write('title', 'Customize "'.$this->product->products_description.'"');

		$this->configureForm();
		$this->configureFormButtons();
		if($this->formidable->shouldSave()) {
			$this->generateProduct();
			$this->CI->include_js->embed('$(closeFancybox);');
			$this->template->write('content', 'Your product is being generated.');
		} else {
			$this->renderForm();
		}

		$this->template->render();
	}

	//----------------------------------------------------------------------

	/**
	 * Handles looking up the product's information, including loading
	 * the original values for a given product (if available) and looking up
	 * the base product (if it wasn't passed in).
	 * 
	 * @param mixed $productId
	 */
	public function lookupProduct($productId) {
		$this->product = $this->products->getProduct($productId);

		if(!empty($this->product->base_products_id)) {
			// get the values on this product
			$row = $this->db
								->select('customized_values')
								->from(TABLE_PRODUCTS)
								->where('products_id', $this->product->products_id)
								->get()->row();
			if($row && !empty($row->customized_values)) {
				$values = json_decode($row->customized_values);
				if(is_object($values)) {
					$this->original_values = $values;
				}
			}

			// now look up the base product
			$this->generated_product = $this->product;
			$this->product = $this->products->getProduct($this->product->base_products_id);
		}
	}

	//----------------------------------------------------------------------

	/**
	 *
	 */
	abstract public function configureForm();

	//----------------------------------------------------------------------

	/**
	 * Configures the form buttons.  By default this adds Cancel and
	 * Generate Report buttons, in a wizard format.
	 */
	public function configureFormButtons() {
		$this->formidable->addWizardCancelButton('cancel', 'Cancel', 'button', array('onclick' => 'closeFancybox();'));
		$this->formidable->addWizardNextButton('generateProduct', 'Create');
	}

	//----------------------------------------------------------------------

	/**
	 * Renders the form.  Also automatically adds custom.js and custom.css,
	 * if they exist for this product.
	 */
	public function renderForm() {
		if(file_exists(PRODUCT_CUSTOMIZERS_ROOT . $this->custom->name)) {
			// look for JS to auto include
			if(file_exists(PRODUCT_CUSTOMIZERS_ROOT . $this->custom->name . '/custom.js')) {
				$js = file_get_contents(PRODUCT_CUSTOMIZERS_ROOT . $this->custom->name . '/custom.js');
				$this->template->add_js($js, 'embed');
			}
			if(file_exists(PRODUCT_CUSTOMIZERS_ROOT . $this->custom->name . '/custom.css')) {
				$this->template->add_css(current_url().'/files/custom.css');
			}
		}
		$this->template->write('content', $this->formidable->render());
	}

	//----------------------------------------------------------------------

	/**
	 * 
	 * @return mixed Result of the customizer start.
	 */
	public function generateProduct() {
		$this->productcustomizer->init($this->product);
		$this->setValues();
		return $this->productcustomizer->start($this->formidable->values);
	}

	//----------------------------------------------------------------------

	/**
	 *
	 */
	public function setValues() {
		$this->productcustomizer->map($this->formidable->values);
	}

	//----------------------------------------------------------------------

	/**
	 * Specialized view loader that allows views to be loaded from within
	 * the customizer's folder.
	 *
	 * NOTE: this method *always* returns the the view.
	 *
	 * @param string $view
	 * @param mixed $vars
	 * @return string
	 */
	public function load_view($view, $vars = array()) {
		if(file_exists(PRODUCT_CUSTOMIZERS_ROOT . $this->custom->name . '/' . $view . EXT)) {
			$view = '../../'.PRODUCT_CUSTOMIZERS_ROOT . $this->custom->name . '/' . $view;
		}
		return $this->CI->load->view($view, $vars, TRUE);
	}

}