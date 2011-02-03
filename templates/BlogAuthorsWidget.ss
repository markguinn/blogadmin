<ul class="blog-authors">
	<% control Authors %>
		<li>
			<% if BlogProfilePhoto %>
				<% control BlogProfilePhoto.SetWidth(100) %>
					<img src="$URL" alt="Profile Photo" class="light_border" />
				<% end_control %>
			<% end_if %>
			
			<% if BlogProfileLink %>
				<a href="$BlogProfileLink">$Title</a>
			<% else_if EntriesLink %>
				<a href="$EntriesLink">$Title</a>
			<% else %>
				$FirstName $Surname
			<% end_if %>
			
			<span class="secondary-copy">$BlogProfileSecondaryContent</span>
		</li>
	<% end_control %>
</ul>
