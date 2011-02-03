<?php
/**
 * BlogTag emulates very roughly a many-to-many dataobject while allowing
 * the tags to be contained in a textfield on the BlogEntry object. This is
 * kind of experimental - I really don't know if it's a great idea or not
 * but it allows us to use ComplextTableField to edit tags which is helpful.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogadmin
 * @created 6.24.10
 */
class BlogTag extends ViewableData implements DataObjectInterface {

	public $ID;
	public $Title;
	public $Original;
	public $EntriesCount;
	
	static $tag_cache;


	function canCreate() {
		return (Permission::check('BLOGMANAGEMENT') || Permission::check('ADMIN'));
	}

	function canDelete() {
		return (Permission::check('BLOGMANAGEMENT') || Permission::check('ADMIN'));
	}

	function canEdit() {
		return (Permission::check('BLOGMANAGEMENT') || Permission::check('ADMIN'));
	}

	
	/**
	 * ID doesn't need to be persistent, it just needs to be consistent
	 * across requests for one session. It's only used for ComplexTableForm
	 * which expects a numeric id to be able to do edit and delete actions.
	 * So we just create a lookup table in the session and use the array
	 * key as an ID.
	 *
	 * Creates the id if it doesn't already exist.
	 *
	 * @param string $tag
	 * @return int
	 */
	static function get_tag_id($tag) {
		if (!isset($_SESSION['BlogTags'])) $_SESSION['BlogTags'] = array('');

		$id = array_search($tag, $_SESSION['BlogTags']);
		
		if ($id === false){
			$id = count($_SESSION['BlogTags']);
			$_SESSION['BlogTags'][] = $tag;
		}
		
		return $id;		
	}
	
	
	/**
	 * returns the tag for a given ID
	 */
	static function get_id_tag($id) {
		if (!isset($_SESSION['BlogTags'])) $_SESSION['BlogTags'] = array('');
		return isset($_SESSION['BlogTags'][$id]) ? $_SESSION['BlogTags'][$id] : null;
	}
	
	
	/**
	 * returns an array of all tags key=tag, val=count
	 */
	static function get_all_tags(){
		if (!isset(self::$tag_cache)){
			$allTags = array();
			$max = 0;
			$entries = DataObject::get('BlogEntry');
			if($entries) {
				foreach($entries as $entry) {
					$theseTags = self::split_tags($entry->Tags);
					foreach($theseTags as $tag) {
						if($tag != "") {
							$allTags[$tag] = isset($allTags[$tag]) ? $allTags[$tag] + 1 : 1; //getting the count into key => value map
							$max = ($allTags[$tag] > $max) ? $allTags[$tag] : $max;
						}
					}
				}
				
				self::natksort($allTags);
				self::$tag_cache = $allTags;
			}
		}
				
		return self::$tag_cache;
	}


