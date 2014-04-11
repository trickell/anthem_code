<?php
/**
 * Library to help with managing states and sets.
 *
 * A set is usually one or two "states" grouped by the state name, minus
 * any -sm or -lg on the end.
 *
 * @author Phil DeJarnett
 * @author Jmadrigal
 */
class States {

	/** @var array $states List of visible states */
	private $states = NULL;

	/** @var User_State[] $selectedSet List of visible sets */
	private $selectedSet = NULL;

	/** @var User_State $selectedState Copy of current user's selected state */
	private $selectedState = NULL;

	/** @var Controller */
	private $CI;

	private $loaded = FALSE;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->CI =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Loads in the current state selections for the user.
	 * This method also handles processing changes to the user's
	 * currently selected state.
	 *
	 * @return void
	 */
	public function loadStates() {
		if($this->loaded) {
			// only load once
			return;
		}

		// if only one state exists, just select it now.
		if(count($this->CI->user->states) == 1) {
			$this->states = $this->CI->user->states;
			$this->selectedSet = $this->states;
			// select the state by default.
			$this->CI->user->current->state = reset($this->CI->user->states);
			$this->CI->user->current->account_type = $this->CI->user->current->state->account_type;
			$this->CI->user->current->state_set = $this->CI->user->current->state->set;
			$this->selectedState = $this->CI->user->current->state;
			// don't render anything
			return;
		}

		// build the list of states.
		$this->buildStatesList();

		// check for GET vars with selected states.
		$this->updateSelectedStateSet();
		$this->updateSelectedState();
		
		// finally, update the options on this object
		$this->updateDropdownOptions();

		$this->loaded = TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Builds the states array - which is grouped by state sets
	 */
	private function buildStatesList() {
		$this->states = array();
		foreach($this->CI->user->states as $state) {
			/* @var $state User_State */
			$set = $state->set;
			// either add to an existing group, or create a new one.
			if(isset($this->states[$set])) {
				$this->states[$set][$state->id] = $state;
			} else {
				$this->states[$set] = array($state->id => $state);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Method to set the selected state set.  Also clears the current state.
	 *
	 * If passed an invalid state set for this user, the state set is cleared.
	 *
	 * @param mixed $set Optional set name to override the $_GET param
	 */
	private function updateSelectedStateSet($set = FALSE) {
		if(count($this->states) == 1) {
			// select the first state
			reset($this->states);
			$this->CI->user->current->state_set = key($this->states);
		} else {
			// see if user has selected a state set
			if($set === FALSE) {
				$set = $this->CI->input->get('stateset');
			}
			if($set !== FALSE) {
				// only allow the user to select proper states.
				if(isset($this->states[$set])) {
					$this->CI->user->current->state_set = $set;
					if(count($this->states[$set]) == 1) {
						// select the specific state
						$this->CI->user->current->state = reset($this->states[$set]);
						$this->CI->user->current->account_type = $this->CI->user->current->state->account_type;
					} else {
						// reset the specific state
						$this->CI->user->current->state = NULL;
						$this->CI->user->current->account_type = NULL;
					}
				} else {
					$this->CI->user->current->state_set = NULL;
					// reset the specific state
					$this->CI->user->current->state = NULL;
					$this->CI->user->current->account_type = NULL;
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Method to set the selected state (also handles the state set).
	 *
	 * If passed an invalid state id for this user, the state is cleared.
	 *
	 * @param mixed $stateid Optional state id to override the $_GET param
	 */
	private function updateSelectedState($stateid = FALSE) {
		if($stateid === FALSE) {
			// see if the user has selected a state
			$stateid = $this->CI->input->get_post('stateid');
		}
		if($stateid !== FALSE) {
			if(isset($this->CI->user->states[$stateid])) {
				$this->CI->user->current->state = $this->CI->user->states[$stateid];
				$this->CI->user->current->account_type = $this->CI->user->current->state->account_type;
				$this->CI->user->current->state_set = $this->CI->user->states[$stateid]->set;
			} else {
				$this->CI->user->current->state = NULL;
				$this->CI->user->current->account_type = NULL;
				// don't reset state set, because it might be set on purpose and is probably still correct.
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Method to update the "selected" objects on this class
	 */
	private function updateDropdownOptions() {
		if(!empty($this->CI->user->current->state_set)) {
			$this->selectedSet = $this->states[$this->CI->user->current->state_set];
		} else {
			$this->selectedSet = NULL;
		}
		if(!empty($this->CI->user->current->state)) {
			$this->selectedState = $this->CI->user->current->state;
		} else {
			$this->selectedState = NULL;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set the selected state set for the current user.
	 *
	 * @param string $set Name of state set
	 */
	public function setStateSet($set) {
		if(!$this->loaded) {
			$this->buildStatesList();
		}
		$this->updateSelectedStateSet($set);
		$this->updateDropdownOptions();
	}

	// --------------------------------------------------------------------

	/**
	 * Set the selected state for the current user.
	 *
	 * @param mixed $stateid ID of state, or User_State object
	 */
	public function setState($stateid) {
		if(!$this->loaded) {
			$this->buildStatesList();
		}
		if(is_object($stateid)) {
			$stateid = $stateid->id;
		}
		$this->updateSelectedState($stateid);
		$this->updateDropdownOptions();
	}

	// --------------------------------------------------------------------

	/**
	 * Returns a 0, 1, or 2 item array featuring the drop-downs
	 * to show for this user.
	 * @return array
	 */
	public function getStatesAndSets() {
		$ret = array();

		if(count($this->states) > 1) {
			$s = array('' => '(Any State)');
			foreach($this->states as $set => $state) {
				$s[$set] = $set;
			}
			$ret['sets'] = $s;
		}

		if(!empty($this->selectedSet) && count($this->selectedSet) > 1) {
			$g = array('' => '(Any Group)');
			/* @var $state User_State */
			foreach($this->selectedSet as $id => $state) {
				$g[$id] = $state->group;
			}
			$ret['groups'] = $g;
		}

		return $ret;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns the selected state or set
	 *
	 * @return mixed NULL, The selected set, or the selected state
	 */
	public function getStateSelections() {
		$this->loadStates();

		$ret = NULL;

		if(!empty($this->selectedState)) {
			$ret = $this->selectedState;
		} else if(!empty($this->selectedSet)) {
			$ret = reset($this->selectedSet)->set;
		}

		return $ret;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns an array of currently selected state IDs, or all states
	 * available to the current user if nothing is selected.
	 *
	 * @return array
	 */
	public function getSelectedStateIds() {
		$this->loadStates();
		$ids = $this->CI->user->state_ids;
		if(!empty($this->selectedState)) {
			$ids = array($this->selectedState->id);
		} else if(!empty($this->selectedSet)) {
			$ids = array();
			foreach($this->selectedSet as $state) {
				$ids[] = $state->id;
			}
		}
		return array_map('intval', $ids);
	}

	// --------------------------------------------------------------------

	/**
	 * Returns the list of states allowed for the product.
	 *
	 * @param mixed $product_id
	 * @return array List of states and state IDs
	 */
	public function getStatesToChooseFrom($product = NULL) {
		$this->loadStates();
		$ret = array(
			'' => '(Select One)'
		);
		if(!empty($this->selectedSet) && count($this->selectedSet) > 1) {
			/* @var $state User_State */
			foreach($this->selectedSet as $state) {
				$ret[$state->id] = $state->group;
			}
		} else {
			foreach($this->states as $state_set => $states) {
				if(count($states) == 1) {
					$state = reset($states);
					$ret[$state->id] = $state->set;
				} else {
					foreach($states as $state) {
						$n = $state->set;
						if($state->group != '') {
							$n .= ' (' . $state->group . ')';
						}
						$ret[$state->id] = $n;
					}
				}
			}
		}

		if(!empty($product)) {
			$this->CI->load->library('products');
			$product = $this->CI->products->getProduct($product);
			if($product->for_state) {
				if(isset($ret[$product->for_state])) {
					$ret = array($product->for_state => $ret[$product->for_state]);
				} else {
					$name = $this->CI->db->select('states_name')->from(TABLE_STATES)->where('states_id', $product->for_state)->get()->row()->states_name;
					$ret = array($product->for_state => $name);
				}
			} else {
				// only allow states that are allowed for the requested product.
				$db_states = $this->CI->db
						->select('states_id')
						->from(TABLE_PRODUCTS_TO_STATES)
						->where('products_id', $product->products_id)
						->get()->result();
				$states = array();
				$general = FALSE;
				foreach($db_states as $state) {
					if($state->states_id == 1) {
						$general = TRUE;
						break;
					}
					$states[] = $state->states_id;
				}
				if(!$general) {
					$tmp = $ret;
					$ret = array();
					foreach($tmp as $k => $v) {
						if(in_array($k, $states)) {
							$ret[$k] = $v;
						}
					}
				}
			}
		}

		return $ret;
	}

}