<!DOCTYPE html>
<html lang="en">
	<com:THead Title="Bacularis - Bacula Web Interface">
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="icon" href="<%~ ../../../../../Common/Images/favicon.ico %>" type="image/x-icon" />
	</com:THead>
	<body>
		<com:TForm>
			<com:TClientScript PradoScripts="ajax, effects" />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/npm-asset/datatables.net/js/dataTables.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/npm-asset/datatables.net-responsive/js/dataTables.responsive.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/npm-asset/datatables.net-buttons/js/dataTables.buttons.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/npm-asset/datatables.net-buttons/js/buttons.html5.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/npm-asset/datatables.net-buttons/js/buttons.colVis.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/npm-asset/datatables.net-select/js/dataTables.select.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/npm-asset/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../Common/JavaScript/misc.js %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../Common/CSS/w3css/w3.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../vendor/npm-asset/datatables.net-dt/css/dataTables.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../vendor/npm-asset/datatables.net-responsive-dt/css/responsive.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../vendor/npm-asset/datatables.net-buttons-dt/css/buttons.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../vendor/npm-asset/datatables.net-fixedheader-dt/css/fixedHeader.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../Common/CSS/baculum.css %> />
			<com:Bacularis.Common.Portlets.TableDefaults />
			<!-- Top container -->
			<div id="main_top_bar" class="w3-bar w3-top w3-black w3-large" style="z-index: 5">
				<button type="button" class="w3-bar-item w3-button w3-hover-none w3-hover-text-light-grey" onclick="W3SideBar.open();"><i class="fa fa-bars"></i>  Menu</button>
				<img class="w3-bar-item w3-right" src="<%~ ../../../../../Common/Images/logo.png %>" alt="" style="margin-top: 3px" />
			</div>
			<span class="w3-right w3-padding-small w3-margin-top w3-margin-right">
				<label><i class="fa-solid fa-sun"></i>
					<label class="switch small" onclick="ThemeMode.toggle_mode();">
						<input type="checkbox" id="theme_mode_switcher" />
						<span class="slider small round"></span>
					</label> <i class="fa-solid fa-moon"></i>
				</label>
			</span>
			<com:Bacularis.API.Portlets.APISideBar />
			<div class="w3-main page_main_el" id="page_main" style="margin-left: 250px; margin-top: 43px;">
				<com:TContentPlaceHolder ID="Main" />
				<footer class="w3-container w3-right-align w3-small"><%[ Version: ]%> <%=Params::BACULARIS_VERSION%></footer>
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
