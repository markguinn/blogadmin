<div class="typography">
	<% include BreadCrumbs %>
	
	<h2>$Title</h2>
		
	<% if Photo %>
		<% control Photo.setWidth(100) %>
			<img alt="$Top.Title" src="$URL" align="left" />
		<% end_control %>
	<% end_if %>
	
	$Content
		
	<% if SecondaryContent %>
		<h3>Additional Information</h3>
		$SecondaryContent
	<% end_if %>

	$Form	
	$PageComments
</div>
