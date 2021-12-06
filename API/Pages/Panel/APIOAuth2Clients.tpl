<%@ MasterClass="Application.API.Layouts.Main" Theme="Baculum-v2"%>
<com:TContent ID="Main">
	<header class="w3-container w3-block">
		<h5>
			<i class="fas fa-user-shield"></i> <%[ OAuth2 clients ]%>
		</h5>
	</header>
	<div class="w3-container">
		<a href="javascript:void(0)" class="w3-button w3-green w3-margin-bottom" onclick="oAPIOAuth2Clients.show_new_client_window(true);">
			<i class="fas fa-plus"></i> &nbsp;<%[ Add OAuth2 client ]%>
		</a>
		<table id="oauth2_client_list" class="w3-table w3-striped w3-hoverable w3-white w3-margin-bottom" style="width: 100%">
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
			dom: 'lBfrtip',
			stateSave: true,
			buttons: [
				'copy', 'csv', 'colvis'
			],
			columns: [
				{
					className: 'details-control',
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
					type: 'column'
				}
			},
			columnDefs: [{
				className: 'control',
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
			<header class="w3-container w3-teal">
				<span onclick="document.getElementById('new_oauth2_client_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
				<h2><%[ Add client ]%></h2>
			</header>
			<div class="w3-container w3-margin-left w3-margin-right w3-text-teal">
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
			<header class="w3-container w3-teal">
				<span onclick="document.getElementById('edit_oauth2_client_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
				<h2><%[ Edit client ]%></h2>
			</header>
			<div class="w3-container w3-margin-left w3-margin-right w3-text-teal">
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
	<com:TJuiDialog
		ID="APIOAuth2EditPopup"
		Options.Title="<%[ Edit OAuth2 client parameters ]%>"
		Options.AutoOpen="False"
		Options.Width="700px"
	>
		<com:TPanel DefaultButton="APIOAuth2SaveBtn">
		<div class="line">
			<div class="text"><com:TLabel ForControl="APIOAuth2ClientId" Text="<%[ OAuth2 Client ID: ]%>" /></div>
			<div class="field">
				<com:TActiveTextBox
					ID="APIOAuth2ClientId"
					CssClass="textbox"
					ReadOnly="true"
				/>
			</div>
		</div>
		<div class="line">
			<div class="text"><com:TLabel ForControl="APIOAuth2ClientSecret" Text="<%[ OAuth2 Client Secret: ]%>" /></div>
			<div class="field">
				<com:TActiveTextBox
					ID="APIOAuth2ClientSecret"
					CssClass="textbox"
					CausesValidation="false"
					MaxLength="50"
				/>
				<com:TRequiredFieldValidator
					CssClass="validator-block"
					Display="Dynamic"
					ControlCssClass="invalidate"
					ControlToValidate="APIOAuth2ClientSecret"
					ValidationGroup="APIOAuth2Edit"
					Text="<%[ Please enter Client Secret. ]%>"
				/>
				<com:TRegularExpressionValidator
					CssClass="validator-block"
					Display="Dynamic"
					ControlCssClass="invalidate"
					ControlToValidate="APIOAuth2ClientSecret"
					RegularExpression="<%=OAuth2::CLIENT_SECRET_PATTERN%>"
					ValidationGroup="APIOAuth2Edit"
					Text="<%[ Invalid Client Secret value. Client Secret may contain any character that is not a whitespace character. ]%>"
				/>
				<a href="javascript:void(0)" onclick="document.getElementById('<%=$this->APIOAuth2ClientSecret->ClientID%>').value = get_random_string('ABCDEFabcdef0123456789', 40); return false;"><%[ generate ]%></a>
			</div>
		</div>
		<div class="line">
			<div class="text"><com:TLabel ForControl="APIOAuth2RedirectURI" Text="<%[ OAuth2 Redirect URI (example: https://baculumgui:9095/web/redirect): ]%>" /></div>
			<div class="field">
				<com:TActiveTextBox
					ID="APIOAuth2RedirectURI"
					CssClass="textbox"
					CausesValidation="false"
				/>
				<com:TRequiredFieldValidator
					CssClass="validator-block"
					Display="Dynamic"
					ControlCssClass="invalidate"
					ControlToValidate="APIOAuth2RedirectURI"
					ValidationGroup="APIOAuth2Edit"
					Text="<%[ Please enter Redirect URI. ]%>"
				/>
			</div>
		</div>
		<div class="line">
			<div class="text"><com:TLabel ForControl="APIOAuth2Scope" Text="<%[ OAuth2 scopes (space separated): ]%>" /></div>
			<div class="field">
				<com:TActiveTextBox
					ID="APIOAuth2Scope"
					CssClass="textbox"
					CausesValidation="false"
					TextMode="MultiLine"
				/>
				<a href="javascript:void(0)" onclick="set_scopes('<%=$this->APIOAuth2Scope->ClientID%>'); return false;" style="vertical-align: top"><%[ set all scopes ]%></a>
				<com:TRequiredFieldValidator
					CssClass="validator-block"
					Display="Dynamic"
					ControlCssClass="invalidate"
					ControlToValidate="APIOAuth2Scope"
					ValidationGroup="APIOAuth2Edit"
					Text="<%[ Please enter OAuth2 scopes. ]%>"
				/>
			</div>
		</div>
		<div class="line">
			<div class="text"><com:TLabel ForControl="APIOAuth2BconsoleCfgPath" Text="<%[ Dedicated Bconsole config file path: ]%>" /></div>
			<div class="field">
				<com:TActiveTextBox
					ID="APIOAuth2BconsoleCfgPath"
					CssClass="textbox"
					CausesValidation="false"
				/> <%[ (optional) ]%>
			</div>
		</div>
		<div class="line">
			<div class="text"><com:TLabel ForControl="APIOAuth2Name" Text="<%[ Short name: ]%>" /></div>
			<div class="field">
				<com:TActiveTextBox
					ID="APIOAuth2Name"
					CssClass="textbox"
					CausesValidation="false"
				/> <%[ (optional) ]%>
			</div>
		</div>
		<div class="center">
			<com:BButton
				Text="<%[ Cancel ]%>"
				CausesValidation="false"
				Attributes.onclick="$('#<%=$this->APIOAuth2EditPopup->ClientID%>').dialog('close'); return false;"
			/>
			<com:BActiveButton
				ID="APIOAuth2SaveBtn"
				ValidationGroup="APIOAuth2Edit"
				OnCommand="TemplateControl.saveOAuth2Item"
				Text="<%[ Save ]%>"
			>
			</com:BActiveButton>
		</div>
		</com:TPanel>
	</com:TJuiDialog>
</com:TContent>
