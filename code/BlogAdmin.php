<?php
/**
 * Creates a new tab for managing blogs/categories/tags
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package blogadmin
 */
class BlogAdmin extends LeftAndMain {

	static $url_segment = 'blog';
	
	static $url_rule = '/$Action/$ID/$OtherID';

	static $menu_priority = 5;

	static $menu_title = 'Blog';
	
	static $tree_class = 'BlogEntry';

	static $allowed_actions = array(
		'posts',
		'add',
		'addauthor',
		'tags',
		'categories',
		'authors',
		'ListForm',
		'CategoryForm',
		'AuthorForm',
		'TagsForm',
		'editpost',
		'editauthor',
		'deletepost',
		'save',
		'doSavePost',
		'doCancel',
		'doShowAddForm',
		'doDeleteSelected',
		'redirectToAddAuthorForm'
	);

	static $status_options = array(
		'Unpublished'	=> 'Unpublished',
		'Published'		=> 'Published',
	);
	
	
	/**
	 * set up the controller
	 */
	function init(){
		parent::init();
		Requirements::javascript(BLOGADMIN_DIR . '/javascript/BlogAdmin.js');
		Requirements::javascript(BLOGADMIN_DIR . '/javascript/BlogAdmin_CommentTableField.js');
		Requirements::block(CMS_DIR . '/javascript/CommentTableField.js');
		Requirements::css(BLOGADMIN_DIR . '/css/blogadmin.css');
		HtmlEditorField::include_js();	
		if (isset($_SESSION['BlogTags']) && isset($_GET['flush'])) unset($_SESSION['BlogTags']);
	}


	// !------- actions -------
	
	
	/**
	 * displays the dashboard screen
	 */
	function index(){
		Requirements::javascript(BLOGADMIN_DIR . '/javascript/BlogTableField.js');
		Requirements::css(BLOGADMIN_DIR . '/css/WidgetDashboard.css');
		return array(
			'EditForm' => $this->Dashboard(),
		);
	}


	/**
	 * displays a list of blog entries
	 */
	function posts(){
		return $this->getLastFormIn(
			$this->customise(array(
				'EditForm' => $this->ListForm()
			))->renderWith('BlogAdmin_right')
		);
	}
	

	/**
	 * deletes the blog post fully from both stage and live
	 */
	function deletepost($request) {
		$id = (int)$request->param('ID');
		$this->deleteEntry($id);
	}


	/**
	 * displays the edit form
	 */
	function editpost($request) {
		return $this->getLastFormIn(
			$this->customise(array(
				'EditForm' => $this->getEditForm((int)$request->param('ID'))
			))->renderWith('BlogAdmin_right')
		);
	}

	/**
	 * displays the author edit form
	 */
	function editauthor($request) {
		return $this->getLastFormIn(
			$this->customise(array(
				'EditForm' => $this->getAuthorEditForm((int)$request->param('ID'))
			))->renderWith('BlogAdmin_right')
		);
	}
	
