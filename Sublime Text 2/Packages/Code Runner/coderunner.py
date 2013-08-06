import sublime, sublime_plugin
import sys
import subprocess
import os
import threading
import math
import time
import tempfile

class ThreadProgress():
    """
    Class taken from Package Control

    :param thread:
        The thread to track for activity

    :param message:
        The message to display next to the activity indicator
    """

    def __init__(self, thread, message):
        self.thread  = thread
        self.message = message
        self.addend  = 1
        self.size    = 8
        sublime.set_timeout(lambda: self.run(0), 100)

    def run(self, i):
        if not self.thread.is_alive():
            sublime.status_message('' if hasattr(self.thread, 'result') and not self.thread.result else self.thread.result)
            return

        before = i % self.size
        after = (self.size - 1) - before

        sublime.status_message('%s [%s=%s]' % \
            (self.message, ' ' * before, ' ' * after))

        if not after:
            self.addend = -1
        if not before:
            self.addend = 1
        i += self.addend

        sublime.set_timeout(lambda: self.run(i), 100)


class RunThread(threading.Thread):

    command = ""
    result  = ""

    def __init__(self, command, path, args, commandObj):

        self.command    = command
        self.commandObj = commandObj
        self.path       = '"' + path + '"'
        self.args       = args
        threading.Thread.__init__(self)
        
    def run(self):
        # Some program may be in /usr/local/bin
        env = os.environ.copy()
        env["PATH"] += ":/usr/local/bin"

        # And count the eclapsed time

        eclapsed = time.time()

        cmd = self.command.replace("{{file}}", self.path).replace("{{args}}", self.args)

        run = subprocess.Popen(cmd
                , bufsize = -1
                , shell   = True
                , stdout  = subprocess.PIPE
                , stderr  = subprocess.PIPE
                , env     = env)

        stdout, stderr = run.communicate()

        eclapsed = int( (time.time() - eclapsed) * 1000 )

        if stderr:
            self.result = "Run Failed"
        else:
            self.result = "Run Completed in {0}ms".format(eclapsed)

        sys.stdout.write(stderr)

        # Collect stdout and stderr, show them in a new buffer
        if stderr or stdout:
            self.stdout = stdout
            self.stderr = stderr
            sublime.set_timeout(lambda : self.show_res(), 10)

    def show_res(self):
        
        template = """/*
 * Run Result from : {0}
 */

{1}
"""
        command_settings = sublime.load_settings("coderunner.sublime-settings")

        if command_settings.get( "show_result_in_buffer" ):
            new_view = self.commandObj.window.get_output_panel("run_result")
            self.commandObj.window.run_command("show_panel", {"panel" : "output.run_result" })
        else:
            new_view = self.commandObj.window.new_file()

        new_view.set_name("Run Result")
        new_view.set_read_only(False)

        edit   = new_view.begin_edit()
        output = template.format( self.path, self.stdout or self.stderr )

        new_view.insert( edit, 0, output.decode(sys.getfilesystemencoding()) )
        new_view.end_edit(edit)
        
        new_view.set_read_only(True)
        new_view.set_scratch(True)
        new_view.settings().set( 'syntax', 'Packages/JavaScript/JavaScript.tmLanguage' )


class RunCodeCommand(sublime_plugin.WindowCommand):
    def run(self, args=""):
        view     = self.window.active_view()
        settings = view.settings()
        syntax   = settings.get( 'syntax' )

        language = os.path.basename(syntax).replace('.tmLanguage', '').lower() if syntax != None else "plain text"

        command_settings = sublime.load_settings("coderunner.sublime-settings")
        commands         = command_settings.get("commands")
        if not language in commands:
          return

        # Generate a thread to execute the command
        sys.stdout.write(tempfile.tempdir)
        filename = view.file_name()
        if view.is_dirty():
            # Save the dirty content to a temp file
            filename = os.path.normpath(tempfile.tempdir) + "/" + command_settings.get("temp_file_name")
            try:
                temp = open(filename, "w")
            except:
                sublime.status_message("Cannot save buffer to temp file for running!")
                return
            temp.write(view.substr(sublime.Region(0,view.size())))
            temp.close()

        thread = RunThread( commands[language], filename, args , self )
        thread.start()

        # Show process indicator
        ThreadProgress(thread, "Running...")


class RunCodeCustomCommand(sublime_plugin.WindowCommand):

    lastArgs = ""

    def run(self):
        self.window.show_input_panel("Custom Arguments"
            , self.lastArgs
            , lambda a : self.window.run_command("run_code", { "args" : a } )
            , None
            , None)

    
