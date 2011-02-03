<div id="form_actions_right" class="ajaxActions"></div>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin/blog/EditForm" method="post" enctype="multipart/form-data">
		<h1>Blog Dashboard</h1>
		$Dashboard
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
