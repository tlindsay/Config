{$mode macpas}
{$modeswitch objectivec1}

unit AppDelegate;
interface
uses
	iPhoneAll, ViewController;

type
	HelloWorldAppDelegate = objcclass(NSObject)

		fWindow: UIWindow; {* IBOutlet *} 
		fmyViewController: MyViewController;
		
	{* Declare any methods we are going to override. *}
		procedure dealloc; override;

	{* @property (nonatomic, retain) UIWindow *window; *}
		function getWindow: UIWindow; message 'window';
		procedure setWindow (inWindow: UIWindow); message 'setWindow:';

	{* @property (nonatomic, retain) MyViewController *myViewController; *}
		function getMyViewController: MyViewController; message 'getMyViewController';
		procedure setMyViewController (inMyViewController: MyViewController); message 'setMyViewController:';

	{* UIApplicationDelegateProtocol methods we'll be adopting *}
		procedure applicationDidFinishLaunching(app: UIApplication); message 'applicationDidFinishLaunching:';
	end;

implementation


procedure HelloWorldAppDelegate.dealloc;
begin
	fmyViewController.release;
	fWindow.release;
	inherited dealloc;
end;


function HelloWorldAppDelegate.getWindow: UIWindow;
begin
	getWindow := fWindow;
end;


procedure HelloWorldAppDelegate.setWindow (inWindow: UIWindow);
begin
	{* fWindow.release; *}
	fWindow := inWindow;
	fWindow.retain;
end;

		
function HelloWorldAppDelegate.getMyViewController: MyViewController;
begin
	getMyViewController := fmyViewController;
end;


procedure HelloWorldAppDelegate.setMyViewController (inMyViewController: MyViewController);
begin
	{* fmyViewController.release; *}
	fmyViewController := inMyViewController;
	fmyViewController.retain;
end;


procedure HelloWorldAppDelegate.applicationDidFinishLaunching (app:UIApplication);
var
	aViewController: MyViewController;
	controllersView: UIView;
	nibName: NSString;
begin
	{* Setup the view controller. *}
	nibName := NSSTR('HelloWorld'); 
	aViewController := MyViewController(MyViewController.alloc).initWithNibName_bundle(nibName, NSBundle.mainBundle);
	self.setmyViewController(aViewController);
	aViewController.release;
	
	(UIApplication.sharedApplication).setStatusBarStyle(UIStatusBarStyleBlackOpaque);
	
	{* Add the view controller's view as a subview of the window. *}
	controllersView := fmyViewController.view;
	fWindow.addSubview(controllersView);
	fWindow.makeKeyAndVisible;
end;

end.