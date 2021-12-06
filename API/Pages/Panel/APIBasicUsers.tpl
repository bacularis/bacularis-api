<%@ MasterClass="Application.API.Layouts.Main" Theme="Baculum-v2"%>
<com:TContent ID="Main">
	<header class="w3-container w3-block">
		<h5>
			<i class="fas fa-users"></i> <%[ Basic users ]%>
		</h5>
	</header>
	<div class="w3-container">
		<a href="javascript:void(0)" class="w3-button w3-green w3-margin-bottom" onclick="oAPIBasicUsers.show_new_user_window(true);">
			<i class="fas fa-plus"></i> &nbsp;<%[ Add user ]%>
		</a>
		<table id="basic_user_list" class="w3-table w3-striped w3-hoverable w3-white w3-margin-bottom" style="width: 100%">
			<thead>
				<tr>
					<th></th>
					<th><%[ Username ]%></th>
					<th><%[ Dedicated Bconsole config ]%></th>
					<th><%[ Actions ]%></th>
				</tr>
			</thead>
			<tbody id="basic_user_list_body"></tbody>
			<tfoot>
				<tr>
					<th></th>
					<th><%[ Username ]%></th>
					<th><%[ Dedicated Bconsole config ]%></th>
					<th><%[ Actions ]%></th>
				</tr>
			</tfoot>
		</table>
	</div>
<script>
var oBasicUserList = {
	ids: {
		basic_user_list: 'basic_user_list',
		basic_user_list_body: 'basic_user_list_body'
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
		this.table = $('#' + this.ids.basic_user_list).DataTable({
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
				{data: 'username'},
				{
					data: 'bconsole_cfg_path',
					render: function(data, type, row) {
						var ret;
						if (type == 'display') {
							ret = '';
							if (data) {
								var check = document.createElement('I');
								check.className = 'fas fa-check';
								ret = check.outerHTML;
							}
						} else {
							ret = data;
						}
						return ret;
					}
				},
				{
					data: 'username',
					render: function(data, type, row) {
						var span = document.createElement('SPAN');
						span.className = 'w3-right';

						var edit_btn = document.createElement('BUTTON');
						edit_btn.className = 'w3-button w3-green';
						edit_btn.type = 'button';
						var i = document.createElement('I');
						i.className = 'fas fa-edit';
						var label = document.createTextNode(' <%[ Edit ]%>');
						edit_btn.appendChild(i);
						edit_btn.innerHTML += '&nbsp';
						edit_btn.appendChild(label);
						edit_btn.setAttribute('onclick', 'oAPIBasicUsers.edit_user("' + data + '")');

						span.appendChild(edit_btn);

						if (this.data.length > 1) {
							var del_btn = document.createElement('BUTTON');
							del_btn.className = 'w3-button w3-red w3-margin-left';
							del_btn.type = 'button';
							var i = document.createElement('I');
							i.className = 'fas fa-trash-alt';
							var label = document.createTextNode(' <%[ Delete ]%>');
							del_btn.appendChild(i);
							del_btn.innerHTML += '&nbsp';
							del_btn.appendChild(label);
							del_btn.setAttribute('onclick', 'oAPIBasicUsers.delete_user("' + data + '")');

							span.appendChild(del_btn);
						}
						return span.outerHTML;
					}.bind(this)
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
				targets: [ 2, 3 ]
			}],
			order: [1, 'asc'],
		});
	},
	get_user_props: function(username) {
		var props = {};
		for (var i = 0; i < this.data.length; i++) {
			if (this.data[i].username === username) {
				props = this.data[i];
				break;
			}
		}
		return props;
	}
};
</script>
	<div id="new_basic_user_window" class="w3-modal">
		<div class="w3-modal-content w3-animate-top w3-card-4">
			<header class="w3-container w3-teal">
				<span onclick="document.getElementById('new_basic_user_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
				<h2><%[ Add user ]%></h2>
			</header>
			<div class="w3-container w3-margin-left w3-margin-right w3-text-teal">
				<com:Application.Common.Portlets.NewAuthClient
					ID="NewBasicClient"
					Mode="add"
					AuthType="basic"
					OnSuccess="loadBasicUsers"
					OnCancel="cancelBasicUserWindow"
				/>
			</div>
		</div>
	</div>
	<div id="edit_basic_user_window" class="w3-modal">
		<div class="w3-modal-content w3-animate-top w3-card-4">
			<header class="w3-container w3-teal">
				<span onclick="document.getElementById('edit_basic_user_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
				<h2><%[ Edit user ]%></h2>
			</header>
			<div class="w3-container w3-margin-left w3-margin-right w3-text-teal">
				<com:Application.Common.Portlets.NewAuthClient
					ID="EditBasicClient"
					Mode="edit"
					AuthType="basic"
					OnSuccess="loadBasicUsers"
					OnCancel="cancelBasicUserWindow"
				/>
			</div>
		</div>
	</div>
<com:TCallback ID="LoadUsers" OnCallback="loadBasicUsers" />
<com:TCallback ID="DeleteUser" OnCallback="deleteBasicUser" />
<script>
var oAPIBasicUsers = {
	ids: {
		new_user_window: 'new_basic_user_window',
		new_basic_user: '<%=$this->NewBasicClient->APIBasicLogin->ClientID%>',
		edit_user_window: 'edit_basic_user_window',
		edit_basic_pwd: '<%=$this->EditBasicClient->APIBasicPassword->ClientID%>'
	},
	new_obj: <%=$this->NewBasicClient->ClientID%>oNewAuthClient,
	edit_obj: <%=$this->EditBasicClient->ClientID%>oNewAuthClient,
	init: function() {
		this.load_basic_users();
	},
	show_new_user_window: function(show) {
		oAPIBasicUsers.new_obj.hide_errors();
		oAPIBasicUsers.new_obj.clear_basic_fields();
		var win = document.getElementById(oAPIBasicUsers.ids.new_user_window);
		if (show) {
			win.style.display = 'block';
		} else {
			win.style.display = 'none';
		}
		document.getElementById(oAPIBasicUsers.ids.new_basic_user).focus();
	},
	show_edit_user_window: function(show) {
		oAPIBasicUsers.edit_obj.hide_errors();
		var win = document.getElementById(oAPIBasicUsers.ids.edit_user_window);
		if (show) {
			win.style.display = 'block';
		} else {
			win.style.display = 'none';
		}
		document.getElementById(oAPIBasicUsers.ids.edit_basic_pwd).focus();
	},
	load_basic_users: function() {
		var cb = <%=$this->LoadUsers->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_basic_users_cb: function(users) {
		oBasicUserList.data = users;
		oBasicUserList.init();
	},
	edit_user: function(username) {
		this.edit_obj.clear_basic_fields();
		var props = oBasicUserList.get_user_props(username);
		this.edit_obj.set_basic_props(props);
		this.show_edit_user_window(true);
	},
	delete_user: function(username) {
		if (!confirm('<%[ Are you sure? ]%>')) {
			return false;
		}
		var cb = <%=$this->DeleteUser->ActiveControl->Javascript%>;
		cb.setCallbackParameter(username);
		cb.dispatch();
	}
};
$(function() {
	oAPIBasicUsers.init();
});
</script>
</com:TContent>
