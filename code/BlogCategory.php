<?php
/**
 * Blog category as a many-to-many dataobject
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogext
 */
class BlogCategory extends DataObject {
	
	static $db = array(
		'Title' => 'Varchar',
		'URLSegment' => 'Varchar',
		'Content' => 'Text',
	);
	
	static $has_one = array(
		'Blog' => 'BlogHolder'
	);
	
	static $many_many = array(
		'Entries' => 'BlogEntry',
	);

	function EntriesCount(){
		// TODO cache this and/or make it easy to bundle in the query
		return $this->Entries()->Count();
	}


	function canCreate() {
		return (Permission::check('BLOGMANAGEMENT') || Permission::check('ADMIN'));
	}

	function canDelete() {
		return (Permission::check('BLOGMANAGEMENT') || Permission::check('ADMIN'));
	}

	function canEdit() {
		return (Permission::check('BLOGMANAGEMENT') || Permission::check('ADMIN'));
	}
	

	/*
	 * Return the category search link
	 */
	function Link(){
		// TODO - make this work even if the current context is not a blogholder
		$container = BlogTree::current();
		return $container->Link().'category/'.$this->URLSegment;
	}
	
	function onBeforeWrite(){
 		$this->URLSegment = SiteTree::GenerateURLSegment($this->Title);
		parent::onBeforeWrite();
	}
	
}