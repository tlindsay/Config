<?

// Outputs a Pascal source file as styled HTML

class PascalTextFormatter {
	
	// Pascal syntax definitions
	var $syntax = array(	"keywords" => array("/\b(absolute|abstract|all|and|and_then|array|as|asm|attribute|begin|bindable|case|class|const|constructor|destructor|div|do|do|else|end|except|export|exports|external|far|file|finalization|finally|for|forward|goto|if|implementation|import|in|inherited|initialization|interface|interrupt|is|label|library|length|mod|module|name|near|not|object|of|only|operator|or|or_else|ord|otherwise|packed|pow|private|program|property|protected|public|published|qualified|record|repeat|resident|restricted|segment|set|shl|shr|then|to|try|type|unit|until|uses|var|view|virtual|while|with|write|writeln|xor)+\b/i"),
							//"functions" => array(""),
							"comments" => array("/\{.*\}/s", "/^\/\/(.*)/i"),
						);
	
	// Syntax styles which match syntax captures
	var $styles = array(	"keywords" => "<span style=\"color:rgb(0,0,255)\">\$1</span>",
							"comments" => "<span style=\"color:rgb(255,0,0)\">\$0</span>",
							);
		
	var $path;
	var $line;
	
	private function format () {
		
		/*
		$content = file_get_contents($this->path);
		
		foreach ($this->syntax as $definition => $patterns) {
			$content = @preg_replace($patterns, $this->styles[$definition], $content);
		}
		
		$content = str_replace("\n", "<br/>\n", $content);
		*/
		
		$lines = file($this->path);
		$content = "<p>";
		
		// Iterate all lines of file
		$line_count = 0;
		foreach ($lines as $line) {
			$line = trim($line, "\n");
			$line_count ++;
			
			// Apply syntax styles to each line
			foreach ($this->syntax as $definition => $patterns) {
				$line = @preg_replace($patterns, $this->styles[$definition], $line);
			}
			
			// Print the line with selection hilite for the source line
			if ($line_count == $this->line) {
				$content .= "<a name=\"$line_count\">$line_count)</a> <span class=\"selection\">$line</span><br/>\n";
			} else {
				$content .= "<a name=\"$line_count\">$line_count)</a> $line<br/>\n";
			}
		}
		
		$content .= "</p>";
		print($content);
	}
		
	function __construct ($source_path, $source_line) {
		$this->path = $source_path;
		$this->line = $source_line;

		//print($this->path);
		$this->format();
	}
	
}
	
//$GLOBALS['argv'][1] = "/Developer/Pascal/GPCInterfaces/VersionH/FPCPInterfaces/CFArray.pas";
//$GLOBALS['argv'][2] = 10;
print_r($GLOBALS['argv']);

$formatter = new PascalTextFormatter ($GLOBALS['argv'][1], $GLOBALS['argv'][2]);

?>