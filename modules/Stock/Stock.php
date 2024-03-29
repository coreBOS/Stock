<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'data/CRMEntity.php';
require_once 'data/Tracker.php';

class Stock extends CRMEntity {
	public $table_name = 'vtiger_stock';
	public $table_index= 'stockid';
	public $column_fields = array();

	/** Indicator if this is a custom module or standard module */
	public $IsCustomModule = true;
	public $HasDirectImageField = false;
	public $moduleIcon = array('library' => 'standard', 'containerClass' => 'slds-icon_container slds-icon-standard-account', 'class' => 'slds-icon', 'icon'=>'planogram');

	/**
	 * Mandatory table for supporting custom fields.
	 */
	public $customFieldTable = array('vtiger_stockcf', 'stockid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	public $tab_name = array('vtiger_crmentity', 'vtiger_stock', 'vtiger_stockcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	public $tab_name_index = array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_stock'   => 'stockid',
		'vtiger_stockcf' => 'stockid',
	);

	/**
	 * Mandatory for Listing (Related listview)
	 */
	public $list_fields = array(
		/* Format: Field Label => array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'StockNo'=> array('stock' => 'stockno'),
		'Warehouse'=> array('stock' => 'whid'),
		'Producto'=> array('stock' => 'pdoid'),
		'Stock'=> array('stock' => 'stocknum'),
		'Assigned To' => array('crmentity' => 'smownerid')
	);
	public $list_fields_name = array(
		/* Format: Field Label => fieldname */
		'StockNo'=> 'stockno',
		'Warehouse'=> 'whid',
		'Producto'=> 'pdoid',
		'Stock'=> 'stocknum',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	public $list_link_field = 'stockno';

	// For Popup listview and UI type support
	public $search_fields = array(
		/* Format: Field Label => array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'StockNo'=> array('stock' => 'stockno'),
		'Warehouse'=> array('stock' => 'whid'),
		'Producto'=> array('stock' => 'pdoid'),
		'Stock'=> array('stock' => 'stock'),
	);
	public $search_fields_name = array(
		/* Format: Field Label => fieldname */
		'StockNo'=> 'stockno',
		'Warehouse'=> 'whid',
		'Producto'=> 'pdoid',
		'Stock'=> 'stock',
	);

	// For Popup window record selection
	public $popup_fields = array('stockno');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	public $sortby_fields = array();

	// For Alphabetical search
	public $def_basicsearch_col = 'stockno';

	// Column value to use on detail view record text display
	public $def_detailview_recname = 'stockno';

	// Required Information for enabling Import feature
	public $required_fields = array();

	// Callback function list during Importing
	public $special_functions = array('set_import_assigned_user');

	public $default_order_by = 'stockno';
	public $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	public $mandatory_fields = array('createdtime', 'modifiedtime');

	public function save_module($module) {
		if ($this->HasDirectImageField) {
			$this->insertIntoAttachment($this->id, $module);
		}
		global $adb;
		if ($this->mode!='edit') { // direct create, sum stock to product total
			$adb->pquery('update vtiger_products set qtyinstock=qtyinstock+? where productid=?', array($this->column_fields['stocknum'], $this->column_fields['pdoid']));
		}
	}

	public function trash($module, $id) {
		global $adb;
		$stck=$adb->query("select stocknum,pdoid from vtiger_stock where stockid=$id");
		$snum=$adb->query_result($stck,0,'stocknum');
		$pdid=$adb->query_result($stck,0,'pdoid');
		$adb->query("update vtiger_products set qtyinstock=qtyinstock-$snum where productid=$pdid");
		parent::trash($module, $id);
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param string Module name
	 * @param string Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	public function vtlib_handler($modulename, $event_type) {
		if ($event_type == 'module.postinstall') {
			// Handle post installation actions
			// Related lists
			$this->setModuleSeqNumber('configure', $modulename, 'stck-', '0000001');
			$module = Vtiger_Module::getInstance($modulename);
			$mod = Vtiger_Module::getInstance('Products');
			$mod->setRelatedList($module, 'Stock', Array('ADD'),'get_dependents_list');
			$mod = Vtiger_Module::getInstance('Warehouse');
			$mod->setRelatedList($module, 'Stock', Array('ADD'),'get_dependents_list');
			//Create Outside warehouses
			global $current_user, $adb;
			include_once 'modules/Warehouse/Warehouse.php';
			$module = new Warehouse();
			$module->column_fields['assigned_user_id']=$current_user->id;
			$module->column_fields['title']='Sale';
			$module->save('Warehouse');
			$adb->query("update vtiger_warehouse set warehno='Sale' where warehouseid=".$module->id);
			$module->column_fields['title']='Purchase';
			$module->save('Warehouse');
			$adb->query("update vtiger_warehouse set warehno='Purchase' where warehouseid=".$module->id);
		} elseif ($event_type == 'module.disabled') {
			// Handle actions when this module is disabled.
		} elseif ($event_type == 'module.enabled') {
			// Handle actions when this module is enabled.
		} elseif ($event_type == 'module.preuninstall') {
			// Handle actions when this module is about to be deleted.
		} elseif ($event_type == 'module.preupdate') {
			// Handle actions before this module is updated.
		} elseif ($event_type == 'module.postupdate') {
			// Handle actions after this module is updated.
		}
	}
}
?>
