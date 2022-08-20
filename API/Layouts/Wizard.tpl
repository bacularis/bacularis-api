<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<com:THead Title="Bacularis - Bacula Web Interface" ShortcutIcon="<%~ ../../../../../Common/Images/favicon.ico %>">
		<meta name="viewport" content="width=device-width, initial-scale=1" />
	</com:THead>
	<body>
		<com:TForm>
				<com:BClientScript ScriptUrl=<%~ ../../../../../Common/JavaScript/misc.js %> />
				<com:TClientScript PradoScripts="effects" />
				<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../Common/CSS/w3css/w3.css %> />
				<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../Common/CSS/baculum.css %> />
				<com:TContentPlaceHolder ID="Wizard" />
		</com:TForm>
	</body>
</html>
