<h2><% _t('MANAGE_BLOG', 'Manage Blog') %></h2>

<div id="treepanes">
	<div id="sitetree_holder">
		<ul id="sitetree" class="tree unformatted">
			<li id="$ID" class="Root"><a><strong><% _t('POSTS', 'Posts') %></strong></a>
				<ul>
					<li id="record-posts" <% if Action=posts %>class="current"<% end_if %>>
						<a href="$baseURL/admin/blog/posts" title="Edit Posts">Edit Posts</a>
					</li>

					<li id="record-add" <% if Action=add %>class="current"<% end_if %>>
						<a href="$baseURL/admin/blog/add" title="Add New Post">Add New Post</a>
					</li>

					<% if IsBlogAdmin %>
						<li id="record-tags" <% if Action=tags %>class="current"<% end_if %>>
							<a href="$baseURL/admin/blog/tags" title="Manage Tags">Manage Tags</a>
						</li>

						<% if UseCategories %>
							<li id="record-categories" <% if Action=categories %>class="current"<% end_if %>>
								<a href="$baseURL/admin/blog/categories" title="Manage Categories">Manage Categories</a>
							</li>
						<% end_if %>
					<% end_if %>

					<% if UseAuthorProfiles %>
						<li id="record-authors" <% if Action=authors %>class="current"<% end_if %>>
							<a href="$baseURL/admin/blog/authors" title="Manage Authors">Manage Authors</a>
						</li>
					<% end_if %>
					
				</ul>
				<br/>
			</li>
		</ul>

<!--
<br/><br/>
<i>Other things could go here as well, eventually, in the same tree format as above:</i><br/>
limited comment mgmt<br/>
limited media mgmt<br/>
limited settings (widgets,etc)<br/>
-->

	</div>
</div>

