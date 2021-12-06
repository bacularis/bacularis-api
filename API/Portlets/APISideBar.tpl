<!-- Sidebar/menu -->
<nav class="w3-sidebar w3-white w3-animate-left w3-margin-bottom" style="z-index:3; width:250px;" id="sidebar">
	<div class="w3-container w3-row w3-section">
		<div class="w3-col s3">
			<img src="<%=$this->getPage()->getTheme()->getBaseUrl()%>/avatar2.png" class="w3-circle w3-margin-right" style="width: 33px" />
		</div>
		<div class="w3-col s9 w3-bar">
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
	<div class="w3-container w3-black">
		<h5 class="w3-center" style="margin: 6px 0 2px 0">Baculum API Menu</h5>
	</div>
	<div class="w3-bar-block" style="margin-bottom: 45px;">
		<div class="w3-black" style="height: 3px"></div>
		<a href="<%=$this->Service->constructUrl('APIHome')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'APIHome' ? ' w3-blue': ''%>"><i class="fas fa-tachometer-alt fa-fw"></i> &nbsp;<%[ Dashboard ]%></a>
		<a href="<%=$this->Service->constructUrl('APIBasicUsers')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'APIBasicUsers' ? ' w3-blue': ''%>"><i class="fa fa-users fa-fw"></i> &nbsp;<%[ Basic users ]%></a>
		<a href="<%=$this->Service->constructUrl('APIOAuth2Clients')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'APIOAuth2Clients' ? ' w3-blue': ''%>"><i class="fa fa-user-shield fa-fw"></i> &nbsp;<%[ OAuth2 clients ]%></a>
		<a href="<%=$this->Service->constructUrl('APIDevices')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'APIDevices' ? ' w3-blue': ''%>"><i class="fa fa-server fa-fw"></i> &nbsp;<%[ Devices ]%></a>
		<a href="<%=$this->Service->constructUrl('APISettings')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'APISettings' ? ' w3-blue': ''%>"><i class="fa fa-wrench fa-fw"></i> &nbsp;<%[ Settings ]%></a>
		<a href="<%=$this->Service->constructUrl('APIInstallWizard')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'APIInstallWizard' ? ' w3-blue': ''%>"><i class="fa fa-hat-wizard fa-fw"></i> &nbsp;<%[ Configuration wizard ]%></a>
	</div>
</nav>
<!-- Overlay effect when opening sidebar on small screens -->
<div class="w3-overlay w3-hide-large w3-animate-opacity" onclick="W3SideBar.close(); return false;" style="cursor:pointer" title="close side menu" id="overlay_bg"></div>
