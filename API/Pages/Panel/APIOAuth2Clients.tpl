<%@ MasterClass="Bacularis\API\Layouts\Main" Theme="Baculum-v2"%>
<com:TContent ID="Main">
	<header class="w3-container w3-block">
		<h5>
			<i class="fas fa-user-shield"></i> <%[ OAuth2 clients ]%>
		</h5>
	</header>
	<div class="w3-container">
		<a href="javascript:void(0)" class="w3-button w3-green w3-margin-bottom" onclick="oAPIOAuth2Clients.new_client();">
			<i class="fas fa-plus"></i> &nbsp;<%[ Add OAuth2 client ]%>
		</a>
		<table id="oauth2_client_list" class="display w3-table w3-striped w3-hoverable w3-margin-bottom" style="width: 100%">
			<thead>
				<tr>
					<th></th>
					<th><%[ Name ]%></th>
					<th><%[ Client ID ]%></th>
					<th><%[ Redirect URI ]%></th>
					<th><%[ Actions ]%></th>
				</tr>
			</thead>
			<tbody id="oauth2_client_list_body"></tbody>
			<tfoot>
				<tr>
					<th></th>
					<th><%[ Name ]%></th>
					<th><%[ Client ID ]%></th>
					<th><%[ Redirect URI ]%></th>
					<th><%[ Actions ]%></th>
				</tr>
			</tfoot>
		</table>
	</div>
<script>
var oOAuth2ClientList = {
	ids: {
		oauth2_client_list: 'oauth2_client_list',
		oauth2_client_list_body: 'oauth2_client_list_body'
	},
	table: null,
	data: [],
	init: function() {
		if (!this.table) {
			this.set_table();
		} else {
			var page = this.table.page();
			this.table.clear().rows.add(this.data).draw();
			this.table.page(page).draw(false);
		}
	},
	set_table: function() {
		this.table = $('#' + this.ids.oauth2_client_list).DataTable({
			data: this.data,
			deferRender: true,
			fixedHeader: {
				header: true,
				headerOffset: $('#main_top_bar').height()
			},
			layout: {
				topStart: [
					{
						pageLength: {}
					},
					{
						buttons: ['copy', 'csv', 'colvis']
					}
				],
				topEnd: [
					'search'
				],
				bottomStart: [
					'info'
				],
				bottomEnd: [
					'paging'
				]
			},
			stateSave: true,
			columns: [
				{
					orderable: false,
					data: null,
					defaultContent: '<button type="button" class="w3-button w3-blue"><i class="fa fa-angle-down"></i></button>'
				},
				{data: 'name'},
				{data: 'client_id'},
				{data: 'redirect_uri'},
				{
					data: 'client_id',
					render: function(data, type, row) {
						var span = document.createElement('SPAN');
						span.className = 'w3-right';

						var chpwd_btn = document.createElement('BUTTON');
						chpwd_btn.className = 'w3-button w3-green';
						chpwd_btn.type = 'button';
						var i = document.createElement('I');
						i.className = 'fas fa-edit';
						var label = document.createTextNode(' <%[ Edit ]%>');
						chpwd_btn.appendChild(i);
						chpwd_btn.innerHTML += '&nbsp';
						chpwd_btn.appendChild(label);
						chpwd_btn.setAttribute('onclick', 'oAPIOAuth2Clients.edit_client("' + data + '")');

						var del_btn = document.createElement('BUTTON');
						del_btn.className = 'w3-button w3-red w3-margin-left';
						del_btn.type = 'button';
						var i = document.createElement('I');
						i.className = 'fas fa-trash-alt';
						var label = document.createTextNode(' <%[ Delete ]%>');
						del_btn.appendChild(i);
						del_btn.innerHTML += '&nbsp';
						del_btn.appendChild(label);
						del_btn.setAttribute('onclick', 'oAPIOAuth2Clients.delete_client("' + data + '")');

						span.appendChild(chpwd_btn);
						span.appendChild(del_btn);

						return span.outerHTML;
					}
				}
			],
			responsive: {
				details: {
					type: 'column',
					display: DataTable.Responsive.display.childRow
				}
			},
			columnDefs: [{
				className: 'dtr-control',
				orderable: false,
				targets: 0
			},
			{
				className: "dt-center",
				targets: [ 4 ]
			}],
			order: [1, 'asc'],
		});
	}
};
</script>
	<div id="new_oauth2_client_window" class="w3-modal">
		<div class="w3-modal-content w3-animate-top w3-card-4">
			<header class="w3-container w3-green">
				<span onclick="document.getElementById('new_oauth2_client_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
				<h2><%[ Add client ]%></h2>
			</header>
			<div class="w3-container w3-margin-left w3-margin-right">
				<com:Application.Common.Portlets.NewAuthClient
					ID="NewOAuth2Client"
					Mode="add"
					AuthType="oauth2"
					OnSuccess="loadOAuth2Clients"
					OnCancel="cancelOAuth2ClientWindow"
				/>
			</div>
		</div>
	</div>
	<div id="edit_oauth2_client_window" class="w3-modal">
		<div class="w3-modal-content w3-animate-top w3-card-4">
			<header class="w3-container w3-green">
				<span onclick="document.getElementById('edit_oauth2_client_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
				<h2><%[ Edit client ]%></h2>
			</header>
			<div class="w3-container w3-margin-left w3-margin-right">
				<com:Application.Common.Portlets.NewAuthClient
					ID="EditOAuth2Client"
					Mode="edit"
					AuthType="oauth2"
					OnSuccess="loadOAuth2Clients"
					OnCancel="cancelOAuth2ClientWindow"
				/>
			</div>
		</div>
	</div>
