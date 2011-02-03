<?php
/**
 * Displays the top 5 most recent posts. If a user with appropriate
 * permissions is logged in, it will also have an edit link and
 * the comments will link to the admin comments interface instead
 * of the post itself.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogadmin
 * @subpackage widgets
 * @date 9.23.10
 */
class RecentPostsWidget extends Widget {

	static $title = 'Recent Posts';
	static $cmsTitle = 'Recent Posts';
	static $description = 'Displays a list of the most recent posts.';
	
	// @todo make this configurable
	public $NumToDisplay = 10;
	
	// @todo limit by blog?
	

	/**
	 * return a list of entries
	 */
	function Entries(){
		$where = BlogAdmin::use_member_authors() && !Permission::check('BLOGMANAGEMENT') && !Permission::check('ADMIN')
			? "\"AuthorMemberID\" = '" . Member::currentUserID() . "'"
			: "";

		return DataObject::get('BlogEntry', $where, '`Created` desc', '', $this->NumToDisplay);
	}

}



class RecentPostsWidget_Controller extends Widget_Controller {

	/**
	 * render with a generic template for lists of posts
	 */
	function Content(){
		return $this->renderWith(array('BlogEntryListWidget'));
	}

}


