<form $FormAttributes>
	<% if DashboardTitle %>
		<h1>$DashboardTitle</h1>
	<% end_if %>
	
	<div class="WidgetDashboard" data-saveurl="$SaveURL">
		<% control Columns %>
			<div class="WidgetDashboardColumn" id="column_$Column">
				<% control WidgetControllers %>
					<div class="Widget" id="widget_$ID">$WidgetHolder</div>
				<% end_control %>
			</div>
		<% end_control %>
	</div>

	<% control Fields %>
		$FieldHolder
	<% end_control %>

	<% if Actions %>
		<div class="Actions" style="display:none">
			<% control Actions %>
				$Field
			<% end_control %>
		</div>
	<% end_if %>
</form>
