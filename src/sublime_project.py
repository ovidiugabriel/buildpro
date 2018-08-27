
import json

class sublime_project:
    def __init__(self):
        self.settings = []
        self.build_systems = []
        self.folders = []

    def add_build_system(self, cmd):
        self.build_systems.append({"cmd": cmd})

    def add_folder(self, folder):
        print("+ " + folder.path)
        self.folders.append(folder.to_dict())
        pass


    """
        * https://sublime-text-unofficial-documentation.readthedocs.org/en/latest/file_management/file_management.html\
        #the-sublime-project-format

        * http://docs.sublimetext.info/en/latest/reference/projects.html
    """
    def sublime_project(self):
        # TODO: Work sublime project generator
        data = {}
        if len(self.build_systems) > 0:
            data["build_systems"] = self.build_systems

        if len(self.settings) > 0:
            data["settings"] = self.settings

        if len(self.folders) > 0:
            data["folders"] = self.folders

        return json.dumps(data, sort_keys=True, indent=4, separators=(',', ': ') )



