<div class="typography">

	<% if Level(1) %>
	  	<% include BreadCrumbs %>
	<% end_if %>

	<% if Menu(2) %>
		<div id="PageContent">
			<div id="Main">
	
	<% else %>
		<div id="Content">
	<% end_if %>
	
	<% if Profile %>
		<% control Profile %>
	
				<h2>$BlogProfileTitle</h2>
				
				<div id="MainContent">
					$BlogProfileContent
				</div>
				<div id="SecondaryContent">
				<% if Photo %>
					<% control Photo.setWidth(100) %>
						<span class="profilePhoto"><img src="$URL" ></span>
					<% end_control %>
				<% else %>
						<span class="profilePhoto"><img src="/blogadmin/images/profile.jpg" width="100" ></span>
				<% end_if %>
					<div class="clear"></div>

					$BlogProfileSecondaryContent			
				</div>
		<% end_control %>
	<% end_if %>
				
				<div class="clear"></div>
				$Form
				$PageComments
	
			</div>
	
	<% if Menu(2) %>
	
			<div id="Sidebar">
				<% if Menu(2) %>
					<% include SideBar %>
				<% end_if %>
			</div>
			
			<div class="clear"></div>
			
		</div>
	
			<div id="PageContentBottom"></div>

	</div>
	
	<% end_if %>
</div>