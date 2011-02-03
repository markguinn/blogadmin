<?php
/**
 * Displays a list of recent comments.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogadmin
 * @subpackage widgets
 * @date 9.22.10
 */
class RecentCommentsWidget extends Widget {

	static $title = 'Recent Comments';
	static $cmsTitle = 'Recent Comments';
	static $description = 'Displays the most recent comments.';

	public $NumToDisplay = 5;


	/**
	 * returns a list of comments. right now that's
	 * all comments anywhere on the site - in the
	 * future we should narrow it
	 */
	function Comments(){
		return DataObject::get('PageComment', '`IsSpam`=0', '`Created` desc', '', $this->NumToDisplay);
	}
	
}


class RecentCommentsWidget_Controller extends Widget_Controller {

	/**
	 * render with a generic template for lists of posts
	 */
	function Content(){
		return $this->renderWith(array('CommentListWidget'));
	}

}