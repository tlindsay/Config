{$mode macpas}{$H+}
{$modeswitch objectivec1}

unit ViewController;
interface
uses
  iPhoneAll;
		
		
type
	MyViewController = objcclass(UIViewController)
		{* IBOutlet *} fTextField: UITextField;
		{* IBOutlet *} fLabel: UILabel;
		fString: NSString;
					
	{* Declare any methods we are going to override. *}
		procedure viewDidLoad; override;
		procedure dealloc; override;
		procedure touchesBegan_withEvent (touches: NSSet; event:UIEvent); override;
		
	{* @property (nonatomic, retain) UITextField *textField; *}
		function getTextField: UITextField; message 'textField';
		procedure setTextField (inTextField: UITextField); message 'setTextField:';
	
	{* @property (nonatomic, retain) UILabel *label; *}
		function getLabel: UILabel; message 'label';
		procedure setLabel (inLabel: UILabel); message 'setLabel:';
	
	{* @property (nonatomic, copy) NSString *string; *}
		function getString: NSString; message 'string';
		procedure setString (inString: NSString); message 'setString:';
		
		procedure updateString; message 'updateString';

	{* UITextFieldDelegateProtocol methods we'll be adopting. *}
		function textFieldShouldReturn (theTextField: UITextField): Boolean; message 'textFieldShouldReturn:';
	end;
		
		
implementation


procedure MyViewController.viewDidLoad;

begin
	{* When the user starts typing, show the clear button in the text field. *}
	fTextField.setClearButtonMode(UITextFieldViewModeWhileEditing);
	
	{* When the view first loads, display the placeholder text that's in the *}
	{* text field in the label. *}
	fLabel.setText(fTextField.placeholder);
end;


procedure MyViewController.dealloc;
begin
	{* fTextField.release; *}
	fLabel.release;
	inherited dealloc;
end;


procedure MyViewController.touchesBegan_withEvent (touches: NSSet; event:UIEvent);
begin
	fTextField.resignFirstResponder;
	fTextField.setText(self.getString);
	inherited touchesBegan_withEvent(touches, event);
end;


function MyViewController.getTextField: UITextField;
begin
	getTextField := fTextField;
end;


procedure MyViewController.setTextField (inTextField: UITextField);
begin
	{* fTextField.release; *};
	fTextField := inTextField;
	fTextField.retain;
end;


function MyViewController.getLabel: UILabel;
begin
	getLabel := fLabel;
end;


procedure MyViewController.setLabel (inLabel: UILabel);
begin
	{* fLabel.release; *}
	fLabel := inLabel;
	fLabel.retain;
end;


function MyViewController.getString: NSString;
begin
	getString := fString;
end;


procedure MyViewController.setString (inString: NSString);
begin
	{* fString.release; *}
	fString := inString;
	fString.retain;
end;


procedure MyViewController.updateString;
begin
	self.setString(fTextField.text);
	fLabel.setText(self.getString);
end;


function MyViewController.textFieldShouldReturn (theTextField: UITextField): Boolean;
begin	
	if (theTextField = fTextField) then
	begin
		fTextField.resignFirstResponder;
		self.updateString;
	end;

	textFieldShouldReturn := TRUE;
end;

end.
