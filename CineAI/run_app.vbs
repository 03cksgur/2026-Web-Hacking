Set WshShell = CreateObject("WScript.Shell")

' Set path to PHP and server folder
Dim phpPath, appDir, command
phpPath = "C:\xampp\php\php.exe"
appDir = CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptPosition)

' Start PHP server in the background (0 = Hidden window)
' -S 127.0.0.1:8000 -t [folder_path]
command = """" & phpPath & """ -S 127.0.0.1:8000 -t """ & appDir & """"
WshShell.Run command, 0, False

' Wait a bit for the server to initialize
WScript.Sleep 1000

' Open the browser to the site
WshShell.Run "http://127.0.0.1:8000"
