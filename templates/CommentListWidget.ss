<% if Comments %>
	<table class="CommentList">
		<thead>
			<tr>
				<th>Comment</th>
				<th>Author</th>
				<th>Created</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<% control Comments %>
				<tr>
					<td>
						<a href="$Parent.Link#PageComment_$ID">$Comment.LimitCharacters(100)</a> 
						<% if IsSpam %><% else %><% if NeedsModeration %>(unmoderated)<% end_if %><% end_if %>
					</td>
					<td>$Name</td>
					<td>$Created.Ago</td>
					<td>
						<% if IsSpam %>
							<% if HamLink %><a href="$HamLink"><img src="cms/images/approvecomment.png" alt="Not Spam" title="Not Spam"/></a><% end_if %>
						<% else %>
							<% if NeedsModeration %>
								<% if ApproveLink %><a href="$ApproveLink"><img src="cms/images/approvecomment.png" alt="Approve" title="Approve"/></a><% end_if %>
								<% if SpamLink %><a href="$SpamLink"><img src="cms/images/declinecomment.png" alt="Spam" title="Spam"/></a><% end_if %>
							<% else %>
								<% if SpamLink %><a href="$SpamLink"><img src="cms/images/declinecomment.png" alt="Spam" title="Spam"/></a><% end_if %>
							<% end_if %>
						<% end_if %>
						<% if DeleteLink %><a href="$DeleteLink"><img src="cms/images/delete.gif" alt="Delete" title="Delete"/></a><% end_if %>
					</td>
				</tr>
			<% end_control %>
		</tbody>
	</table>
<% else %>
	<p>No posts to display.</p>
<% end_if %>