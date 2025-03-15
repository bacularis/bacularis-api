<script>
var oSudoConfig = {
	bin_fields: {},
	bin_opts: {},
	bin_ownership: {},
	ids: {
		dialog: '<%=$this->SudoConfigPopup->ClientID%>'
	},
	def_user: 'root',
	def_group: '',
	set_bin_fields: function(bin_fields) {
		this.bin_fields = bin_fields;
	},
	set_bin_ownership: function(bin_ownership) {
		this.bin_ownership = bin_ownership;
	},
	set_bin_opts: function(bin_opts) {
		this.bin_opts = bin_opts;
	},
	get_config: function(type) {
		var val, pre;
		var cfg = '';
		var users = ['apache_nginx_lighttpd', 'www-data', 'wwwrun'];
		var fields = this.bin_fields.hasOwnProperty(type) ? this.bin_fields[type] : [];
		const runas = this.get_runas_user_group(type);
		for (var i = 0; i < users.length; i++) {
			var pre = document.getElementById('sudo_config_' + users[i].replace(/-/g, '_'));
			if (users[i] == 'apache_nginx_lighttpd') {
				// For CentOS, RHEL and others by default for PHP-FPM is set apache user.
				users[i] = 'apache';
			}
			pre.textContent = 'Defaults:' + users[i] + ' !requiretty' + "\n";
			for (var j = 0; j < fields.length; j++) {
				val = document.getElementById(fields[j]).value.trim();
				if (this.bin_opts.hasOwnProperty(type)) {
					if (this.bin_opts[type].hasOwnProperty('base_path') && this.bin_opts[type].base_path) {
						val = val.split(' ').shift(); // NOTE: It will not work with paths containing spaces
					}
				}
				if (val) {
					pre.textContent += users[i] + ' ALL = (' + runas + ') NOPASSWD: ' + val + "\n";
				}
			}
		}
		$('#' + this.ids.dialog).dialog('open');
	},
	get_runas_user_group: function(type) {
		let user = this.def_user;
		let group = this.def_group;
		let us, gr;
		if (this.bin_ownership.hasOwnProperty(type)) {
			if (this.bin_ownership[type].hasOwnProperty('user')) {
				us = document.getElementById(this.bin_ownership[type].user).value.trim();
				if (us) {
					user = us;
				}
			}
			if (this.bin_ownership[type].hasOwnProperty('group')) {
				gr = document.getElementById(this.bin_ownership[type].group).value.trim();
				if (gr) {
					group = gr;
				}
			}
		}
		const runas = [];
		if (user) {
			runas.push(user);
		}
		if (group) {
			runas.push(group);
		}
		let ret = '';
		if (!us && group) {
			ret = ':' + group;
		} else {
			ret = runas.join(' : ');
		}
		return ret;
	}
};
</script>
<com:TJuiDialog
	ID="SudoConfigPopup"
	Options.title="<%[ Sudo configuration ]%>"
	Options.autoOpen="false",
	Options.minWidth="820"
	Options.minHeight="200"
>
	<p><%[ Please copy appropriate sudo configuration and put it to a new sudoers.d file for example /etc/sudoers.d/bacularis ]%></p>
	<p><strong><%[ Note ]%>:</strong> <%[ Please use visudo to add this configuration, otherwise please do remember to add empty line at the end of file. ]%>
	<p><%[ Example sudo configuration for Apache, Nginx and Lighttpd web servers with default PHP-FPM configuration: ]%></p>
	<h4><%[ RHEL, CentOS, Fedora, Rocky Linux, AlmaLinux, Oracle Linux and others ]%></h4>
	<com:Bacularis.Common.Portlets.CopyButton ID="Copy1" TextId="sudo_config_apache_nginx_lighttpd" />
	<pre id="sudo_config_apache_nginx_lighttpd" class="w3-code" style="margin-top: 4px !important"></pre>
	<h4><%[ Debian, Ubuntu and others ]%></h4>
	<com:Bacularis.Common.Portlets.CopyButton ID="Copy2" TextId="sudo_config_www_data" />
	<pre id="sudo_config_www_data" class="w3-code" style="margin-top: 4px !important"></pre>
	<h4><%[ SLES, openSUSE and others ]%></h4>
	<com:Bacularis.Common.Portlets.CopyButton ID="Copy3" TextId="sudo_config_wwwrun" />
	<pre id="sudo_config_wwwrun" class="w3-code" style="margin-top: 4px !important"></pre>
</com:TJuiDialog>
