<?php

/**
 * Adds a few features to the blogentry class.
 *
 * Usage in _config.php:
 *
 * Object::add_extension('Member', 'BlogMemberExtensions');
 *
 * @author Tyler Kidd
 * @package blogext
 */
 
class BlogMemberExtensions extends DataObjectDecorator {
   
   /**
   	* add extra fields to the Member class.
   	* 
    **/      
   function extraStatics() {
      return array(
         'db' => array(
            'BlogProfileTitle'			=> 'Varchar(100)',
            'BlogProfileURLSegment' 	=> 'Varchar(100)',
            'BlogProfileContent' 		=> 'HTMLText',
            'BlogProfileSecondaryContent' => 'HTMLText'
         ),
         'has_one' => array(
         	'BlogProfilePhoto' => 'Image'
         ),
         'has_many' => array(
         	'BlogEntries' => 'BlogEntry'
         )
      );
   }
	
	function ViewerGroups(){
		return false;
	}
	
	function BlogProfileLink(){
		if (!$this->getOwner()->BlogProfileURLSegment) return null;
		$container = BlogTree::current();
		return $container->Link() . 'profile/' . $this->getOwner()->BlogProfileURLSegment;
	}

	function EntriesLink(){
		$container = BlogTree::current();
		return $container->Link() . 'author/'
			. ($this->getOwner()->BlogProfileURLSegment ? $this->getOwner()->BlogProfileURLSegment : $this->getOwner()->ID);
	}
	
	function Title(){
		return rtrim($this->getOwner()->FirstName,' ').' '.$this->getOwner()->Surname;
	}


	/**
	 * Is this member also a blog author?
	 * @return bool
	 */
	function isBlogAuthor() {
		if (BlogExtensions::$author_groups) {
			return $this->getOwner()->inGroups( BlogExtensions::$author_groups );
		} else {
			return true;
		}
	}


	/**
	 * returns a SQLQuery that will give you only Members who are also authors
	 */
	static function author_query() {
		if (BlogExtensions::$author_groups) {
			$query = singleton('Member')->extendedSQL('', '', null,
				'INNER JOIN "Group_Members" ON "Group_Members"."MemberID" = "Member"."ID" '
					. ' AND "Group_Members"."GroupID" in (' . implode(',', BlogExtensions::$author_groups) .')');
		} else {
			$query = singleton('Member')->extendedSQL('', '', null,'');
		}
		
		return $query;
	}

}