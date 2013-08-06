#####################################
# BASH Profile						#
#####################################

# Utility Commands
#####################################
alias ips="ifconfig -a | perl -nle'/(\d+\.\d+\.\d+\.\d+)/ && print $1'"
alias myip="dig +short myip.opendns.com @resolver1.opendns.com"
alias flush="dscacheutil -flushcache"
alias ping="ping -c 5"
alias ll="ls -l"
#####################################

# Folder Aliases
#####################################
alias javahw="cd /Users/Pat/Documents/Schoolwork/Fall\ \'12/CIS\ 315\ \-\ Java/Homework"
alias automata="cd /Users/Pat/Documents/Schoolwork/Spring\ \'13/CS\ 421\ —\ Automata"
alias squirtle="cd /Library/WebServer/Documents/Team\ Squirtle/TeamSquirtle"
alias olm="cd /Library/WebServer/Documents/OneLessMile"
#####################################

# Functions
#####################################
#web - Navigate to WebServer dir and list projects
web(){
	cd /Library/WebServer/Documents/;
	ll;
}

# subl - Open file in Sublime Text 2
subl(){
    open -a Sublime\ Text\ 2 "$@";
}

# find - Open file/folder in Finder
find(){
	open -a Finder "$@";
}

# tree - Display current directory and subdir contents in tree form
tree(){
	ls -R | grep ":$" | sed -e 's/:$//' -e 's/[^-][^\/]*\//--/g' -e 's/^/   /' -e 's/-/|/';
}
#####################################

# Terminal Configurations
#####################################
# Set CLICOLOR if you want Ansi Colors in iTerm2 
export CLICOLOR=1

# Set colors to match iTerm2 Terminal Colors
export TERM=xterm-256color

export LSCOLORS=GxFxCxDxBxegedabagaced
#####################################

PATH=$PATH:~/bin/
export PATH
### Added by the Heroku Toolbelt
export PATH="/usr/local/heroku/bin:$PATH"
