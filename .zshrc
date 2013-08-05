# Path to your oh-my-zsh configuration.
ZSH=$HOME/.oh-my-zsh

# Set name of the theme to load.
# Look in ~/.oh-my-zsh/themes/
# Optionally, if you set this to "random", it'll load a random theme each
# time that oh-my-zsh is loaded.
ZSH_THEME="agnoster"

# Example aliases
# alias zshconfig="mate ~/.zshrc"
# alias ohmyzsh="mate ~/.oh-my-zsh"

# Set to this to use case-sensitive completion
# CASE_SENSITIVE="true"

# Comment this out to disable bi-weekly auto-update checks
# DISABLE_AUTO_UPDATE="true"

# Uncomment to change how often before auto-updates occur? (in days)
# export UPDATE_ZSH_DAYS=13

# Uncomment following line if you want to disable colors in ls
# DISABLE_LS_COLORS="true"

# Uncomment following line if you want to disable autosetting terminal title.
# DISABLE_AUTO_TITLE="true"

# Uncomment following line if you want to disable command autocorrection
# DISABLE_CORRECTION="true"

# Uncomment following line if you want red dots to be displayed while waiting for completion
# COMPLETION_WAITING_DOTS="true"

# Uncomment following line if you want to disable marking untracked files under
# VCS as dirty. This makes repository status check for large repositories much,
# much faster.
# DISABLE_UNTRACKED_FILES_DIRTY="true"

# Which plugins would you like to load? (plugins can be found in ~/.oh-my-zsh/plugins/*)
# Custom plugins may be added to ~/.oh-my-zsh/custom/plugins/
# Example format: plugins=(rails git textmate ruby lighthouse)
plugins=(git)

source $ZSH/oh-my-zsh.sh

# Customize to your needs...
export PATH=$PATH:/usr/local/heroku/bin:/opt/local/bin:/opt/local/sbin:/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin:/usr/texbin:/Users/Pat/bin/

####################################################################################################################################################################################

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
alias nw="/Applications/node-webkit.app/Contents/MacOS/node-webkit"
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
# export TERM=xterm-256color

export LSCOLORS=GxFxCxDxBxegedabagaced

set guifont=Menlo\ for\ Powerline\ Medium\ 12

set user=Pat@patrick-lindsays-macbook-pro

#####################################

PATH=$PATH:~/bin/
export PATH
### Added by the Heroku Toolbelt
export PATH="/usr/local/heroku/bin:$PATH"
