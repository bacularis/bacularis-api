<!-- Sidebar/menu -->
<nav class="w3-sidebar w3-animate-left w3-margin-bottom" style="z-index:3; width:250px;" id="sidebar">
	<div class="w3-container w3-center w3-margin-top" style="height: 30px;">
		<img src="<%~ ../../../../../Common/Images/logo_xl.png %>" rel="logo" style="width: 68%; height: auto;" />
	</div>
	<div class="w3-container w3-border-bottom" style="min-height: 84px; margin-bottom: 5px;">
		<div class="w3-center w3-margin-top">
			<h5><%[ API panel ]%></h5>
			<span><%[ Welcome ]%><strong><%=isset($_SERVER['PHP_AUTH_USER']) ? ', ' . $_SERVER['PHP_AUTH_USER'] : ''%></strong></span><br>
			<script>var main_side_bar_reload_url = '<%=$this->reload_url%>';</script>
			<com:TActiveLinkButton
				ID="Logout"
				OnClick="logout"
				CssClass="w3-bar-item w3-button"
				ToolTip="<%[ Logout ]%>"
			>
				<prop:ClientSide.OnComplete>
					if (!window.chrome || window.navigator.webdriver)  {
						window.location.href = main_side_bar_reload_url;
					} else if (window.chrome) {
						// For chrome this reload is required to show login Basic auth prompt
						window.location.reload();
					}
				</prop:ClientSide.OnComplete>
				<i class="fa fa-power-off"></i>
			</com:TActiveLinkButton>
		</div>
	</div>
	<div class="w3-bar-block" style="margin-bottom: 45px;">
		<a href="<%=$this->Service->constructUrl('APIHome')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'APIHome' ? ' w3-blue': ''%>"><i class="fas fa-tachometer-alt fa-fw"></i> &nbsp;<%[ Dashboard ]%></a>
		<a href="<%=$this->Service->constructUrl('APIBasicUsers')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'APIBasicUsers' ? ' w3-blue': ''%>"><i class="fa fa-users fa-fw"></i> &nbsp;<%[ Basic users ]%></a>
		<a href="<%=$this->Service->constructUrl('APIOAuth2Clients')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'APIOAuth2Clients' ? ' w3-blue': ''%>"><i class="fa fa-user-shield fa-fw"></i> &nbsp;<%[ OAuth2 clients ]%></a>
		<a href="<%=$this->Service->constructUrl('APIDevices')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'APIDevices' ? ' w3-blue': ''%>"><i class="fa fa-server fa-fw"></i> &nbsp;<%[ Devices ]%></a>
		<a href="<%=$this->Service->constructUrl('APISettings')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'APISettings' ? ' w3-blue': ''%>"><i class="fa fa-wrench fa-fw"></i> &nbsp;<%[ Settings ]%></a>
		<a href="<%=$this->Service->constructUrl('APIInstallWizard')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'APIInstallWizard' ? ' w3-blue': ''%>"><i class="fa fa-magic fa-fw"></i> &nbsp;<%[ Configuration wizard ]%></a>
	</div>
</nav>
<script>
const set_logo_sidebar = () => {
	if (ThemeMode.is_dark()) {
		document.querySelectorAll('[rel="logo"]').forEach(function(el) {
			el.src = '<%~ ../../../../../Common/Images/logo_xl_white.png %>';
		});
	} else {
		document.querySelectorAll('[rel="logo"]').forEach(function(el) {
			el.src = '<%~ ../../../../../Common/Images/logo_xl.png %>';
		});
	}
};
ThemeMode.add_cb(set_logo_sidebar);
set_logo_sidebar();
</script>
<!-- Overlay effect when opening sidebar on small screens -->
<div class="w3-overlay w3-hide-large w3-animate-opacity" onclick="W3SideBar.close(); return false;" style="cursor:pointer" title="close side menu" id="overlay_bg"></div>
