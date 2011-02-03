<?php
/**
 * Special table field for blog admin.
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogadmin
 * @modified Tyler Kidd <tyler@adaircreative.com>
 * currently lacking features like Delete/Filter
 */
 
class AuthorTableField extends TableListField {

	/**
	 * fields to display (could in theory be changed in _config.php)
	 */
	static $author_list_fields = array(
		'FirstName'	=> 'FirstName',
		'Surname'	=> 'LastName'
	);
	
	/**
	 * casting for fields - I'm breaking this out in this way in case
	 * you don't actually want it to be NiceUS
	 */
	static $author_list_casting = array(
	);

	protected $template = "AuthorTableListField";
	
	public $itemClass = 'AuthorTableField_Item';

	public $actions = array(
		'show' => array(
			'label' => 'Preview',
			'icon' => 'cms/images/show.png',
			'icon_disabled' => 'cms/images/show_disabled.png',
			'class' => 'showauthorlink'
		),
		'edit' => array(
			'label' => 'Edit',
			'icon' => 'cms/images/edit.gif',
			'icon_disabled' => 'cms/images/edit_disabled.gif',
			'class' => 'editauthorlink' 
		),
		'delete' => array(
			'label' => 'Delete',
			'icon' => 'cms/images/delete.gif',
			'icon_disabled' => 'cms/images/delete_disabled.gif',
			'class' => 'deletelink' 
		),
	);


	/**
	 * create the list with some configuration built in
	 */
	function __construct($name, $filter = null){
	
		// set up the parent field object
		parent::__construct($name, 'Member', self::$author_list_fields);
		
		$this->setPermissions(array('show','edit','delete'));

		$query = (Permission::check('BLOGMANAGEMENT') || Permission::check('ADMIN'))
			? singleton('Member')->extendedSQL('', '', null,'')
			: singleton('Member')->extendedSQL('"ID" = '.Member::currentUserID(), '', null,'');
		
		$this->setCustomQuery($query);
				
		// set up appropriate casting
		$this->setFieldCasting(self::$author_list_casting);
		
		// paging
		$this->setShowPagination(true);
		$this->setPageSize(10);
		
		Requirements::javascript(BLOGADMIN_DIR . '/javascript/AuthorTableField.js');
	}
	
	
	/**
	 * for now we'll just disable sorting - filtering is more effective
	 * and the TableListField sorting doesn't work together with paging
	 * at all.
	 */
	function isFieldSortable($f){
		return false;
	}
		
}


/**
 * Single row of a {@link AuthorTableField}
 * @package blogadmin
 */
class AuthorTableField_Item extends TableListField_Item {

	/**
	 * override the default fields like to format them
	 * more how we want - could use setFieldFormatting
	 * but I can't figure out how to do the splitting/joining
	 * for tags/categories with that method.
	 * @TODO make it safe for csv export
	 * @TODO move this to a decorator field for tags + setfieldformatting 
	 */
	function Fields($xmlSafe=true){
		$fields = parent::Fields($xmlSafe);
		foreach ($fields as $field){
			switch ($field->Name){
				// link up the tags
/*
				case 'Tags':
					$tags = explode(',', $field->Value);
					foreach ($tags as &$tag){
						$tag = trim($tag);
						$URL_tag = urlencode($tag);
						$tag = "<a class=\"filterLink tagFilter\" href=\"admin/blog/ListForm/field/Entries?ctf[filter][Search]=$URL_tag\">$tag</a>";
					}
					
					$field->Value = implode(', ', $tags);
				break;
*/
				
				// link up comments
				//case 'CommentsCount':
				//	$field->Value = "<a class=\"commentsLink\" href=\"admin/blog/editpost/{$this->item->ID}\" title=\"Comments\">{$field->Value}</a>";
				//break;
				
				// @todo add in content summary with link to reveal
			}
		}
		
		return $fields;
	}

	function EditLink() {
		return BlogAdmin::make_link('editauthor', $this->item->ID);
//		return Controller::join_links($this->Link(), "edit");
	}

	function DeleteLink() {
		//return BlogAdmin::make_link('deletepost', $this->item->ID);
//		return Controller::join_links($this->Link(), "delete");
	}


	function Can($mode) {
		return Permission::check('BLOGMANAGEMENT') || Permission::check('ADMIN')
			   || $this->item->ID == Member::currentUserID();
	}
}

