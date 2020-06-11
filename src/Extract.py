import zipfile as zf
import sys
import os

dir1 = sys.argv[1]
dir2 = sys.argv[2]

r0 = open('activeDir', 'r')
activeDir = r0.readline()
r0.close()

# Clean up before itself
filelist = [ f for f in os.listdir('users/dirs/' + activeDir + '/view')]
for f in filelist:
    os.remove(os.path.join('users/dirs/' + activeDir + '/view', f))

# Unzip the file
with zf.ZipFile(dir1, 'r') as zf:
    try:
        zf.extractall(dir2)
    except Exception as e:
        print(e)
