<?php
/**
 * Adds a few features to the BlogTree class.
 *
 * Usage in _config.php:
 *
 * Object::add_extension('BlogTree_Controller', 'BlogTreeExtensions');
 *
 * @author Tyler Kidd
 * @package blogadmin
 * @subpackage extensions
 */

class BlogTreeExtensions extends DataObjectDecorator {
	
	static $CustomEntryList = '';
	
	static $limit = 10;

	static $allowed_actions = array(
		'category',
		'author',
		'profile'
	);
	
	function author(){
		$URLSegment = Director::urlParam('URLSegment');
		$ID = Director::urlParam('ID');
 		
 		$container = BlogTree::Current();	 		
 		if($URLSegment != $container->URLSegment){
			//$this->request->shiftAllParams();
			//$this->request->shift();
			$ID = Director::urlParam('OtherID');
 		}
 		
 		$field = is_numeric($ID) ? 'ID' : 'BlogProfileURLSegment';
 		$ID = Convert::raw2sql($ID);
 		$profile = DataObject::get_one('Member',"Member.$field = '$ID'");
		$entries = $profile->getComponents('BlogEntries',"",'Created DESC','',self::$limit);
		
		if($entries) {
			$this->CustomEntryList = $entries;
		} else {
			$this->CustomEntryList = '';
		}
		
		return $this->getOwner()->customise(array(
			'Title' => 'Posts by ' . $profile->Title(),
		));
	}
	
	
	function profile(){	
		$URLSegment = Director::urlParam('URLSegment');
		$ID = Director::urlParam('ID');
		
		$container = BlogTree::Current();
			 		
 		if($URLSegment != $container->URLSegment){
			//$this->request->shiftAllParams();
			//$this->request->shift();
			$ID = Director::urlParam('OtherID');
 		}
 		
 		$profile = DataObject::get_one('Member',"Member.BlogProfileURLSegment = '$ID'");
				
		if($profile) {
//			$controller = new BlogProfile_Controller();
//   			$controller->init();
//   			$controller->Profile = $profile;
//   			
   			// Set the title of the page
//   			$controller->MetaTitle = $profile->Title;

//			return $controller->getViewer('index')->process($controller);
			return $this->getOwner()->customise(array(
				'Profile'	=> $profile,
				'MetaTitle'	=> $profile->Title,
			))->renderWith(array('BlogProfile','Page'));
		}
		
		return false;
	}

	/**
	 * Check if the category exists.
	 * If it does - set the CustomEntryList with it's entries
	 **/
 	function category() {

			$URLSegment = Director::urlParam('URLSegment');
			$URLCategory = Director::urlParam('ID');

	 		$container = BlogTree::Current();	 		
	 		if($URLSegment != $container->URLSegment){
				//$this->request->shiftAllParams();
				//$this->request->shift();
				$URLCategory = Director::urlParam('OtherID');
	 		}

			$SQL_cat = Convert::raw2sql($URLCategory);
			$category = DataObject::get_one('BlogCategory',"URLSegment = '$SQL_cat'");
			
			if($category){				
				if($category->Entries()){
					$this->CustomEntryList = $category->getManyManyComponents('Entries',"",'Created DESC','',self::$limit);
				}
			}else{
				$this->CustomEntryList = '';
			}
			
		return array(
			'SelectedCategory' => $category,
			'CustomBlogEntries' => $this->CustomEntryList,
			'BlogEntries' => $this->CustomEntryList,
		);
	}
	

	/*
	 * get list of authors pages
	 * this needs to be changed to display Members who are authors - not all
	 * need to determine if they are a blog author & if they are an author
	 * of this blog holder
	 */
	function getAuthors(){
		//$group = DataObject::get_by_id('Group',1);
		//return $group->Members();
		
		return DataObject::get('Member',"BlogProfileURLSegment != ''");
	}
	
	function getCategories(){
		//if($this->
		
		//$id = $container = BlogTree::current()->ID;
		//if($id == null)	$id = $this->Owner->ID;
		//return DataObject::get('BlogCategory',"BlogID = $id");
		//if($this->getOwner()->ClassName == 'BlogHolder') return $this->getOwner()->Categories();
		$owner = $this->getOwner();
		return DataObject::get('BlogCategory',"BlogID = $owner->ID");
	}

	/**
	 ** override BlogEntries from Blog Module
	 **
	 ** Now that we're just overridding BlogEntries, 
	 ** we might add the filters in this method.
	 */
	
	function CustomBlogEntries($limit = 10) {		
		if(isset($this->CustomEntryList)){
			if(empty($this->CustomEntryList)) return false;
			return $this->CustomEntryList;
		}
		
		return $this->owner->BlogEntries($limit);
	
		require_once('Zend/Date.php');
		
		if($limit === null) $limit = BlogTree::$default_entries_limit;
	
		// only use freshness if no action is present (might be displaying tags or rss)
		if ($this->LandingPageFreshness && !$this->request->param('Action')) {
			$d = new Zend_Date(SS_Datetime::now()->getValue());
			$d->sub($this->LandingPageFreshness);
			$date = $d->toString('YYYY-MM-dd');
			
			$filter = "\"BlogEntry\".\"Date\" > '$date'";
		} else {
			$filter = '';
		}
		// allow filtering by author field and some blogs have an authorID field which
		// may allow filtering by id
		if(isset($_GET['author']) && isset($_GET['authorID'])) {
			$author = Convert::raw2sql($_GET['author']);
			$id = Convert::raw2sql($_GET['authorID']);
			
			$filter .= " \"BlogEntry\".\"Author\" LIKE '". $author . "' OR \"BlogEntry\".\"AuthorID\" = '". $id ."'";
		}
		else if(isset($_GET['author'])) {
			$filter .=  " \"BlogEntry\".\"Author\" LIKE '". Convert::raw2sql($_GET['author']) . "'";
		}
		else if(isset($_GET['authorID'])) {
			$filter .=  " \"BlogEntry\".\"AuthorID\" = '". Convert::raw2sql($_GET['authorID']). "'";
		}
		
		$start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
		
		$date = $this->SelectedDate();
		
		return $this->Entries("$start,$limit", $this->SelectedTag(), ($date) ? $date->Format('Y-m') : '', null, $filter);
	}

}