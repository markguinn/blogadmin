Silverstripe Blog Admin Module
==============================

Version:      0.1.0 alpha
Author:       Mark Guinn <mark@adaircreative.com>
Contributors: Tyler Kidd <tyler@adaircreative.com>


Requirements
------------
Silverstripe 2.4.0+
Blog 0.3+


Installation
------------
The basic admin module works out of the box. The module includes some
additional extensions to complement the blog and make it work more like
other blogging platforms. To install all the extensions add the following
to _config.php:

[code]
// some optional changes to core blog functionality
BlogExtensions::$enable_blog_categories = true;
BlogExtensions::$enable_members_as_authors = true;
Object::add_extension('BlogEntry', 'BlogExtensions');

// required for author profile pages
Object::add_extension('Member', 'BlogMemberExtensions');

// required for frontend stuff such as listing all posts in a category
Object::add_extension('BlogTree_Controller', 'BlogTreeExtensions');
[/code]


Optional Core Changes
---------------------

*Remove BlogEntry's From Normal CMS Visibility*
The following will cause BlogEntry pages not to show up in the tree under
the Pages tab. In cms/code/CMSMain.php, find the SiteTreeAsUL function,
around line 160, change the last line to:

[code]
		return $this->getSiteTreeFor("SiteTree", null, null, null, create_function('$n', 'return $n->ClassName != "BlogEntry";'));
[/code]


Extensions To Blog Module
-------------------------
The following extensions are optionally integrated into the core blog
module functionality.

* _Many-to-many Categories_ - Out of the box you can use BlogTree and
  BlogHolder to create a category structure, but this allows you for
  a totally separate structure where a post can be in many categories.
  This structure works side by side with the BlogTree/BlogHolder stuff.
 
* _Members as Authors_ - By default a blog post simply has a string
  field for the author's name. This changes that so that each post
  has assigned a Member as it's author. Admin users can set the author
  to any valid user. For everyone else the author is their own account.
  
* _Author Profile Pages_ - Extensions to the member object that allow
  you to store additional information about authors, as well as making
  them available at something like: /blog/profiles/author-name.

* _Frontend Extensions_ - Integrates the above extensions with the
  frontend blog stuff.

