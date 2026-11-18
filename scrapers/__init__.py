import os
import sys

_scrapers_dir = os.path.dirname(os.path.abspath(__file__))
_project_root = os.path.dirname(_scrapers_dir)

if _scrapers_dir not in sys.path:
    sys.path.insert(0, _scrapers_dir)
if _project_root not in sys.path:
    sys.path.insert(0, _project_root)