<com:TCallback ID="LoadClients" OnCallback="loadOAuth2Clients" />
<com:TCallback ID="LoadNewClient" OnCallback="loadNewOAuth2Client" />
<com:TCallback ID="DeleteClient" OnCallback="deleteOAuth2Client" />
<script>
var oAPIOAuth2Clients = {
	ids: {
		new_user_window: 'new_oauth2_client_window',
		new_oauth2_client_id: '<%=$this->NewOAuth2Client->APIOAuth2ClientId->ClientID%>',
		edit_user_window: 'edit_oauth2_client_window',
		edit_oauth2_client_secret: '<%=$this->EditOAuth2Client->APIOAuth2ClientSecret->ClientID%>'
	},
	new_obj: <%=$this->NewOAuth2Client->ClientID%>oNewAuthClient,
	edit_obj: <%=$this->EditOAuth2Client->ClientID%>oNewAuthClient,
	init: function() {
		this.load_oauth2_clients();
	},
	show_new_client_window: function(show) {
		oAPIOAuth2Clients.new_obj.hide_errors();
		oAPIOAuth2Clients.new_obj.clear_oauth2_fields();
		var win = document.getElementById(oAPIOAuth2Clients.ids.new_user_window);
		if (show) {
			win.style.display = 'block';
		} else {
			win.style.display = 'none';
		}
		document.getElementById(oAPIOAuth2Clients.ids.new_oauth2_client_id).focus();
	},
	show_edit_client_window: function(show) {
		oAPIOAuth2Clients.edit_obj.hide_errors();
		var win = document.getElementById(oAPIOAuth2Clients.ids.edit_user_window);
		if (show) {
			win.style.display = 'block';
		} else {
			win.style.display = 'none';
		}
		document.getElementById(oAPIOAuth2Clients.ids.edit_oauth2_client_secret).focus();
	},
	load_oauth2_clients: function() {
		var cb = <%=$this->LoadClients->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_oauth2_clients_cb: function(clients) {
		oOAuth2ClientList.data = clients;
		oOAuth2ClientList.init();
	},
	get_client_props: function(client_id) {
		var clients_len = oOAuth2ClientList.data.length;
		var client = {};
		for (var i = 0; i < clients_len; i++) {
			if (oOAuth2ClientList.data[i].client_id === client_id) {
				client = oOAuth2ClientList.data[i];
				break;
			}
		}
		return client;
	},
	new_client: function() {
		const cb = <%=$this->LoadNewClient->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	new_client_cb: function(props) {
		oAPIOAuth2Clients.new_obj.clear_oauth2_fields();
		oAPIOAuth2Clients.new_obj.set_oauth2_props(props);
		oAPIOAuth2Clients.show_new_client_window(true);
	},
	edit_client: function(client_id) {
		this.edit_obj.clear_oauth2_fields();
		var props = this.get_client_props(client_id);
		this.edit_obj.set_oauth2_props(props);
		this.show_edit_client_window(true);
	},
	delete_client: function(username) {
		if (!confirm('<%[ Are you sure? ]%>')) {
			return false;
		}
		var cb = <%=$this->DeleteClient->ActiveControl->Javascript%>;
		cb.setCallbackParameter(username);
		cb.dispatch();
	}
};
$(function() {
	oAPIOAuth2Clients.init();
});
</script>
</com:TContent>
