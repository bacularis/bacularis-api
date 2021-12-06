<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<com:THead Title="Baculum - Bacula Web Interface" ShortcutIcon="<%=$this->getPage()->getTheme()->getBaseUrl()%>/favicon.ico" />
	<body>
		<com:TForm>
				<com:BClientScript ScriptUrl=<%~ ../../Common/JavaScript/misc.js %> />
				<com:TClientScript PradoScripts="effects" />
				<com:BStyleSheet StyleSheetUrl=<%~ ../../vendor/w3css/w3.css %> />
				<com:BStyleSheet StyleSheetUrl=<%~ ../../../themes/Baculum-v2/css/baculum.css %> />
				<com:TContentPlaceHolder ID="Wizard" />
		</com:TForm>
	</body>
</html>
