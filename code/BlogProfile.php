<?php
/**
 * Profile page for members, particularly blog authors. While there is a
 * special interface in the blogadmin for an author to edit his/her own
 * profile, the profile page can also be edited as a normal page within
 * the CMS. By default the page is created as a child of the first BlogHolder,
 * but they can live anywhere.
 *
 * @todo - non-admins should only be able to edit their own profile
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogadmin
 * @date 8.14.10
 */

class BlogProfile_Controller extends Page_Controller {
		
	function init(){
		parent::init();
		
		//$this->MetaTitle = $this->Profile->FirstName;
		
	}
}
