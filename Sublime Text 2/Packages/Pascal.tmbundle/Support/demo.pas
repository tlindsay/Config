{$mode objfpc}
{$modeswitch objectivec1}

program Main;

type
	NSClassName = objcclass (NSObject)
		procedure loadSomeFile_params (theFile: pointer; params: pointer);
  	function application_printFiles_withSettings_showPrintPanels(application: NSApplication; fileNames: NSArray; printSettings: NSDictionary; showPrintPanels: Boolean): NSApplicationPrintReply; message 'application:printFiles:withSettings:showPrintPanels:';
	end;

begin
	NSApplication();
end.