	/**
	 * saves the post
	 */
	function save($data, $form, $request){
		//if this is an author, use saveAuthor
		if(isset($data['IsAuthor'])) return $this->saveAuthor($data, $form, $request);
		
		if (isset($data['ID'])){
			$entry = $this->currentRecord;
			// if it was already published and they changed the status
			// we need to unpublish it
			if ($data['Status'] != 'Published') {
				$SQL_id = (int)$data['ID'];
				$live = Versioned::get_one_by_stage('BlogEntry', 'Live', "\"SiteTree\".ID = '$SQL_id'");
				if ($live && $live->ID) {
					$live->deleteFromStage('Live');
					$live->flushCache();
				}
			}
		} else {
			// if we're creating a new post,
			// go ahead and save it so that there's an ID
			// this will allow the categories to save correctly
			$entry = new BlogEntry;
			$entry->writeToStage('Stage');
		}
		
		// load up the rest of the data
		$form->saveInto($entry);
		
		// try to save it
		try {
			// automatically handle some fields
			// is this the best spot for this?
			$entry->MenuTitle = $entry->Title;
			
			// save
			$entry->writeToStage('Stage');
			
			// published if needed
			if ($data['Status'] == 'Published') {
				$entry->publish('Stage','Live');
			}
		} catch(ValidationException $e) {
			FormResponse::add($e->getResult()->message(), 'bad');
			return FormResponse::respond();
		}
		
		// Behaviour switched on ajax.
		if(Director::is_ajax()) {
			// if we're just saving an existing item
			// we don't need to reload the whole form
			if (isset($data['ID'])) {
				FormResponse::add("statusMessage('Post has been saved.','good');");
			} else {
				FormResponse::add("
					statusMessage('Post has been created. Reloading...'); 
					\$('Form_EditForm').loadURLFromServer('admin/blog/editpost/{$entry->ID}');
				");
			}

			return FormResponse::respond();		
		} else {
			Director::redirectBack();
		}
	}
	
	function saveAuthor($data, $form, $request){
		if(isset($data['ID'])){
			$author = DataObject::get_by_id('Member',$data['ID']);
		}else{
			$author = new Member();
		}
		
		$author->BlogProfileURLSegment = isset($data['BlogProfileTitle']) ? SiteTree::GenerateURLSegment($data['BlogProfileTitle']) : null;		
		
		// load up the rest of the data
		$form->saveInto($author);
		
		// try to save it
		try {
			// automatically handle some fields
			// is this the best spot for this?
			
			$author->write();

		} catch(ValidationException $e) {
			FormResponse::add($e->getResult()->message(), 'bad');
			return FormResponse::respond();
		}
		
		// Behaviour switched on ajax.
		if(Director::is_ajax()) {
			// if we're just saving an existing item
			// we don't need to reload the whole form
			if (isset($data['ID'])) {
				FormResponse::add("statusMessage('Author has been saved.','good');");
			} else {
				FormResponse::add("
					statusMessage('Author has been created. Reloading...'); 
					\$('Form_EditForm').loadURLFromServer('admin/blog/editauthor/{$author->ID}');
				");
			}

			return FormResponse::respond();		
		} else {
			Director::redirectBack();
		}
	}

	
	
	function deleteSelected($data, $form){
		$num = 0;
		foreach ($data['Entries'] as $id) {
//			echo "id=$id\n";
			if ($this->deleteEntry($id)) $num++;
		}
		
		$msg = ($num == 1) ? "1 post was deleted." : "$num posts were deleted.";		
		FormResponse::add("
			statusMessage('$msg. Reloading...','good');
			\$('Form_EditForm').loadURLFromServer('admin/blog/posts');
		");
		return FormResponse::respond();
	}
	
	
	/**
	 * just sends them back to the list of posts
	 */
	function redirectToList($data, $form){
		FormResponse::add("statusMessage('loading...'); \$('Form_EditForm').loadURLFromServer('admin/blog/posts');");
		return FormResponse::respond();
	}
	
	/**
	 * just sends them back to the list of authors
	 */
	function redirectToAuthorsList($data, $form){
		FormResponse::add("statusMessage('loading...'); \$('Form_EditForm').loadURLFromServer('admin/blog/authors');");
		return FormResponse::respond();
	}


	/**
	 * just displays the add form
	 */
	function redirectToAddForm($data, $form){
		FormResponse::add("statusMessage('loading...'); \$('Form_EditForm').loadURLFromServer('admin/blog/add');");
		return FormResponse::respond();
	}
	
	/**
	 * just displays the addAuthor form
	 */
	function redirectToAddAuthorForm($data, $form){
		FormResponse::add("statusMessage('loading...'); \$('Form_EditForm').loadURLFromServer('admin/blog/addauthor');");
		return FormResponse::respond();
	}
	
	
	/**
	 * displays the form to add a new blog
	 */
	function add(){
		return $this->getLastFormIn(
			$this->customise(array(
				'EditForm' => $this->getEditForm()
			))->renderWith('BlogAdmin_right')
		);
	}

	/**
	 * displays the form to add a new author
	 */
	function addauthor(){
		return $this->getLastFormIn(
			$this->customise(array(
				'EditForm' => $this->getAuthorEditForm()
			))->renderWith('BlogAdmin_right')
		);
	}
	
	
	/**
	 * displays a list of tags and allows you to add/edit tags
	 */
	function tags(){
		return $this->getLastFormIn(
			$this->customise(array(
				'EditForm' => $this->TagsForm()
			))->renderWith('BlogAdmin_right')
		);
	}
	
	
	/**
	 * displays a list of categories and allows you to add/edit them
	 */
	function categories(){
		return $this->getLastFormIn(
			$this->customise(array(
				'EditForm' => $this->CategoryForm()
			))->renderWith('BlogAdmin_right')
		);
	}
	
	/**
	 * displays a list of authors and allows you to add/edit them
	 */
	function authors(){
		return $this->getLastFormIn(
			$this->customise(array(
				'EditForm' => $this->AuthorForm()
			))->renderWith('BlogAdmin_right')
		);
	}
	
	
	// !------- forms -------
	

	public function EditForm() {
		// routing hacks
		// !TODO - clean this up. this is a terrible mess
		if (isset($_REQUEST['IsDashboard'])) return $this->Dashboard();
		if (isset($_REQUEST['IsAuthor'])) return $this->getAuthorEditForm(isset($_REQUEST['ID']) ? $_REQUEST['ID'] : false);
		if (isset($_REQUEST['action_redirectToAddAuthorForm'])) return $this->AuthorForm();
		if (isset($_REQUEST['action_redirectToAddForm'])) return $this->ListForm();

		// Include JavaScript to ensure HtmlEditorField works.
		HtmlEditorField::include_js();

		if ($this->currentPageID() != 0) {
			$record = $this->currentPage();
			if(!$record) return false;
			if($record && !$record->canView()) return Security::permissionFailure($this);
		}

		return $this->getEditForm($this->currentPageID());
	}


	/**
	 * returns the edit form based on an id from any source
	 */
	function getEditForm($id=false){
		$entry = $this->currentRecord = $id ? DataObject::get_by_id('BlogEntry', $id) : singleton('BlogEntry');

		// start with our own default fields
		$fields = $this->getEntryFields($entry);
		
		// give a hook for the blog model to override the editing fields
		if ($entry->hasMethod('getBlogAdminFields')){
			$fields = $entry->getBlogAdminFields($fields);
		}
		
		// create the actions
		$actions = new FieldSet(
			$save	= new FormAction('save', 'Save'),
			$cancel	= new FormAction('redirectToList', 'Back')
		);
		
		// make a form
		$form = new Form($this, 'EditForm', $fields, $actions);
		
		// load up the data if needed
		if ($id) {
			if (!$_POST) {
				$form->loadDataFrom($entry);
			}
		} else {
			// this allows the UpdateURL js to correctly change the urlsegment
			// the first time the title is updated
			$form->loadDataFrom(array(
				'Title' 		=> 'New Post',
				'URLSegment' 	=> 'new-post',
				'Date' 			=> date('Y-m-d H:i:s'),
				'Status'		=> 'Published',
			));
		}
		
		return $form;
	}
	
	/**
	 * returns the author edit form based on an id from any source
	 */
	function getAuthorEditForm($id=false){
		$author = $this->currentRecord = $id ? DataObject::get_by_id('Member', $id) : singleton('Member');

		// start with our own default fields
		$fields = $this->getAuthorFields($author);
		
		// give a hook for the blog model to override the editing fields
		// not sure if we need this - tk
		/*
		if ($author->hasMethod('getAuthorAdminFields')){
			$fields = $author->getAuthorAdminFields($fields);
		}
		*/
				
		// create the actions
		$actions = new FieldSet(
			$save	= new FormAction('save', 'Save'),
			$cancel	= new FormAction('redirectToAuthorsList', 'Back')
		);
		
		// make a form
		$form = new Form($this, 'EditForm', $fields, $actions);
		
		// load up the data if needed
		if ($id) {
			if (!$_POST) {
				$form->loadDataFrom($author);
			}
		} else {
			// this allows the UpdateURL js to correctly change the urlsegment
			// the first time the title is updated
			$form->loadDataFrom(array(
				'Title' 		=> 'New Author',
				'URLSegment' 	=> 'new-author'
			));
		}

		return $form;
	}

	
	
	/**
	 * creates the list form for editing entries
	 */
	function ListForm(){
		if (self::use_categories()) {
			BlogExtensions::$category_list_format = '<a class="filterLink categoryFilter" href="admin/blog/ListForm/field/Entries?ctf[filter][CategoryID]=$ID">$Title</a>';
		}

		BlogExtensions::$tag_list_format = '<a class="filterLink tagFilter" href="admin/blog/ListForm/field/Entries?ctf[filter][Search]=$URL_Title">$XML_Title</a>';
	
		$form = new Form(
			$this, 'ListForm',
			new FieldSet(
				new TabSet("Root",
					new Tab("BlogPosts",
						$this->getFilterFields(),
						$table = new BlogTableField('Entries', isset($_REQUEST['ctf']['filter']) ? $_REQUEST['ctf']['filter'] : null)
					)
				)
			),
			new FieldSet(
				new FormAction('redirectToAddForm', 'Add New Post'),
				new FormAction('deleteSelected','Delete Selected')
			)
		);
		
		$form->disableSecurityToken();

		if (isset($_REQUEST['ctf']['filter'])) {
			// not sure if this is the best place for this?
			$table->paginationBaseLink = $this->Link() . 'ListForm/field/Entries?';
			foreach ($_REQUEST['ctf']['filter'] as $key => $val) {
				$table->paginationBaseLink .= 'ctf[filter][' . $key . ']=' . urlencode($val) . '&';
			}

			trim($table->paginationBaseLink, '&');
		}
				
		return $form;
	}
	
	
	function CategoryForm(){
		$holders = DataObject::get('BlogHolder');
		$form = new Form(
			$this, 'CategoryForm',
			new FieldSet(
				new TabSet("Root",
					new Tab("Categories",
						$table = new ComplexTableField(
							$this, 'Categories', 
							'BlogCategory',
							array(
								'Title'			=> 'Category Name',
								'URLSegment'	=> 'URL Segment',
								'EntriesCount'	=> 'Posts'
							),
							new FieldSet(
								new TextField('Title', 'Category Name'),
								new TextField('URLSegment', 'URL Segment'),
								new TextareaField('Content', 'Content'),
								new DropdownField('BlogID','Blog Holder',$holders->toDropDownMap())
							)
						)
					)
				)
			),
			new FieldSet()
		);
		
		$table->setPermissions(array('add','edit','delete'));

		return $form;
	}
	
	
	function TagsForm(){
		$form = new Form(
			$this, 'TagsForm',
			new FieldSet(
				new TabSet("Root",
					new Tab("Tags",
						$table = new ComplexTableField(
							$this, 'Tags', 
							'BlogTag',
							array(
								'Title' 		=> 'Tag Name',
								'EntriesCount'	=> 'Posts'
							),
							new FieldSet(
								new TextField('Title','Tag Name')
							)
						)
					)
				)
			),
			new FieldSet()
		);
		
		$table->setCustomSourceItems(singleton('BlogTag')->instance_get());
		$table->setPermissions(array('edit','delete'));
		
		return $form;
	}
	
	/**
	** This should probably be more like the normal member table
	** TODO - filter results to only show authors - not all members
	**/
	
	function AuthorForm(){		

		$actions = $this->getIsBlogAdmin()
			? new FieldSet(new FormAction('redirectToAddAuthorForm', 'Add New Author'))
			: new FieldSet();


		$form = new Form(
			$this,
			'AuthorForm',
			new FieldSet(
				new TabSet("Root",
					new Tab("Authors",
						$table = new AuthorTableField('Member', null)
					)
				)
			),
			$actions
		);
		
		$form->disableSecurityToken();		
		$table->setPermissions(array('add','edit','delete'));

		return $form;
	}

	
	/**
	 * returns a list of columns and the widgets for each one
	 */
	function Dashboard(){
		$defaults = array(
			array('RecentPostsWidget', 'RecentCommentsWidget'),
			array('RecentSpamWidget'),
		);
		
		$form = new WidgetDashboard($this, 'EditForm', 2, 'Blog Administration', $defaults);
		$form->setHTMLID('Form_EditForm');
		return $form;
	}
	
	
	
	// !------- static -------
	
	
	static function make_link($action, $id){
		return "/admin/" . self::$url_segment . "/{$action}/$id";
	}
	
	
	/**
	 * returns true of blogentries are set up with has_one(Member) authors
	 */
	static function use_member_authors(){
		return BlogExtensions::$enable_members_as_authors;
	}
	

	/**
	 * returns true of blogentries are set up with many-to-many categories
	 */
	static function use_categories(){
		return BlogExtensions::$enable_blog_categories;
	}


	/**
	 * returns true if author profile pages are turned on
	 * @static
	 * @return bool
	 */
	static function use_author_profiles(){
		return self::use_member_authors() && Member::currentUser()->hasDatabaseField('BlogProfileTitle');
	}

	
	// !------- general -------
	
	
	/**
	 * creates the kind of blog editing form we want, not
	 * 100% based on the default getCMSFields
	 */
	protected function getEntryFields($entry){
		// we're going to throw this away but use a few of the secondary tabs from it
		SiteTree::disableCMSFieldsExtensions();
		$old = $entry->getCMSFields();
		SiteTree::enableCMSFieldsExtensions();
		
		// build the correct author field
		if (self::use_member_authors()) {
			$authorField = new DropdownField(
				"AuthorMemberID", _t("BlogEntry.AU", "Author"), 
				Member::mapInGroups(BlogExtensions::$author_groups), 
				Member::currentUserID()
			);
		} else {
			$authorField = new TextField("Author", _t("BlogEntry.AU", "Author"), Member::currentUser()->FirstName);
		}
		
		// set up fields
		$fields = new FieldSet(
			$rootTab = new TabSet("Root",
				$contentTab = new Tab('Content',
					new LiteralField('', '<div class="left_column_fields">'),
					$titleField	= new TextField("Title", 'Title'),
					$mainField	= new HtmlEditorField("Content", 'Content', 20),
					new LiteralField('', '</div><div class="right_column_fields">'),
					$statusField = new DropdownField("Status", "Status", self::$status_options),
					$authorField,
					$dateField 	= new DatetimeField("Date", _t("BlogEntry.DT", "Date")),
					$tagsField	= new TextField("Tags", _t("BlogEntry.TS", "Tags (comma sep.)")),
					new LiteralField('', '</div>')
				),
				$old->fieldByName('Root.Content.Metadata'),
				$old->fieldByName('Root.Todo'),
				$old->fieldByName('Root.Access')
			)			
		);

		// add comments if needed
//		if ($entry->ID){
			$rootTab->push(
				new Tab('Comments', 
					$this->getCommentTable($entry->ID)
				)
			);
//		}

		// allow categories
		if (self::use_categories()) {
			$categoryList = DataObject::get('BlogCategory');
			$contentTab->insertAfter(new CheckboxSetField('Categories', 'Categories', $categoryList), 'Tags');
		}
			
		// allow non-wysiwyg mode
		if(!BlogEntry::$allow_wysiwyg_editing) {
			$codeparser = new BBCodeParser();
			$fields->removeFieldFromTab("Root.Content","Content");
			$fields->addFieldToTab("Root.Content", new TextareaField("Content", _t("BlogEntry.CN", "Content"), 20));
			$fields->addFieldToTab("Root.Content", new LiteralField("BBCodeHelper", "<div id='BBCode' class='field'>" .
							"<a  id=\"BBCodeHint\" target='new'>" . _t("BlogEntry.BBH", "BBCode help") . "</a>" .
							"<div id='BBTagsHolder' style='display:none;'>".$codeparser->useable_tagsHTML()."</div></div>"));
		}
	
		// configure some of the fields
		$dateField->getDateField()->setConfig('showcalendar', true);
		$dateField->getTimeField()->setConfig('showdropdown', true);
	
		// add a field for the id if needed
		if ($entry->ID){
			$fields->push(new HiddenField('ID', $entry->ID));
		}
		
		// add any other fields
		// @note I'm not sure if this is the best approach or not - seems like
		// it'd be better to have a simpler interface
		//$fields->addFieldToTab('Root.Metadata', $old->fieldByName('Root.Content.Main.MenuTitle'), 'MetaTagsHeader');		
		$fields->push(new HiddenField('MenuTitle','')); // this should keep the update url javascript happy
		
		$holders = DataObject::get('BlogHolder');
		if ($holders->Count() > 1) {
			$parentField = new DropdownField("ParentID", "Blog Holder", $holders->toDropDownMap());
		} else {
			$holder = $holders->First();
			$parentField = new HiddenField("ParentID", "ParentID", $holder ? $holder->ID : 0);
		}		
		$contentTab->insertBefore($parentField, 'Date');
		
		return $fields;
	}
	
	/**
	 * creates the author edit form
	 * fields change based on new vs edit
	 * allowing for future customization
	 */
	protected function getAuthorFields($author = false){
		// we're going to throw this away but use a few of the secondary tabs from it		
		// set up fields
		
		SiteTree::disableCMSFieldsExtensions();
		$fields = $author->getCMSFields();
		$fields->removeByName('Permissions');
		$fields->removeByName('Groups');
		$fields->removeByName('Locale');
		$fields->removeByName('DateFormat');
		$fields->removeByName('TimeFormat');
		//$old->removeByName('Password');
		$fields->removeByName('BlogEntries');
		$fields->removeByName('BlogProfilePhoto');
		$fields->removeByName('BlogProfileURLSegment');
		SiteTree::enableCMSFieldsExtensions();
		
		//return $old;
		
		$fields->addFieldToTab("Root.Main",new SimpleImageField('BlogProfilePhoto','Profile Photo'));

			//$fields->push(
			//new LiteralField('', '<div class="left_column_fields">'),
			//new TextField("FirstName", 'First Name'),
			//new TextField("Surname", 'Last Name'),
			//new TextField("BlogProfileTitle", 'Profile Title'),
			//new HTMLEditorField("BlogProfileContent", 'Content'),
			//new HTMLEditorField("BlogProfileSecondaryContent", 'Secondary Content'),
			//new SimpleImageField('BlogProfilePhoto','Profile Photo'),
			//new LiteralField('', '</div><div class="right_column_fields">'),
			//new LiteralField('', '</div>')
			//);
		
		if ($author->ID){
			if($author->ID != Member::CurrentUserID()) $fields->removeByName('Password');
		}
		
		// add a field for the id if needed
		if ($author->ID){
			$fields->push(new HiddenField('ID', 'ID', $author->ID));
		}

		$fields->push(new HiddenField('IsAuthor', 'IsAuthor', 1));
		return $fields;
	}

	/**
	 * returns an appropriate field for editing comments (used on dashboard and post editi form)
	 */
	protected function getCommentTable($entryID = 0){
		$title = "<h2>Comments for this post</h2>";
		$filter = '"ParentID" = ' . $entryID;

		$tableFields = array(
			"Name" => _t('CommentAdmin.AUTHOR', 'Author'),
			"Comment" => _t('CommentAdmin.COMMENT', 'Comment'),
			"Parent.Title" => _t('CommentAdmin.PAGE', 'Page'),
			"CommenterURL" => _t('CommentAdmin.COMMENTERURL', 'URL'),
			"Created" => _t('CommentAdmin.DATEPOSTED', 'Date Posted')
		);

		$popupFields = new FieldSet(
			new TextField('Name', _t('CommentAdmin.NAME', 'Name')),
			new TextField('CommenterURL', _t('CommentAdmin.COMMENTERURL', 'URL')),
			new TextareaField('Comment', _t('CommentAdmin.COMMENT', 'Comment'))
		);

		Object::useCustomClass('CommentTableField_Item', 'BlogAdmin_CommentTableFieldItem', true);
		$table = new CommentTableField($this, "Comments", "PageComment", 'unmoderated', $tableFields, $popupFields, array($filter), 'Created DESC');
		$table->itemClass = 'BlogAdmin_CommentTableFieldItem';
		$table->setParentClass(false);
		$table->setFieldCasting(array(
			'Created' => 'SSDatetime->Full'
		));

		return $table;	
	}
	
	
	/**
	 * returns a fieldgroup with filters
	 */
	protected function getFilterFields(){
		// authors
		if (self::use_member_authors()) {
			$authorField = new DropdownField("ctf[filter][AuthorMemberID]", '', 
				Member::mapInGroups(BlogExtensions::$author_groups),
				isset($_REQUEST['ctf']['filter']['AuthorMemberID']) ? $_REQUEST['ctf']['filter']['AuthorMemberID'] : '',
				null, 'All Authors');
		} else {
			$authorField = new TextField("ctf[filter][Author]", 
				isset($_REQUEST['ctf']['filter']['Author']) ? $_REQUEST['ctf']['filter']['Author'] : '', 
				'Author...');
		}

		// main field
		$filterGroup = new FieldGroup(
			new TextField('ctf[filter][Search]', 
				isset($_REQUEST['ctf']['filter']['Search']) ? $_REQUEST['ctf']['filter']['Search'] : '', 
				'Search...'),
			$authorField,
			new DropdownField('ctf[filter][Status]', 
				isset($_REQUEST['ctf']['filter']['Status']) ? $_REQUEST['ctf']['filter']['Status'] : '', 
				self::$status_options, '', null, 'All Posts'),
			new LiteralField('BlogFilterButton','<input type="submit" name="BlogFilterButton" value="Filter" id="BlogFilterButton"/>')
		);
		
		// category field
		if (self::use_categories()) {
			$cats = DataObject::get('BlogCategory');
			if ($cats) {
				$filterGroup->insertBefore(
					new DropdownField(
						'ctf[filter][CategoryID]', '', $cats->toDropDownMap(), 
						isset($_REQUEST['ctf']['filter']['CategoryID']) ? $_REQUEST['ctf']['filter']['CategoryID'] : '',
						null, 'All Categories'
					),
					'BlogFilterButton'
				);
			}
		}
		
		// blog field
		$blogs = DataObject::get('BlogHolder');
		if ($blogs && $blogs->Count() > 1) {
				$filterGroup->insertBefore(
					new DropdownField(
						'ctf[filter][ParentID]', '', $blogs->toDropDownMap(), 
						isset($_REQUEST['ctf']['filter']['ParentID']) ? $_REQUEST['ctf']['filter']['ParentID'] : '',
						null, 'All Blogs'
					),
					'BlogFilterButton'
				);
		}
		
		// configuration
		$filterGroup->addExtraClass('filterBox');

		return $filterGroup;
	}
	
	
	/**
	 * deletes a single entry from both stage and live sites
	 */
	protected function deleteEntry($id) {
		$entry = DataObject::get_by_id('BlogEntry', $id);
		if (!$entry->canDelete()) {
			return false;
		}

		$entry->deleteFromStage('Live');
		$entry->flushCache();

		$entry = DataObject::get_by_id('BlogEntry', $id);
		$entry->deleteFromStage('Stage');
		
		return true;
	}
	
	
	//! ------- for template -------
	
	function getUseCategories(){
		return self::use_categories();
	}

	function getUseAuthorProfiles(){
		return self::use_author_profiles();
	}

	function getIsBlogAdmin(){
		return (Permission::check('BLOGMANAGEMENT') || Permission::check('ADMIN'));
	}

}



/**
 * Single row of a {@link CommentTableField}
 * @package cms
 * @subpackage comments
 */
class BlogAdmin_CommentTableFieldItem extends ComplexTableField_Item {
	function HasSpamButton() {
		return !$this->item->IsSpam;
	}
	
	function HasApproveButton() {
		return $this->item->NeedsModeration;
	}
	
	function HasHamButton() {
		return $this->item->IsSpam;
	}
}

