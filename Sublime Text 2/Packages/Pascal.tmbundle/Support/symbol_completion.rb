require ENV['TM_SUPPORT_PATH'] + '/lib/ui'

f = File.open(ENV['TM_PROJECT_DIRECTORY'] + '/.tm_completions') or die
choices=[]

f.each_line {|line|
  choices.push line.gsub(/\n/,'')
}

TextMate::UI.complete(choices, :initial_filter => ENV['TM_CURRENT_WORD'], :extra_chars => '_')