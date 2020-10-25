<?php

defined ('_JEXEC') or die('Restricted access');

/**
 * Shipment plugin for general, rules-based shipments, like regular postal services with complex shipping cost structures
 *
 * @version 1.0
 * @package VirtueMart
 * @subpackage Plugins - shipment
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.org
 * @author Tibor Drajkó, based on the weight_countries shipping plugin
 * mail: drajko.tibor@gmail.com
 *
*/
if (!class_exists ('vmPSPlugin')) {
	require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}

if (!class_exists ('plgVmShipmentPickup_Posta')) {
	// Only declare the class once...

	/** Shipping costs according to general rules.
	 *  Supported Variables: Weight, ZIP, Amount
	 *  Assignable variables: Shipping, Name
	 */
	class plgVmShipmentPickup_Posta extends vmPSPlugin {

		/**
		 * @param object $subject
		 * @param array  $config
		 */
  	public $selectedPosta = null;
  	public $_fieldname = null;
			
		function __construct (& $subject, $config) {

			parent::__construct ($subject, $config);

      $this->editform = FALSE;
      $this->updateDatabase();

  		$this->_loggable = TRUE;
  		$this->_tablepkey = 'id';
  		$this->_tableId = 'id';
  		$idname = $this->_idName;
  		
  		$this->tableFields = array_keys ($this->getTableSQLFields ());
  		$varsToPush = $this->getVarsToPush ();
 			$this->setConfigParameterable ($this->_configTableFieldName, $varsToPush);
  		//vmdebug('Muh constructed plgVmShipmentWeight_countries',$varsToPush);
		}
		
    public function get_fieldname($cart) {
//			JFactory::getApplication()->enqueueMessage('get_fieldname', 'message');
			$idName = $this->_idName;
      $this->_fieldname = $this->_psType . '_posta_' . $cart->$idName;

      return $this->_fieldname;
    }
    		
		
		/**
		 * Create the table for this plugin if it does not yet exist.
		 *
		 * @author Drajkó Tibor
		 */
		public function getVmPluginCreateTableSQL () {
//			JFactory::getApplication()->enqueueMessage('getVmPluginCreateTableSQL', 'message');
			return $this->createTableSQL ('Shipment PickUp Posta Table');
		}

		/**
		 * @return array
		 */
		function getTableSQLFields () {
//			JFactory::getApplication()->enqueueMessage('getTableSQLFields', 'message');

  		$SQLfields = array(
  			'id'                           => 'int(1) UNSIGNED NOT NULL AUTO_INCREMENT',
  			'virtuemart_order_id'          => 'int(11) UNSIGNED',
  			'order_number'                 => 'char(32)',
  			'virtuemart_shipmentmethod_id' => 'mediumint(1) UNSIGNED',
  			'shipment_name'                => 'varchar(5000)',
  			'order_weight'                 => 'decimal(10,4)',
  			'shipment_weight_unit'         => 'char(3) DEFAULT \'KG\'',
  			'shipment_cost'                => 'decimal(10,2)',
  			'shipment_package_fee'         => 'decimal(10,2)',
  			'tax_id'                       => 'smallint(1)',
				'posta_id'                     => 'int(10) UNSIGNED',
				'posta_group'                  => 'varchar(30) NULL', 
				'posta_lat'                    => 'varchar(20) NULL',
				'posta_lon'                    => 'varchar(20) NULL',
				'posta_name'                   => 'varchar(500) NULL',
				'posta_zip'                    => 'varchar(20) NULL',
				'posta_kzip'                   => 'varchar(20) NULL',
				'posta_county'                 => 'varchar(150) NULL',
				'posta_address'                => 'varchar(500) NULL',
				'posta_phone'                  => 'varchar(100) NULL'	
			);
			return $SQLfields;
		}

		/**
		 * This method is fired when showing the order details in the frontend.
		 * It displays the shipment-specific data.
		 *
		 * @param integer $virtuemart_order_id The order ID
		 * @param integer $virtuemart_shipmentmethod_id The selected shipment method id
		 * @param string  $shipment_name Shipment Name
		 * @return mixed Null for shipments that aren't active, text (HTML) otherwise
		 * @author Drajkó Tibor
		 * @author Max Milbers
		 */
		public function plgVmOnShowOrderFEShipment ($virtuemart_order_id, $virtuemart_shipmentmethod_id, &$shipment_name) {
//			JFactory::getApplication()->enqueueMessage('plgVmOnShowOrderFEShipment', 'message');
			$this->onShowOrderFE ($virtuemart_order_id, $virtuemart_shipmentmethod_id, $shipment_name);
		}

		/**
		 * This event is fired after the order has been stored; it gets the shipment method-
		 * specific data.
		 *
		 * @param int    $order_id The order_id being processed
		 * @param object $cart  the cart
		 * @param array  $order The actual order saved in the DB
		 * @return mixed Null when this method was not selected, otherwise true
		 * @author Drajkó Tibor
		 */
		function plgVmConfirmedOrder (VirtueMartCart $cart, $order) {
//			JFactory::getApplication()->enqueueMessage('plgVmConfirmedOrder', 'message');

			if (!($method = $this->getVmPluginMethod ($order['details']['BT']->virtuemart_shipmentmethod_id))) {
				return NULL; // Another method was selected, do nothing
			}
			if (!$this->selectedThisElement ($method->shipment_element)) {
				return FALSE;
			}
			$values['virtuemart_order_id'] = $order['details']['BT']->virtuemart_order_id;
			$values['order_number'] = $order['details']['BT']->order_number;
			$values['virtuemart_shipmentmethod_id'] = $order['details']['BT']->virtuemart_shipmentmethod_id;
			$values['shipment_name'] = $this->renderPluginName ($method);

  	  $selectedPosta = $this->getSelectedPostaId();
			$values['posta_id']       = $selectedPosta->id;
			$values['posta_group']    = $selectedPosta->group;
			$values['posta_lat']      = $selectedPosta->lat;
			$values['posta_lon']      = $selectedPosta->lon;
			$values['posta_name']     = $selectedPosta->name;
			$values['posta_zip']      = $selectedPosta->zip;
			$values['posta_kzip']     = $selectedPosta->kzip;
			$values['posta_county']   = $selectedPosta->county;
			$values['posta_address']  = $selectedPosta->address;
			$values['posta_phone']    = $selectedPosta->phone;  			
			
			$values['order_weight'] = $this->getOrderWeight ($cart, $method->weight_unit);
  		$values['shipment_weight_unit'] = $method->weight_unit;
  		$costs = $this->getCosts($cart,$method,$cart->cartPrices);
  		if(!empty($costs)){
  			$values['shipment_cost'] = $method->shipment_cost;
  			$values['shipment_package_fee'] = $method->package_fee;
  		}
  		if(empty($values['shipment_cost'])) $values['shipment_cost'] = 0.0;
  		if(empty($values['shipment_package_fee'])) $values['shipment_package_fee'] = 0.0;
   		$values['tax_id'] = $method->tax_id;

  		$this->storePSPluginInternalData ($values);

			return TRUE;
		}

		/**
		 * This method is fired when showing the order details in the backend.
		 * It displays the shipment-specific data.
		 * NOTE, this plugin should NOT be used to display form fields, since it's called outside
		 * a form! Use plgVmOnUpdateOrderBE() instead!
		 *
		 * @param integer $virtuemart_order_id The order ID
		 * @param integer $virtuemart_shipmentmethod_id The order shipment method ID
		 * @param object  $_shipInfo Object with the properties 'shipment' and 'name'
		 * @return mixed Null for shipments that aren't active, text (HTML) otherwise
		 * @author Drajkó Tibor
		 */
		public function plgVmOnShowOrderBEShipment ($virtuemart_order_id, $virtuemart_shipmentmethod_id) {
//			JFactory::getApplication()->enqueueMessage('plgVmOnShowOrderBEShipment', 'message');

			if (!($this->selectedThisByMethodId ($virtuemart_shipmentmethod_id))) {
				return NULL;
			}
			$html = $this->getOrderShipmentHtml ($virtuemart_order_id);
			return $html;
		}

		/**
		 * @param $virtuemart_order_id
		 * @return string
		 */
		function getOrderShipmentHtml ($virtuemart_order_id) {
//			JFactory::getApplication()->enqueueMessage('getOrderShipmentHtml', 'message');

			$db = JFactory::getDBO ();
			$q = 'SELECT * FROM `' . $this->_tablename . '` '
					. 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
			$db->setQuery ($q);
			if (!($shipinfo = $db->loadObject ())) {
				vmWarn (500, $q . " " . $db->getErrorMsg ());
				return '';
			}

			if (!class_exists ('CurrencyDisplay')) {
				require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');
			}

			$currency = CurrencyDisplay::getInstance ();
			$tax = ShopFunctions::getTaxByID ($shipinfo->tax_id);
			$taxDisplay = is_array ($tax) ? $tax['calc_value'] . ' ' . $tax['calc_value_mathop'] : $shipinfo->tax_id;
			$taxDisplay = ($taxDisplay == -1) ? JText::_ ('COM_VIRTUEMART_PRODUCT_TAX_NONE') : $taxDisplay;

			$html = '<table class="adminlist">' . "\n";
			$html .= $this->getHtmlHeaderBE ();
			$html .= $this->getHtmlRowBE('WEIGHT_COUNTRIES_SHIPPING_NAME', $shipinfo->shipment_name);
			$html .= $this->getHtmlRowBE('WEIGHT_COUNTRIES_WEIGHT', $shipinfo->order_weight . ' ' . ShopFunctions::renderWeightUnit ($shipinfo->shipment_weight_unit));
			$html .= $this->getHtmlRowBE('WEIGHT_COUNTRIES_COST', $currency->priceDisplay ($shipinfo->shipment_cost));
		  $html .= $this->getHtmlRowBE ('WEIGHT_COUNTRIES_PACKAGE_FEE', $currency->priceDisplay ($shipinfo->shipment_package_fee));
			$html .= $this->getHtmlRowBE('WEIGHT_COUNTRIES_TAX', $taxDisplay);
			$html .= '</table>' . "\n";

			return $html;
		}

		protected function renderPluginName($plugin) {
//			JFactory::getApplication()->enqueueMessage('renderPluginName', 'message');
			$return = '';
			$plugin_name = $this->_psType . '_name';
			$plugin_desc = $this->_psType . '_desc';
			$pluginmethod_id = $this->_idName;
			$description = '';
			$logosFieldName = $this->_psType . '_logos';
			$logos = $plugin->$logosFieldName;
			if (!empty($logos)) {
				$return = $this->displayLogos ($logos) . ' ';
			}
			if (!empty($plugin->$plugin_desc)) {
				$description = '<span class="' . $this->_type . '_description">' . $plugin->$plugin_desc . '</span>';
			}
			
      if($this->editform) {
  	    $selectedPosta = $this->getSelectedPostaId();
  			$db = JFactory::getDbo();
    		$query = $db->getQuery(true);
    		$query
    		  ->select('*')
    		  ->from($db->quoteName('#__virtuemart_posta'));
    		if($plugin->pickup_posta_filter){
    		  $query->where(sprintf('%s in (%s)', $db->quoteName('pp_group'), "'" . implode("','", $plugin->pickup_posta_filter) . "'"));
    		};
    		$query->order('pp_county, pp_kzip, pp_address');
  //	    JFactory::getApplication()->enqueueMessage($query->__toString());
        $db->setQuery($query);		
  			$results = $db->loadAssocList();
  

  			$list = '<select id="posta_id" class="pickup_stores_select" name="' . $this->_psType . '_posta_' . $plugin->$pluginmethod_id . '">';
  			foreach ($results as $row) {
  			  $selected = ($selectedPosta->id == $row['pp_id']?' selected':'');
  				$list .= '<option value="' . $row['pp_id'] . '"' . $selected . '>';
  				$list .= $row['pp_county'] . ', (' . $row['pp_kzip']. ') ' . $row['pp_address'] . ' - ' . $row['pp_name'] . '</option>';
  			}
  			$list .= '</select>';
  			
			  $pluginName = $return . '<span class="' . $this->_type . '_name">' . $plugin->$plugin_name.'</span>' ;
			  $pluginName .= $description;
			  $pluginName .=  '</br><span class="pickup_stores">' . $list . '</span>';
			} else {
			  $pluginName = $return . '<span class="' . $this->_type . '_name">' . $plugin->$plugin_name;
			  $pluginName .= $description . '</span>';
			}
				
			return $pluginName;
		}


		/**
		 * @param VirtueMartCart $cart
		 * @param                $method
		 * @param                $cart_prices
		 * @return int
		 */
		function getCosts (VirtueMartCart $cart, $method, $cart_prices) {
//			JFactory::getApplication()->enqueueMessage('getCosts', 'message');

  		if ($method->free_shipment && $cart_prices['salesPrice'] >= $method->free_shipment) {
  			return 0.0;
  		} else {
  			if(empty($method->shipment_cost)) $method->shipment_cost = 0.0;
  			if(empty($method->package_fee)) $method->package_fee = 0.0;
  			return $method->shipment_cost + $method->package_fee;
  		}
/*			if (!empty($method->pickme_overcost) && is_numeric($method->pickme_overcost)) {
				return $method->pickme_overcost;
			}

			vmdebug('getCosts '.$method->name.' does not return shipping costs');
			return 0;
*/		}




		/**
		 * @param \VirtueMartCart $cart
		 * @param int             $method
		 * @param array           $cart_prices
		 * @return bool
		 */
		protected function checkConditions ($cart, $method, $cart_prices) {
//			JFactory::getApplication()->enqueueMessage('checkConditions', 'message');
				
			return true;
		}
		
  	/**
  	 * @param $cart
  	 * @param $method
  	 * @return bool
  	 */
  	private function _nbproductsCond ($cart, $method) {
//  			JFactory::getApplication()->enqueueMessage('_nbproductsCond', 'message');
  
  		if (empty($method->nbproducts_start) and empty($method->nbproducts_stop)) {
  			//vmdebug('_nbproductsCond',$method);
  			return true;
  		}
  
  		$nbproducts = 0;
  		foreach ($cart->products as $product) {
  			$nbproducts += $product->quantity;
  		}
  
  		if ($nbproducts) {
  
  			$nbproducts_cond = $this->testRange($nbproducts,$method,'nbproducts_start','nbproducts_stop','products quantity');
  
  		} else {
  			$nbproducts_cond = false;
  		}
  
  		return $nbproducts_cond;
  	}

		/**
		 * Create the table for this plugin if it does not yet exist.
		 * This functions checks if the called plugin is active one.
		 * When yes it is calling the standard method to create the tables
		 *
		 * @author Drajkó Tibor
		 *
		 */
		function plgVmOnStoreInstallShipmentPluginTable ($jplugin_id) {
//			JFactory::getApplication()->enqueueMessage('plgVmOnStoreInstallShipmentPluginTable', 'message');

			return $this->onStoreInstallPluginTable($jplugin_id);
		}

		/**
		 * @param VirtueMartCart $cart
		 * @return null
		 */
		public function plgVmOnSelectCheckShipment (VirtueMartCart &$cart) {
//			JFactory::getApplication()->enqueueMessage('plgVmOnSelectCheckShipment', 'message');
			$selected = $this->OnSelectCheck($cart);
			if($selected) {
			  $this->get_fieldname($cart);
			  $posta = JFactory::getApplication()->input->get($this->_fieldname, '0', 'string');
			  $this->setSelectedPostaId($posta, $cart, true);
      }

			return $selected; 
			
		}

		/**
		 * plgVmDisplayListFE
		 * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for example
		 *
		 * @param object  $cart Cart object
		 * @param integer $selected ID of the method selected
		 * @return boolean True on success, false on failures, null when this plugin was not selected.
		 * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
		 *
		 * @author Drajkó Tibor
		 * @author Max Milbers
		 */
		public function plgVmDisplayListFEShipment (VirtueMartCart $cart, $selected = 0, &$htmlIn) {
//			JFactory::getApplication()->enqueueMessage('plgVmDisplayListFEShipment', 'message');
      $this->editform = TRUE;
      try {
			  return $this->displayListFE ($cart, $selected, $htmlIn);
			} finally {
        $this->editform = FALSE;
      }
		}

		/**
		 * @param VirtueMartCart $cart
		 * @param array          $cart_prices
		 * @param                $cart_prices_name
		 * @return bool|null
		 */
		// JÓ
		public function plgVmOnSelectedCalculatePriceShipment (VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
//			JFactory::getApplication()->enqueueMessage('plgVmOnSelectedCalculatePriceShipment', 'message');
			return $this->onSelectedCalculatePrice ($cart, $cart_prices, $cart_prices_name);
		}

		/**
		 * plgVmOnCheckAutomaticSelected
		 * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
		 * The plugin must check first if it is the correct type
		 *
		 * @author Drajkó Tibor
		 * @param VirtueMartCart cart: the cart object
		 * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
		 *
		 */
		function plgVmOnCheckAutomaticSelectedShipment (VirtueMartCart $cart, array $cart_prices = array(), &$shipCounter) {
//			JFactory::getApplication()->enqueueMessage('plgVmOnCheckAutomaticSelectedShipment', 'message');
			$selected = $this->OnSelectCheck($cart);
			if($selected) {
			  $this->get_fieldname($cart);
			  $posta = JFactory::getApplication()->input->get($this->_fieldname);
			  if(!empty($posta)) {
			    $this->setSelectedPostaId($posta, $cart, true);
			  } else {
			    $posta = $this->getSelectedPostaId();
			    if(!empty($posta)) {
			      $this->setSelectedPostaId($posta->id, $cart);
			    }
			  }
        $this->hideOtherShipments(); 
      }
			if ($shipCounter > 1) {
				return 0;
			}
			return $this->onCheckAutomaticSelected ($cart, $cart_prices, $shipCounter);
		}

		/**
		 * This method is fired when showing when priting an Order
		 * It displays the the payment method-specific data.
		 *
		 * @param integer $_virtuemart_order_id The order ID
		 * @param integer $method_id  method used for this order
		 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
		 * @author Drajkó Tibor
		 */
    	function plgVmonShowOrderPrint ($order_number, $method_id) {
// 			  JFactory::getApplication()->enqueueMessage('plgVmonShowOrderPrint', 'message');
   	  	return $this->onShowOrderPrint ($order_number, $method_id);
    	}
    
    	function plgVmDeclarePluginParamsShipment ($name, $id, &$dataOld) {
// 			  JFactory::getApplication()->enqueueMessage('plgVmDeclarePluginParamsShipment', 'message');
    		return $this->declarePluginParams ('shipment', $name, $id, $dataOld);
    	}
    
    	function plgVmDeclarePluginParamsShipmentVM3 (&$data) {
// 			  JFactory::getApplication()->enqueueMessage('plgVmDeclarePluginParamsShipmentVM3', 'message');
 			  $this->updateDatabase();
    		return $this->declarePluginParams ('shipment', $data);
    	}
	
		/**
		 * @author Max Milbers
		 * @param $data
		 * @param $table
		 * @return bool
		 */
		function plgVmSetOnTablePluginShipment(&$data,&$table){
// 			  JFactory::getApplication()->enqueueMessage('plgVmSetOnTablePluginShipment', 'message');

			$name = $data['shipment_element'];
			$id = $data['shipment_jplugin_id'];

			if (!empty($this->_psType) and !$this->selectedThis ($this->_psType, $name, $id)) {
				return false;
			} else {

				return $this->setOnTablePluginParams ($name, $id, $table);
			}
		}
		
		function saveST($cart, $redirect = false) {
//    	JFactory::getApplication()->enqueueMessage('saveST', 'message');
	  
  		//If the user is logged in and exists, we check if he has already addresses stored
  		$rec = $this->getSelectedPostaId();
  		if(!empty($cart->user->virtuemart_user_id)){
			  $address = $cart->getST();
			  $address['virtuemart_userinfo_id'] = -1;
        $address['company']   = $rec->name;
        $address['city']      = $rec->county;
        $address['zip']       = $rec->kzip;
        $address['address_1'] = $rec->address;
        $address['address_2'] = '';
        $address['phone_1']   = $rec->phone;
   	    $cart->saveAddressInCart($address, 'ST');
   	    $cart->selected_shipto = -1;
      } else {
     		$address = $cart->getST();
        $address['company']   = $rec->name;
        $address['city']      = $rec->county;
        $address['zip']       = $rec->kzip;
        $address['address_1'] = $rec->address;
        $address['address_2'] = '';
        $address['phone_1']   = $rec->phone;
     	  $cart->saveAddressInCart($address, 'ST');
     	}
      $cart->setCartIntoSession();
      if($redirect) {
//    			JFactory::getApplication()->enqueueMessage('REDIRECTED', 'message');
          $cart->setCartIntoSession();
  				$app = JFactory::getApplication();
  				$app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart',$cart->useXHTML,$cart->useSSL), $msg);
  		}
		}
		
		function setSelectedPostaId($id, &$cart, $redirect = false) {
			$db = JFactory::getDbo();
  		$query = $db->getQuery(true);
  		$query
  		  ->select('*')
  		  ->from($db->quoteName('#__virtuemart_posta'))
  		  ->where($db->quoteName('pp_id') . ' = ' . $id);
      $db->setQuery($query);		
			$rec = $db->loadAssoc();
			
  		$session = JFactory::getSession();
  		$sessionData = new stdClass();
			if($rec) {
	  		$sessionData->id      = $rec['pp_id'];
        $sessionData->group   = $rec['pp_group'];
        $sessionData->lat     = $rec['pp_lat'];
        $sessionData->lon     = $rec['pp_lon'];
        $sessionData->name    = $rec['pp_name'];
        $sessionData->zip     = $rec['pp_zip'];
        $sessionData->kzip    = $rec['pp_kzip'];
        $sessionData->county  = $rec['pp_county'];
        $sessionData->address = $rec['pp_address'];
        $sessionData->phone   = $rec['pp_phone'];        
      }                              
  		$session->set($this->_psType . '_posta', json_encode($sessionData), 'vm');
      $this->saveST($cart, $redirect); 		
    }

		function getSelectedPostaId() {
  		$session = JFactory::getSession();
      $sessionData = $session->get($this->_psType . '_posta', 0, 'vm');
      if($sessionData){
        $value = (object)json_decode($sessionData,true);
      } else {
        $value = new stdClass();
      }     
                                    
  		return $value;
    }
    
		function needUpdate() {
		  $hour = $this->params->get('pickup_posta_update_hour');
		  if(empty($hour)) {
		    $hour = 0;
		  }
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $queryall = $db->getQuery(true);
      $queryall
        ->select('COUNT(*) AS amount')
        ->from($db->quoteName('#__virtuemart_posta'));    
      $query
        ->select('COUNT(*) AS amount')
        ->from($db->quoteName('#__virtuemart_posta'))
        ->where(sprintf('%s < DATE_SUB(NOW(), INTERVAL %d HOUR)', $db->quoteName('pp_updatetime'), $hour))
        ->unionall($queryall);     
      $db->setQuery($query);
      $obj = $db->loadObjectList();
      return !$obj || ($obj[0]->amount > 0) || ($obj[0]->amount + $obj[1]->amount == 0);
		}
		
		protected function callAPI($method, $url, $data){
      $curl = curl_init();
      switch ($method){
        case "POST":
          curl_setopt($curl, CURLOPT_POST, 1);
          if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
          break;
        case "PUT":
          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
          if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
          break;
        default:
          if ($data)
            $url = sprintf("%s?%s", $url, http_build_query($data));
      }
      // OPTIONS:
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array(
//         'APIKEY: 111111111111111111111',
         'Content-Type: application/json'
      ));
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      // EXECUTE:
      $result = curl_exec($curl);
      curl_close($curl);
      return $result;
    }
    
    protected function updateDatabase() {
      if($this->needUpdate()) {
        try {
          $url = $this->params->get('pickup_posta_api');
    			$data = array( group => '', skipgroups => '', callback => '' );
    			$result = $this->callAPI('GET', $url, $data);
  
          if(!empty($result)) {
            try {
              $result = json_decode(substr($result,1,-1));      
              $db = JFactory::getDbo();
              $query = $db->getQuery(true);
              $columns = array('pp_id', 'pp_group', 'pp_lat', 'pp_lon', 'pp_name', 'pp_zip', 'pp_kzip',
                               'pp_county', 'pp_address', 'pp_phone', 'pp_foreignid', 'pp_status',
                               'pp_transaction', 'pp_distance', 'pp_newpoint', 'pp_newpointtill');
      
              $sql = 'TRUNCATE TABLE `#__virtuemart_posta`';
              $db->setQuery($sql);
              $db->execute(); 
              foreach($result as $msg) {
                echo implode($msg); 
                $values = array($msg->id, $msg->group, $msg->lat, $msg->lon, $msg->name, $msg->zip, $msg->kzip,
                               $msg->county, $msg->address, $msg->phone, $msg->foreignid, $msg->status,
                               $msg->transaction, $msg->distance, $msg->newpoint, $msg->newpointtill);
                $query = $db->getQuery(true);
                $query
                    ->insert($db->quoteName('#__virtuemart_posta'))
                    ->columns($db->quoteName($columns))
                    ->values(implode(',', $db->quote($values)));
//    				    JFactory::getApplication()->enqueueMessage($query->__toString());
                $db->setQuery($query);
                $db->execute(); 
              } 
      			} catch (Exception $e) {
      				JFactory::getApplication()->enqueueMessage(JText::sprintf('Some error occurred: %s', $e->getMessage()), 'error');
      			} 
          }			
  			} catch (Exception $e) {
  				JFactory::getApplication()->enqueueMessage(JText::sprintf('Some error occurred: %s', $e->getMessage()), 'error');
  			} 
  		}  
    }
		
  	protected function hideOtherShipments() {
// 			  JFactory::getApplication()->enqueueMessage('hideOtherShipments', 'message');
      $document = JFactory::getDocument();
      // style hidding
      $jq .= '.output-shipto .controls { display: none; }';
      $jq .= '.cart-shipto .details { display: none; }';
      $document->addStyleDeclaration($jq); 

/* 	
   	  $selectedPosta = $this->getSelectedPostaId();
      $value = '<span class=\"values\">%s</span><br class=\"clrear\">';
      $innerHtml = sprintf($value, $selectedPosta->name);
      $innerHtml .= sprintf($value, $selectedPosta->kzip);
      $innerHtml .= sprintf($value, $selectedPosta->county);
      $innerHtml .= sprintf($value, $selectedPosta->address);
      $innerHtml .= sprintf($value, $selectedPosta->phone);

      $jq .= '  window.onload = function(){ ';
//      $jq .= '    document.getElementById("output-shipto-display").style.visibility = "visible";';
      $jq .= '    var shipto = document.getElementsByClassName("cart-shipto");';
      $jq .= '    if(shipto.length > 0) {';
      $jq .= '      var address = shipto[0].getElementsByClassName("output-shipto");';
      $jq .= '    	if(address.length > 0) { address[0].innerHTML = "' . $innerHtml . '"; }';
      $jq .= '    	var details = shipto[0].getElementsByClassName("details");';
      $jq .= '    	if(details.length > 0) { details[0].remove();	}';
      $jq .= '    }';
      $jq .= '  } </script>';
      $document->addScriptDeclaration($jq);  	
*/
    }
/*
    function getElementsByClass(&$parentNode, $tagName, $className) {
      $nodes=array();
  
      $childNodeList = $parentNode->getElementsByTagName($tagName);
      for ($i = 0; $i < $childNodeList->length; $i++) {
          $temp = $childNodeList->item($i);
          if (stripos($temp->getAttribute('class'), $className) !== false) {
              $nodes[]=$temp;
          }
      }
  
      return $nodes;
    }

    function onAfterRender(){
    	$app = JFactory::getApplication();
    	if ($app ->isAdmin()) return;
    	$option = $app->input->get('option');
    	$view =  $app->input->get('view');
    	$body   = JResponse::GetBody();
    	if($app->isSite()){
    	JFactory::getApplication()->enqueueMessage('Message: RENDER');
    	if($option  ==  'com_virtuemart' && $view == 'cart'){
  				$body = str_replace('class="vmCartShipmentLogo"','class="vmCartShipmentLogo" style="display:none;">', $body);
          $html = new DOMDocument();
          $xml->loadHTML($body);
          $body = $doc->saveHTML();
    			JResponse::setBody($body);
    			return true;
    		}	
    	}	
    }
*/	}
}