	/**
	 * creates a new object
	 */
	function __construct($record=null, $singleton=null) {
		parent::__construct();
		if ($record && is_array($record)) {
			foreach ($record as $k => $v) {
				if ($k == 'Title'){
					$this->Title = $this->Original = $v;
				} else {
					$this->$k = $v;
				}
			}
			
			if (isset($this->Title) && !isset($this->ID)) {
				$this->ID = self::get_tag_id($this->Title);
			}
		}
	}
	
	
	/**
	 * if the tag has changed, merges and/or renames all existing instances
	 */
	function write(){
		// no need to even touch anything if it hasn't changed
		if ($this->Title == $this->Original) return;
		
		// clearing is the same as deleting
		if (trim($this->Title) == '') {
			$_SESSION['BlogTags'][$this->ID] = '';
			$this->delete();
			return;
		}
		
		// otherwise, break out the posts and go to town
		$blogs = DataObject::get('BlogEntry');
		foreach ($blogs as $entry){
			// split the tags apart
			$tags = self::split_tags($entry->Tags);
			$newtags = array();
			
			// rename if it's there
			$changed = false;
			foreach ($tags as $tag){
				if (trim($tag) == $this->Original) {
					$newtags[] = $this->Title;
					$changed = true;
				} else {
					$newtags[] = $tag;
				}
			}
			
			// merge if needed
			$newtags = array_unique($newtags);
			
			// rebuild and rewrite
			if ($changed) {
				$entry->Tags = implode(', ', $newtags);
				$entry->writeToStage('Stage');
				if ($entry->Status == 'Published') $entry->publish('Stage','Live');
			}
		}
		
		// update the ID's in the session
		$_SESSION['BlogTags'][$this->ID] = $this->Title;
		$this->Original = $this->Title;
	}
	
	
	/**
	 * removes the tag from all blog posts
	 */
	function delete(){
		$blogs = DataObject::get('BlogEntry');
		foreach ($blogs as $entry){
			// split the tags apart
			$tags = self::split_tags($entry->Tags);
			$newtags = array();
			
			// remove this one if it's there
			$changed = false;
			foreach ($tags as $tag){
				if (trim($tag) != $this->Original) {
					$newtags[] = $tag;
				} else {
					$changed = true;
				}
			}
			
			// rebuild and rewrite
			if ($changed) {
				$entry->Tags = implode(', ', $newtags);
				$entry->writeToStage('Stage');
				if ($entry->Status == 'Published') $entry->publish('Stage','Live');
			}
		}
	}
	
	
	static function split_tags($string){
		return split(" *, *", trim($string));
	}
	
	
	/**
	 * get a sorted and filtered list of tags from the posts
	 */
	function instance_get($filter="", $sort="", $join="", $limit="", $containerClass="DataObjectSet"){	
		$tags = self::get_all_tags();

// @todo sorting
//					uasort($allTags, array($this, "column_sort_by_popularity"));	//sort by popularity
//					$this->natksort($allTags);

		// filtering - this is really really basic right now
		$filter_key = false;
		$filter_val = false;
		if (preg_match('/"(.+)"\s*=\s*\'?(.+)\'?/', $filter, $m)){
			$filter_key = $m[1];
			$filter_val = $m[2];
		}

		// build a dataobjectset
		$num = 0;
		$output = new $containerClass();
		foreach($tags as $tag => $count) {
			$obj = new BlogTag(array(
				'ID' 			=> self::get_tag_id($tag),
				'Title' 		=> $tag,
				'EntriesCount'	=> $count,
			));
			
			if (!$filter_key || $obj->$filter_key == $filter_val){
				if ($limit && ++$num > $limit) break;
				$output->push($obj);
			}
		}
		
		return $output;
	}
	
	
	/**
	 * get a single tag from the list
	 */
	function instance_get_one($filter, $sort=""){
		// simpler version for get_by_id that doesn't break when tags are merged
		// doesn't populate EntriesCount but that's fine for how we're using it
		if (preg_match('/"ID"\s*=\s*\'?(\d+)\'?/', $filter, $m)) {
			$id = (int)$m[1];
			$tag = self::get_id_tag($id);
			return new BlogTag(array(
				'ID' 			=> self::get_tag_id($tag),
				'Title' 		=> $tag,
			));
		} else {
			$all = $this->instance_get($filter, $sort, "", 1);
			return $all->First();
		}
	}
	
	
	/**
	 * pretty straightforward but required by the interface
	 */
	function __get($field){
		return $this->$field;
	}
	

	/**
	 * set a value, with appropriate adjustments
	 */
	function setCastedField($field, $value) {
		switch ($field) {
			case 'ID':
				$this->ID = (int)$value;
				
			case 'Title':
				$this->Title = preg_replace('/[^a-zA-Z0-9\s\._\-]/', '', $value);
				
			case 'EntriesCount':
				$this->EntriesCount = (int)$value;
				
			default:
				$this->$field = $value;
		}
	}



	/**
	 * Helper method to compare 2 Vars to work out the results.
	 * @param mixed
	 * @param mixed
	 * @return int
	 */
	static function column_sort_by_popularity($a, $b){
		if($a == $b) {
			$result  = 0;
		} 
		else {
			$result = $b - $a;
		}
		return $result;
	}

	static function natksort(&$aToBeSorted) {
		$aResult = array();
		$aKeys = array_keys($aToBeSorted);
		natcasesort($aKeys);
		foreach ($aKeys as $sKey) {
		    $aResult[$sKey] = $aToBeSorted[$sKey];
		}
		$aToBeSorted = $aResult;

		return true;
	}


	/**
	 * these are just to maintain compatibility with ComplexTableField
	 */
	function singular_name(){ return 'Tag'; }
	function plural_name(){ return 'Tags'; }
	function fieldLabel($name){ return $name; }
}