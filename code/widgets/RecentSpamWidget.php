<?php
/**
 * Displays a list of recent spam comments.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogadmin
 * @subpackage widgets
 * @date 9.22.10
 */
class RecentSpamWidget extends RecentCommentsWidget {

	static $title = 'Recent Spam Comments';
	static $cmsTitle = 'Recent Spam Comments';
	static $description = 'Displays the most recent comments.';

	public $NumToDisplay = 5;


	/**
	 * returns a list of comments. right now that's
	 * all comments anywhere on the site - in the
	 * future we should narrow it
	 */
	function Comments(){
		return DataObject::get('PageComment', '`IsSpam` = 1', '`Created` desc', '', $this->NumToDisplay);
	}
	
}