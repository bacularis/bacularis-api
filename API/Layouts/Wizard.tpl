<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<com:THead Title="Bacularis - Bacula Web Interface" ShortcutIcon="<%=$this->getPage()->getTheme()->getBaseUrl()%>/favicon.ico">
		<meta name="viewport" content="width=device-width, initial-scale=1" />
	</com:THead>
	<body class="w3-light-grey">
		<com:TForm>
				<com:BClientScript ScriptUrl=<%~ ../../../../../Common/JavaScript/misc.js %> />
				<com:TClientScript PradoScripts="effects" />
				<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../../htdocs/themes/Baculum-v2/css/w3css/w3.css %> />
				<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../../htdocs/themes/Baculum-v2/css/baculum.css %> />
				<com:TContentPlaceHolder ID="Wizard" />
		</com:TForm>
	</body>
</html>
