<% if Entries %>
	<table class="BlogEntryList">
		<thead>
			<tr>
				<th>Title</th>
				<th>Author</th>
				<th>Created</th>
				<th><img src="blogadmin/images/comments.png" alt="Comments" title="Comments"/></th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<% control Entries %>
				<tr>
					<td><a href="$Link">$Title</a> <% if Status == Published %><% else %>(draft)<% end_if %></td>
					<td>$Author</td>
					<td>$Created.Ago</td>
					<td>$Comments.TotalItems</td>
					<td>
						<a class="editbloglink" href="admin/blog/editpost/$ID"><img src="cms/images/edit.gif" alt="Edit" title="Edit"/></a>
						<a class="deletelink" href="admin/blog/deletepost/$ID"><img src="cms/images/delete.gif" alt="Delete" title="Delete"/></a>
					</td>
				</tr>
			<% end_control %>
		</tbody>
	</table>
<% else %>
	<p>No posts to display.</p>
<% end_if %>