<?php
/**
 * Widget to display all the authors of the current blog. Requires members as authors
 * to be enabled.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogadmin
 * @subpackage widgets
 * @date 12/30/10
 */
class BlogAuthorsWidget extends Widget {

	static $title = 'Authors';
	static $cmsTitle = 'Blog Authors';
	static $description = 'Displays a list of blog authors.';


	/**
	 * @return DataObjectSet
	 */
	function Authors() {
		// TODO: if the context is a certain BlogHolder, should limit to authors with posts in that blog
		// TODO: post counts would be great
		return BlogExtensions::get_all_authors();
	}

}
