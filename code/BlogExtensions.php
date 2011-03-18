<?php
/**
 * Adds a few features to the blogentry class.
 *
 * Usage in _config.php:
 *
 * BlogExtensions::$enable_blog_categories = true;
 * BlogExtensions::$enable_members_as_authors = true;
 * Object::add_extension('BlogEntry', 'BlogExtensions');
 *
 * @author Mark Guinn
 * @package blogext
 */
class BlogExtensions extends DataObjectDecorator {

	/**
	 * @var bool
	 * enable/disable the many-to-many blog category feature
	 */
	static $enable_blog_categories = false;
	
	/**
	 * @var bool
	 * enable/disable the has-one(member) as author feature
	 */
	static $enable_members_as_authors = false;

	/**
	 * if set to an array of group id's, only those
	 * members in those groups will be in the dropdown
	 */
	static $author_groups = null;

	/**
	 * for getCategoryList - $Field will be replaced with $cat->Field
	 */	
	static $category_list_format = '$Title';

	/**
	 * for getTagList - $Title is the only appropriate substitution (also $URL_Title and $XML_Title)
	 */	
	static $tag_list_format = '$Title';


	/**
	 * add some database stuff
	 */
	function extraStatics() {
		$stat = array();
		
		if (self::$enable_blog_categories) {
			$stat['belongs_many_many'] = array(
				'Categories' => 'BlogCategory'
			);
		}
		
		if (self::$enable_members_as_authors) {
			$stat['has_one'] = array(
				'AuthorMember' => 'Member'
			);
		}
		
		return $stat;
	}


	/**
	 * returns a comma-delimited list of categories
	 */
	function getCategoryList(){
		if (!self::$enable_blog_categories) return '';
		
		$list = array();
		$cats = $this->getOwner()->Categories();
		
		$format = preg_replace('/\$([A-Za-z0-9-_]+)/','{$item->$1}', self::$category_list_format);
		$format = str_replace('"', '\\"', $format);
		
		foreach ($cats as $item) {
			eval('$list[] = "' . $format . '";');
		}
		
		return implode(', ', $list);
	}


	/**
	 * returns a comma-delimited list of tags, formatted appropriately
	 */
	function getTagList(){
		$list = array();
		$tags = BlogTag::split_tags($this->getOwner()->Tags);
		
		$format = preg_replace('/\$([A-Za-z0-9-_]+)/','{$item->$1}', self::$category_list_format);
		$format = str_replace('"', '\\"', $format);
		
		foreach ($tags as $tag) {
			$list[] = str_replace(
				array(
					'$Title',
					'$XML_Title',
					'$URL_Title',
				), array(
					$tag,
					Convert::raw2xml($tag),
					urlencode($tag),
				), self::$tag_list_format
			);
		}
		
		return implode(', ', $list);
	}


	/**
	 * returns the author field unless the member as author extension
	 * is functioning in which case it returns the member name.
	 * @return string
	 */
	function getAuthor() {
		if (self::$enable_members_as_authors) {
			//return $this->getOwner()->AuthorMember();
			
			if ( $author = $this->getOwner()->AuthorMember() ){
				return $author->getName();
			}
		} else {
			return $this->getOwner()->Author;
		}
	}


	/**
	 * returns the profile page for the author if any
	 * @return AuthorBioPage
	 */
	/* 
	function getAuthorBioPage() {
		if (!self::$enable_members_as_authors) return;
		$author = $this->getOwner()->AuthorMember();
		
		if($author) return $author;
		//return $author ? AuthorBioPage::get_by_member($author) : null;
	}
	/*
	
	
	/**
	 * returns a link to the profile page
	 */
	function getAuthorLink() {
		if (!BlogAdmin::use_member_authors()) return $this->getAuthor();

		$profile = $this->getOwner()->AuthorMember();
		if (!$profile) return '';
		
		$name = Convert::raw2xml($profile->getName());

		return $profile 
			? '<a href="' . $profile->BlogProfileLink() . '">' . $name . '</a>'
			: $name;
	}
	
	
	/**
	 * Returns all authors that are in the author group, if it's
	 * been established, otherwise returns all members.
	 *
	 * @static
	 * @return DataObjectSet
	 */
	static function get_all_authors($filters = "", $sort = "Surname ASC, FirstName ASC") {
		if (self::$author_groups) {
			return DataObject::get(
				'Member',
				"\"GroupID\" IN (" . implode(',', self::$author_groups) . ")" . ($filters ? " AND $filters" : ""),
				$sort,
				"INNER JOIN \"Group_Members\" ON \"MemberID\"=\"Member\".\"ID\""
			);
		} else {
			return DataObject::get('Member', $filters, $sort);
		}
	}
	
	
	/**
	 * returns a list of categories
	 * @TODO Make a categories widget
	 */
	function getCategories(){
		return BlogTreeExtensions::getCategories();
	}
	
	/**
	 * returns a list of categories
	 * @TODO Make an authors widget
	 */
	function getAuthors(){
		return BlogTreeExtensions::getAuthors();
	}	

}




/**
 * Extension to remove blog entries from the site tree.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogadmin
 * @subpackage extensions
 * @date 1.8.11
 */
/*
class BlogExtensions_CMSMain extends Extension
{

	public function SiteTreeAsUL() {
		$this->getOwner()->generateDataTreeHints();
		$this->getOwner()->generateTreeStylingJS();

		// Pre-cache sitetree version numbers for querying efficiency
		Versioned::prepopulate_versionnumber_cache("SiteTree", "Stage");
		Versioned::prepopulate_versionnumber_cache("SiteTree", "Live");

		return $this->getOwner()->getSiteTreeFor("SiteTree", null, null, null, array($this, 'filterOutBlogEntries'));
	}
	
	
	function filterOutBlogEntries($n) {
		return ($n->ClassName != 'BlogEntry');
	}

}
*/
