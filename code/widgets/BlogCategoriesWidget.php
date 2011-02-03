<?php
/**
 * Widget to display all the categories of the current blog. Requires many-to-many categories
 * to be enabled.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogadmin
 * @subpackage widgets
 * @date 12/30/10
 */
class BlogCategoriesWidget extends Widget {

	static $title = 'Categories';
	static $cmsTitle = 'Blog Categories';
	static $description = 'Displays a list of categories.';

	/**
	 * @return DataObjectSet
	 */
	function Categories() {
		// TODO if the context is a certain blogholder should limit to those categories
		// TODO counts would be great
		return DataObject::get('BlogCategory');
	}
}
