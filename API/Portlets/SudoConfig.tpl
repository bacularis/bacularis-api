<script>
var oSudoConfig = {
	bin_fields: {},
	bin_opts: {},
	ids: {
		dialog: '<%=$this->SudoConfigPopup->ClientID%>'
	},
	set_bin_fields: function(bin_fields) {
		this.bin_fields = bin_fields;
	},
	set_bin_opts: function(bin_opts) {
		this.bin_opts = bin_opts;
	},
	get_config: function(type) {
		var val, pre;
		var cfg = '';
		var users = ['apache', 'lighttpd', 'www-data'];
		var fields = this.bin_fields.hasOwnProperty(type) ? this.bin_fields[type] : [];
		for (var i = 0; i < users.length; i++) {
			var pre = document.getElementById('sudo_config_' + users[i].replace(/-/g, '_'));
			pre.textContent = 'Defaults:' + users[i] + ' !requiretty' + "\n";
			for (var j = 0; j < fields.length; j++) {
				val = document.getElementById(fields[j]).value.trim();
				if (this.bin_opts.hasOwnProperty(type)) {
					if (this.bin_opts[type].hasOwnProperty('base_path') && this.bin_opts[type].base_path) {
						val = val.split(' ').shift(); // NOTE: It will not work with paths containing spaces
					}
				}
				if (val) {
					pre.textContent += users[i] + ' ALL = (root) NOPASSWD: ' + val + "\n";
				}
			}
		}
		$('#' + this.ids.dialog).dialog('open');
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
	<p><%[ Please copy appropriate sudo configuration and put it to a new sudoers.d file for example /etc/sudoers.d/baculum-api ]%></p>
	<p><strong><%[ Note ]%></strong> <%[ Please use visudo to add this configuration, otherwise please do remember to add empty line at the end of file. ]%>
	<p><%[ Example sudo configuration for Apache web server user (RHEL, CentOS and others): ]%></p>
	<pre id="sudo_config_apache"></pre>
	<p><%[ Example sudo configuration for Lighttpd web server user (RHEL, CentOS and others): ]%></p>
	<pre id="sudo_config_lighttpd"></pre>
	<p><%[ Example sudo configuration for Apache and Lighttpd web servers user (Debian, Ubuntu and others): ]%></p>
	<pre id="sudo_config_www_data"></pre>
</com:TJuiDialog>
