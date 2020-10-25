<?php

defined('_JEXEC') or die('Restricted access');

class plgVmShipmentPickup_PostaInstallerScript {

	public function install(JAdapterInstance $adapter) {
		// enabling plugin
		$db =& JFactory::getDBO();
		$db->setQuery('update #__extensions set enabled = 1 where type = "plugin" and element = "pickme_shipping" and folder = "vmshipment"');
		$db->query();

		return true;
	}

	public function uninstall(JAdapterInstance $adapter) {
		// Remove plugin table
		// $db =& JFactory::getDBO();
		// $db->setQuery('DROP TABLE `#__virtuemart_shipment_plg_rules_shipping`;');
		// $db->query();
	}

	public function postflight($route, JAdapterInstance $adapter) {
		if ($route=='install' || $route=='update') {			
			// create pickme shop table
			$db =& JFactory::getDBO();
			$db->setQuery('CREATE TABLE IF NOT EXISTS `'.$db->getPrefix().'virtuemart_posta` (
					`pp_id` int(10) unsigned NOT NULL,
					`pp_group` varchar(30) NULL, 
					`pp_lat` varchar(20) NULL,
					`pp_lon` varchar(20) NULL,
					`pp_name` varchar(500) NULL,
					`pp_zip` varchar(20) NULL,
					`pp_kzip` varchar(20) NULL,
					`pp_county` varchar(150) NULL,
					`pp_address` varchar(500) NULL,
					`pp_phone` varchar(100) NULL,
					`pp_foreignid` varchar(20) NULL,
					`pp_status` varchar(10) NULL,
					`pp_transaction` varchar(10) NULL,
					`pp_distance` varchar(20) NULL,
					`pp_newpoint` varchar(20) NULL,
					`pp_newpointtill` varchar(20) NULL,
					`pp_updatetime` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					PRIMARY KEY  (`pp_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
			$db->query();
				
			// copy logo
//			$logo_file = 'chronopost_pickup.jpg';
//			$src = JPATH_ROOT.DS.'plugins'.DS.'vmshipment'.DS.'pickme_shipping'.DS.$logo_file;
//			$dest_dir = JPATH_ROOT.DS.'images'.DS.'stories'.DS.'virtuemart'.DS.'shipment';
//			if (!JFolder::exists($dest_dir)) {
//				JFolder::create($dest_dir);
//			}
//			JFile::copy($src, $dest_dir.DS.$logo_file);
		}
	}
}
