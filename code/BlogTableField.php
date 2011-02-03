<?php
/**
 * Special table field for blog admin.
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogadmin
 */
class BlogTableField extends TableListField {

	/**
	 * fields to display (could in theory be changed in _config.php)
	 */
	static $blog_list_fields = array(
		'Title' 		=> 'Post Title',
		'Author' 		=> 'Author',		// will be removed if needed
		'AuthorMember.Name'	=> 'Author',	// will be removed if needed
		'Date' 			=> 'Post Date',
		'Parent.Title'	=> 'Blog',			// will be removed if needed
		'CategoryList'	=> 'Categories', 	// will be removed if needed
		'TagList'		=> 'Tags',
		'Status'		=> 'Live',
		'CommentsCount' => '&nbsp;',
	);
	
	/**
	 * casting for fields - I'm breaking this out in this way in case
	 * you don't actually want it to be NiceUS
	 */
	static $blog_list_casting = array(
		'Date' 		=> 'Date->NiceUS',
	);

	protected $template = "BlogTableListField";
	
	public $itemClass = 'BlogTableField_Item';

	public $actions = array(
		'show' => array(
			'label' => 'Preview',
			'icon' => 'cms/images/show.png',
			'icon_disabled' => 'cms/images/show_disabled.png',
			'class' => 'showbloglink' 
		),
		'edit' => array(
			'label' => 'Edit',
			'icon' => 'cms/images/edit.gif',
			'icon_disabled' => 'cms/images/edit_disabled.gif',
			'class' => 'editbloglink' 
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
		// make a few changes to the fields based on the available features
		if (BlogAdmin::use_member_authors()) {
			unset(self::$blog_list_fields['Author']);
		} else {
			unset(self::$blog_list_fields['AuthorMember.Name']);
		}
		
		if (!BlogAdmin::use_categories()) {
			unset(self::$blog_list_fields['CategoryList']);
		}
		
		// if there is only one blogholder, no need to display that field
		$holders = DataObject::get('BlogHolder');
		if (!$holders || $holders->Count() == 1) {
			unset(self::$blog_list_fields['Parent.Title']);
		}
		
		// set up the parent field object
		parent::__construct($name, 'BlogEntry', self::$blog_list_fields);
		
		$this->setPermissions(array('show','edit','delete'));
		
		// set up the query so we can join in the comments
		$query = singleton('BlogEntry')->extendedSQL('', 'Date desc', null, 
			'LEFT JOIN "PageComment" ON "PageComment"."ParentID" = "SiteTree"."ID"');
		$query->select[] = 'count("PageComment"."ID") as "CommentsCount"';

		// only allow them to see their own posts if they're not an admin
		if (BlogAdmin::use_member_authors() && !Permission::check('BLOGMANAGEMENT') && !Permission::check('ADMIN')) {
			$query->where[] = "\"AuthorMemberID\" = '" . Member::currentUserID() . "'";
		}

		// add the filter if needed
		if ($filter) {
			if (isset($filter['AuthorMemberID']) && $filter['AuthorMemberID'] > 0){
				$SQL_author = (int)$filter['AuthorMemberID'];
				$query->where[] = "`BlogEntry`.`AuthorMemberID` = '$SQL_author'";
			}
			
			if (isset($filter['Author']) && $filter['Author'] != '' && $filter['Author'] != 'Author...'){
				$SQL_author = Convert::raw2sql($filter['Author']);
				$query->where[] = "`BlogEntry`.`Author` LIKE '%$SQL_author%'";
			}
			
			if (isset($filter['Search']) && $filter['Search'] != '' && $filter['Search'] != 'Search...') {
				$SQL_search = Convert::raw2sql($filter['Search']);
				$query->where[] = "(
					`SiteTree`.`Title` LIKE '%{$SQL_search}%' OR 
					`SiteTree`.`Content` LIKE '%{$SQL_search}%' OR
					`BlogEntry`.`Tags` LIKE '%{$SQL_search}%'
				)";
			}
			
			if (isset($filter['Status']) && $filter['Status'] != '') {
				$SQL_status = Convert::raw2sql($filter['Status']);
				$query->where[] = "`Status` = '$SQL_status'";
			}
			
			if (isset($filter['CategoryID']) && $filter['CategoryID'] > 0) {
				$SQL_cat = (int)$filter['CategoryID'];
				$query->from[] = 'LEFT JOIN "BlogCategory_Entries" ON "BlogCategory_Entries"."BlogEntryID" = "SiteTree"."ID"';
				$query->where[] = "`BlogCategory_Entries`.`BlogCategoryID` = '$SQL_cat'";
			}

			if (isset($filter['ParentID']) && $filter['ParentID'] > 0) {
				$SQL_blog = (int)$filter['ParentID'];
				$query->where[] = "`SiteTree`.`ParentID` = '$SQL_blog'";
			}
		}
		
		$query->groupby('"SiteTree"."ID"');
		$this->setCustomQuery($query);
				
		// set up appropriate casting
		$this->setFieldCasting(self::$blog_list_casting);
		
		// set up formatting
		$this->setFieldFormatting(array(
			'Status'	=> '<img src=\\"blogadmin/images/tickbox-$value.gif\\" alt=\\"$value\\" title=\\"$value\\"/>',
		));
		
		// paging
		$this->setShowPagination(true);
		$this->setPageSize(10);
		
		// allow checkboxs for multiple-delete
		$this->Markable = true;
		$this->MarkableTitle = '<input type="checkbox" name="select_all_posts" id="select_all_posts" value="1"/>';
		
		Requirements::javascript(BLOGADMIN_DIR . '/javascript/BlogTableField.js');
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
 * Single row of a {@link BlogTableField}
 * @package blogadmin
 */
class BlogTableField_Item extends TableListField_Item {

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
				case 'CommentsCount':
					$field->Value = "<a class=\"commentsLink\" href=\"admin/blog/editpost/{$this->item->ID}\" title=\"Comments\">{$field->Value}</a>";
				break;
				
				// @todo add in content summary with link to reveal
			}
		}
		
		return $fields;
	}


	function ShowLink() {
		return $this->item->Link() . '?stage=Stage';
	}

	function EditLink() {
		return BlogAdmin::make_link('editpost', $this->item->ID);
//		return Controller::join_links($this->Link(), "edit");
	}

	function DeleteLink() {
		return BlogAdmin::make_link('deletepost', $this->item->ID);
//		return Controller::join_links($this->Link(), "delete");
	}


	function Can($mode) {
		if (Permission::check('BLOGMANAGEMENT') || Permission::check('ADMIN')) return true;
		if (BlogAdmin::use_member_authors() && $this->item->AuthorMemberID == Member::currentUserID()) return true;
		return false;
	}

}

