<!DOCTYPE html>
<html lang="en">
	<com:THead Title="Baculum - Bacula Web Interface">
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="icon" href="<%=$this->getPage()->getTheme()->getBaseUrl()%>/favicon.ico" type="image/x-icon" />
	</com:THead>
	<body  class="w3-light-grey">
		<com:TForm>
			<com:TClientScript PradoScripts="ajax, effects" />
			<com:BClientScript ScriptUrl=<%~ ../../vendor/bower-asset/datatables.net/js/jquery.dataTables.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../vendor/bower-asset/datatables.net-responsive/js/dataTables.responsive.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../vendor/bower-asset/datatables.net-buttons/js/dataTables.buttons.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../vendor/bower-asset/datatables.net-buttons/js/buttons.html5.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../vendor/bower-asset/datatables.net-buttons/js/buttons.colVis.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../vendor/bower-asset/datatables.net-select/js/dataTables.select.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../Common/JavaScript/misc.js %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../vendor/w3css/w3.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../vendor/bower-asset/datatables.net-dt/css/jquery.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../vendor/bower-asset/datatables.net-responsive-dt/css/responsive.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../vendor/bower-asset/datatables.net-buttons-dt/css/buttons.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../themes/Baculum-v2/css/baculum.css %> />
			<com:Application.Common.Portlets.TableDefaults />
			<!-- Top container -->
			<div class="w3-bar w3-top w3-black w3-large" style="z-index: 4">
				<button type="button" class="w3-bar-item w3-button w3-hover-none w3-hover-text-light-grey" onclick="W3SideBar.open();"><i class="fa fa-bars"></i>  Menu</button>
				<span class="w3-bar-item w3-right">
					<img src="<%=$this->getPage()->getTheme()->getBaseUrl()%>/logo.png" alt="" />
				</span>
			</div>
			<com:Application.API.Portlets.APISideBar />
			<div class="w3-main page_main_el" id="page_main" style="margin-left: 250px; margin-top: 43px;">
				<com:TContentPlaceHolder ID="Main" />
				<footer class="w3-container w3-right-align w3-small"><%[ Version: ]%> <%=Params::BACULUM_VERSION%></footer>
			</div>
			<div id="small" class="w3-hide-large"></div>
		</com:TForm>
<script type="text/javascript">
var is_small = $('#small').is(':visible');
if (is_small) {
	W3SideBar.close();
}
</script>
	</body>
</html>
