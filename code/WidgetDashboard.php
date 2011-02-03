<?php
/**
 * Extension of WidgetArea that, rather than providing a single widget
 * area on the sidebar or something, gives a user 1 or more columns of
 * widgets that can be resorted and is saved for each logged-in member
 * It's actually a form, so that it can handle saving too. 
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogadmin
 * @date 9.23.10
 */
class WidgetDashboard extends Form {

	protected $_numColumns;
	protected $_defaults;
	
	public $DashboardTitle;
	
	function __construct($controller, $method, $numColumns=2, $header = '', $defaults=array()){
		$this->_numColumns = $numColumns;
		$this->_defaults = $defaults;
		$this->DashboardTitle = $header;
		
		// include all our javascript
		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/base/jquery.ui.all.css');
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui-1.8rc3.custom.js');
		Requirements::javascript(BLOGADMIN_DIR . '/javascript/WidgetDashboard.js');
		
		// create the simple form
		$fields = new FieldSet(
			new HiddenField('IsDashboard', '', '1')
		);
		
		for ($i = 1; $i <= $numColumns; $i++) {
			$fields->push(new HiddenField("column_$i", '', "UNTOUCHED"));
		}
		
		$actions = new FieldSet(
			new FormAction('doSaveWidgets', 'Save Widgets')
		);
		
		parent::__construct($controller, $method, $fields, $actions);
	}
	
	
	/**
	 * saves the widgets for the current user
	 */
	function doSaveWidgets($data, $form) {
		$memberID = Member::currentUserID();

		// loop through each column
		foreach ($data as $key => $val) {
			if (preg_match('/^column_(\d)$/', $key, $m) && $val != 'UNTOUCHED') {
				// figure out inputs
				$colNum = (int)$m[1];
				$widgetIDs = explode(',', $val);
				
				// grab the column's id
				$col = DataObject::get_one('WidgetDashboard_Column', "`MemberID` = '$memberID' AND `Column` = '$colNum'");
				if ($col) {
					// orphan any widgets that were previously under this column
					// NOTE: i'm not sure if this is the best way to do it, but it's clearer (to me anyway)
					// and faster so I think it's ok. It could be rewritten to use the ORM if needed
					DB::query("
						UPDATE `Widget` SET
							Enabled = 0,
							ParentID = 0
						WHERE ParentID = '{$col->ID}'
					");
					
					// loop through and add any widgets
					foreach ($widgetIDs as $i => $id) {
						$widget = DataObject::get_by_id('Widget', $id);
						$widget->Sort = $i+1;
						$widget->ParentID = $col->ID;
						$widget->Enabled = true;
						$widget->write();						
					}
				}
			}
		}
	
		return 'ok';
	}
	
	
	/**
	 * Returns the columns and creates them if needed
	 * If no member is logged in, it will return the defaults every time
	 * @TODO: would be cool to use cookies if noone is logged in
	 */
	function getColumns() {
		// load the saved columns
		$id = Member::currentUserID();
		$columns = DataObject::get('WidgetDashboard_Column', "`MemberID` = '$id'", 'Column');
		
		// if none are found, fake it with the defaults
		if (!$columns) {
			$columns = new DataObjectSet();
			
			foreach ($this->_defaults as $n => $dcol) {
				// create the column
				$col = new WidgetDashboard_Column(array(
					'MemberID' => $id,
					'Column' => $n+1,
				));
				$col->write();
				
				// create the widgets
				foreach ($dcol as $i => $className){
					$w = new $className(array(
						'Sort' => $i+1,
						'Enabled' => true,
						'ParentID' => $col->ID,
					));
					$w->write();
				}
				
				// add it to the list
				$columns->push($col);
			}
		}
		
		return $columns;
	}
	
	
	function forTemplate(){
		return $this->renderWith(array('WidgetDashboard'));
	}
	
}


/**
 * actual subclass of widgetarea - each widgetdashboard has 1 or more of these
 */
class WidgetDashboard_Column extends WidgetArea {

	static $db = array(
		'Column' => 'Int',
	);
	
	static $has_one = array(
		'Member' => 'Member',
	);
	
}

