
class SublimeFolder:
    def __init__(self, name, path):
        self.name = name
        self.path = path

        self.file_exclude_patterns   = []
        self.binary_file_patterns    = []
        self.folder_exclude_patterns = []

    def to_dict(self):
        return {
            "name": self.name,
            "path": self.path,
            "file_exclude_patterns": self.file_exclude_patterns,
            "binary_file_patterns": self.binary_file_patterns,
            "folder_exclude_patterns": self.folder_exclude_patterns
        }
