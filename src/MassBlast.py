from zipfile import ZipFile
import os
import math
from PIL import Image, ImageDraw, ImageFont
import shutil

# Harvest parameters
r = open('activeDir', 'r')
whoami = r.readline()
r.close()
r2 = open('users/dirs/' + whoami + '/tmp/request', 'r')
r2.readline()
srcFile = r2.readline().replace('\n', '')
compareWith = []
peptideIndices = []
filterBy = ''
filterIndices = []
saveas = ''
while True:
    line = r2.readline().replace('\n', '')
    if line.__contains__('.faa'):
        compareWith.append(line)
    else:
        peptideIndices = line.split('||')
        peptideIndices[0] = int(peptideIndices[0])
        peptideIndices[1] = int(peptideIndices[1])
        filterBy = r2.readline().replace('\n', '')
        filterIndices = r2.readline().replace('\n', '').split('||')
        try:
            filterIndices[0] = float(filterIndices[0])
            filterIndices[1] = float(filterIndices[1])
        except:
            filterIndices[0] = 0
            filterIndices[1] = 0
        saveas = r2.readline().replace('\n', '')
        break
r2.close()

# Clean up before itself
filelist = [f for f in os.listdir('users/dirs/' + whoami + '/results')]
for f in filelist:
    os.remove(os.path.join('users/dirs/' + whoami + '/results', f))

# Isolate the sequences of focus file
r3 = open('users/dirs/' + whoami + '/files/' + srcFile)
tmp = []
tmp2 = ''
for line in r3.readlines():
    tmp2 = tmp2 + line
tmp2 = tmp2.split('>')
peptides = tmp2[peptideIndices[0]-1:peptideIndices[1]+1]
w = open('users/dirs/' + whoami + '/tmp/focus.faa', 'w')
for a in range(1, peptides.__len__()):
    w.write('>' + peptides[a])
w.close()

# Generate the data
s = []
i = []
p = []
g = []
clear = open('users/dirs/' + whoami + '/results/allMB.txt', 'w')
clear.close()
for a in range(0, compareWith.__len__()):
    cmd = 'blastp -query users/dirs/' + whoami + '/tmp/focus.faa -subject ' + 'users/dirs/' + whoami + '/files/' +compareWith[a] + ' -evalue .001 -out users/dirs/' + whoami + '/tmp/mB.txt -max_target_seqs 1 -max_hsps 1'
    os.system(cmd)
    r3 = open('users/dirs/' + whoami + '/tmp/mB.txt', 'r')
    st = []
    it = []
    pt = []
    gt = []
    rtmp = open('users/dirs/' + whoami + '/tmp/mB.txt', 'r')
    fileLines = []    
    linecount = 0
    for line in rtmp.readlines():
        fileLines.append(line)
        linecount += 1
    append = open('users/dirs/' + whoami + '/results/allMB.txt', 'a')
    append.write('===---===---===\n')
    for b in range(0, linecount):
        append.write(fileLines[b])
    append.close()
    queryMode = False
    while True:
        line = r3.readline().replace('\n', '')
        linecount -= 1
        if line.__contains__('Query='):
            queryMode = True
            continue
        if queryMode and line.__contains__('***** No hits found *****'):
            st.append(-1)
            it.append(-1)
            pt.append(-1)
            gt.append(-1)
            queryMode = False
        elif queryMode and line.__contains__('Score =') and line.__contains__('Expect ='):
            tmp = line.split(',')
            score = float(tmp[0].split(' ')[3])
            line = r3.readline().replace('\n', '')
            tmp = line.split(',')
            tmp2 = tmp[0].split(' ')[3].split('/')
            id = float(tmp2[0])/float(tmp2[1])
            tmp2 = tmp[1].split(' ')[3].split('/')
            positive = float(tmp2[0])/float(tmp2[1])
            tmp2 = tmp[2].split(' ')[3].split('/')
            gap = float(tmp2[0])/float(tmp2[1])
            filterOut = False
            if filterBy == 'Score' and (score < filterIndices[0] or score > filterIndices[1]):
                filterOut = True
            if filterBy == 'ID' and (id < filterIndices[0] or id > filterIndices[1]):
                filterOut = True
            if filterBy == 'Positive' and (positive < filterIndices[0] or positive > filterIndices[1]):
                filterOut = True
            if filterBy == 'Gap' and (gap < filterIndices[0] or gap > filterIndices[1]):
                filterOut = True
            if filterOut:
                score = 0
                id = 0
                positive = 0
                gap = 0
            st.append(score)
            it.append(id)
            pt.append(positive)
            gt.append(gap)
            queryMode = False
        if linecount == 1:
            break 
    s.append(st)
    i.append(it)
    p.append(pt)
    g.append(gt)

# Write down all hits detected
w = open('users/dirs/' + whoami + '/results/allPeptides.txt', 'w')
rMB = open('users/dirs/' + whoami + '/results/allMB.txt', 'r')
rMB.readline()
allMB = ''
for line in rMB.readlines():
    allMB += line
allMB = allMB.split('===---===---===')
query = allMB[0].split('\n')
peptideOI = []
for a in range(0, query.__len__()):
    if query[a].__contains__('Query='):
        toAdd = query[a]
        if not query[a].__contains__(']'):
            toAdd += query[a + 1]
        toAdd += '\n'
        peptideOI.append(toAdd)
allPeptides = []
for a in range(0, peptideOI.__len__()):
    tmp2 = []
    w.write(str(a + peptideIndices[0]) + '... ' + peptideOI[a])
    for b in range(0, allMB.__len__()):
        w.write('\t' + str(b + 1) + '.. ' + compareWith[b] + '\n')
        lines = allMB[b].split('Query=')[a + 1].split('\n')
        tmp = []
        for c in range(1, lines.__len__()):
            if lines[c].__contains__('> '):
                toAppend = lines[c]
                if not toAppend.__contains__(']'):
                    toAppend += lines[c + 1]
                tmp.append(toAppend.replace('\n', ' '))
        if tmp.__len__() == 0:
            w.write('\t\t***** No hits found *****\n')
            tmp2.append(0)
        else:
            for d in range(0, tmp.__len__()):
                w.write('\t\t' + tmp[d] + '\n')
            tmp2.append(tmp.__len__())
    allPeptides.append(tmp2)# Write the data
wS = open('users/dirs/' + whoami + '/results/score.csv', 'w')
wI = open('users/dirs/' + whoami + '/results/id.csv', 'w')
wP = open('users/dirs/' + whoami + '/results/positives.csv', 'w')
wG = open('users/dirs/' + whoami + '/results/gap.csv', 'w')
wF = open('users/dirs/' + whoami + '/results/frequency.csv', 'w')


def write(input):
    wS.write(input)
    wI.write(input)
    wP.write(input)
    wG.write(input)
    wF.write(input)


toWrite = ','
for a in range(peptideIndices[0], peptideIndices[1] + 1):
    toWrite += str(a) + ','
toWrite += "\n"
write(toWrite)
for a in range(0, compareWith.__len__()):
    write(compareWith[a] + ',')
    for b in range(0, s[0].__len__()):
        wS.write(str(s[a][b]) + ',')
        wI.write(str(i[a][b]) + ',')
        wP.write(str(p[a][b]) + ',')
        wG.write(str(g[a][b]) + ',')
        wF.write(str(allPeptides[b][a]) + ',')
    write("\n")
wS.close()
wI.close()
wP.close()
wG.close()
wF.close()

# Find the max to create allow RGB to be scaled
maxS = -2
maxI = -2
maxF = -2
for a in range(0, s.__len__()):
    for b in range(0, s[0].__len__()):
        if maxS < s[a][b]:
            maxS = s[a][b]
        if maxI < i[a][b]:
            maxI = i[a][b]
        if maxF < allPeptides[b][a]:
            maxF = allPeptides[b][a]
try:
    for a in range(0, peptideOI.__len__()):
        peptideOI[a] = peptideOI[a].replace('\n', '')

    # Generate heatmap PNG for Score
    im = Image.new('RGB', (250 + (25*(peptideIndices[1] - peptideIndices[0] + 2)),2 + 25*(1 + compareWith.__len__())), (80, 80, 80))
    draw = ImageDraw.Draw(im)
    font = ImageFont.truetype("font/arial.ttf", 25)
    font2 = ImageFont.truetype("font/arial.ttf", 15)
    for a in range(0, compareWith.__len__()):
        draw.text((0, 25*(a+1)), compareWith[a], fill=(255, 255, 255, 255), font=font)
    for a in range(0, (peptideIndices[1] - peptideIndices[0] + 1)):
        if (peptideIndices[0] + a) % 10 == 0:
            draw.text((250 + (25*a), 0), str(peptideIndices[0] + a), fill=(255, 255, 255, 255), font=font)
        elif (peptideIndices[0] + a) % 2 == 0:
            draw.text((250 + (25*a) + 4, 0), str(peptideIndices[0] + a), fill=(255, 255, 255, 255), font=font2)
    for a in range(0, s.__len__()):
        for b in range(0, s[0].__len__()):
            left = 250 + 25*b
            right = left + 25
            top = 25*(a+1)
            bottom = top + 25
            if s[a][b] == -1:
                draw.rectangle((left, top, right, bottom), fill=(255, 0, 0), outline=(255, 0, 0))
            elif s[a][b] == 0:
                draw.rectangle((left, top, right, bottom), fill=(0, 0, 255), outline=(0, 0, 255))
            else:
                g = (s[a][b] / maxS) ** 2
                g = int(g * 255)
                draw.rectangle((left, top, right, bottom), fill=(0, g, 0), outline=(0, g, 0))
    im.save('users/dirs/' + whoami + '/results/hm-score.png', quality=500)

    # Generate heatmap PNG for ID
    im = Image.new('RGB', (250 + (25*(peptideIndices[1] - peptideIndices[0] + 2)),2 + 25*(1 + compareWith.__len__())), (80, 80, 80))
    draw = ImageDraw.Draw(im)
    font = ImageFont.truetype("font/arial.ttf", 25)
    font2 = ImageFont.truetype("font/arial.ttf", 15)
    for a in range(0, compareWith.__len__()):
        draw.text((0, 25*(a+1)), compareWith[a], fill=(255, 255, 255, 255), font=font)
    for a in range(0, (peptideIndices[1] - peptideIndices[0] + 1)):
        if (peptideIndices[0] + a) % 10 == 0:
            draw.text((250 + (25*a), 0), str(peptideIndices[0] + a), fill=(255, 255, 255, 255), font=font)
        elif (peptideIndices[0] + a) % 2 == 0:
            draw.text((250 + (25*a) + 4, 0), str(peptideIndices[0] + a), fill=(255, 255, 255, 255), font=font2)
    for a in range(0, i.__len__()):
        for b in range(0, i[0].__len__()):
            left = 250 + 25*b
            right = left + 25
            top = 25*(a+1)
            bottom = top + 25
            if i[a][b] == -1:
                draw.rectangle((left, top, right, bottom), fill=(255, 0, 0), outline=(255, 0, 0))
            elif i[a][b] == 0:
                draw.rectangle((left, top, right, bottom), fill=(0, 0, 255), outline=(0, 0, 255))
            else:
                g = (i[a][b] / maxI) ** 2
                g = int(g * 255)
                draw.rectangle((left, top, right, bottom), fill=(0, g, 0), outline=(0, g, 0))
    im.save('users/dirs/' + whoami + '/results/hm-id.png', quality=500)

    # Generate heatmap HTML for Score
    w = open('users/dirs/' + whoami + '/results/hm-score.html', 'w')
    w.write('<table>\n\t<tr>\n\t\t<th class="headcol">Score</th>')
    for a in range(0, peptideIndices[1] - peptideIndices[0] + 1):
        w.write('\n\t\t<th>' + str(peptideIndices[0] + a) + '</th>')
    w.write('\n\t</tr>')
    for a in range(0, compareWith.__len__()):
        w.write('\n\t<tr>\n\t\t<th class="headcol">' + compareWith[a] + '</th>')
        for b in range(0, s[0].__len__()):
            if s[a][b] == -1:
                comment = "Peptide: " +  peptideOI[b] + ", (No Hit Detected), Peptide Index: " + str(peptideIndices[0] + b) + ", Compared With " + compareWith[a]
                w.write('\n\t\t<td style="background-color:rgb(255,0,0)" title="Peptide: ' + peptideOI[b] + ', (No Hit Detected), Peptide Index: ' + str(peptideIndices[0] + b) + ', Compared With ' + compareWith[a] + '"><button class="rb" name="IBC" value="' + str(peptideIndices[0] + b) + '||' + compareWith[a] + '||' + comment + '">_</button></td>')
            elif s[a][b] == 0:
                comment = "Peptide: " +  peptideOI[b] + ", (Hit Detected, but Filtered), Peptide Index: " + str(peptideIndices[0] + b) + ", Compared With " + compareWith[a]
                w.write('\n\t\t<td style="background-color:rgb(0,0,255)" title="Peptide: ' + peptideOI[b] + ', (Hit Detected, but Filtered), Peptide Index: ' + str(peptideIndices[0] + b) + ', Compared With ' + compareWith[a] + '"><button class="rb" name="IBC" value="' + str(peptideIndices[0] + b) + '||' + compareWith[a] + '||' + comment + '">_</button></td>')
            else:
                g = (s[a][b] / maxS)
                g = int(g * 255)
                comment = "Peptide: " + peptideOI[b] + ", Score: " + str(s[a][b]) +  "(bits), Peptide Index: " + str(peptideIndices[0] + b) + ", Compared With " + compareWith[a]
                w.write('\n\t\t<td style="background-color:rgb(0,' + str(g) + ',0)" title="Peptide: ' + peptideOI[b] + ', Score: ' + str(s[a][b]) + ' (bits), Peptide Index: ' + str(peptideIndices[0] + b) + ', Compared With ' + compareWith[a] + '"><button name="IBC" value="' + str(peptideIndices[0] + b) + '||' + compareWith[a] + '||' + comment + '">' + str(s[a][b]) + '</button></td>')
        w.write('\n\t</tr>')
    w.write('\n</table>')
    w.close()

    # Generate heatmap HTML for ID
    w = open('users/dirs/' + whoami + '/results/hm-id.html', 'w')
    w.write('<table>\n\t<tr>\n\t\t<th class="headcol">ID</th>')
    for a in range(0, peptideIndices[1] - peptideIndices[0] + 1):
        w.write('\n\t\t<th>' + str(peptideIndices[0] + a) + '</th>')
    w.write('\n\t</tr>')
    for a in range(0, compareWith.__len__()):
        w.write('\n\t<tr>\n\t\t<th class="headcol">' + compareWith[a] + '</th>')
        for b in range(0, i[0].__len__()):
            if i[a][b] == -1:
                comment = "Peptide: " +  peptideOI[b] + ", (No Hit Detected), Peptide Index: " + str(peptideIndices[0] + b) + ", Compared With " + compareWith[a]
                w.write('\n\t\t<td style="background-color:rgb(255,0,0)" title="Peptide: ' + peptideOI[b] + ', (No Hit Detected), Peptide Index: ' + str(peptideIndices[0] + b) + ', Compared With ' + compareWith[a] + '"><button class="rb" name="IBC" value="' + str(peptideIndices[0] + b) + '||' + compareWith[a] + '||' + comment + '">_</button></td>')
            elif i[a][b] == 0:
                comment = "Peptide: " +  peptideOI[b] + ", (Hit Detected, but Filtered), Peptide Index: " + str(peptideIndices[0] + b) + ", Compared With " + compareWith[a]
                w.write('\n\t\t<td style="background-color:rgb(0,0,255)" title="Peptide: ' + peptideOI[b] + ', Hit Detected, but Filtered, Peptide Index: ' + str(peptideIndices[0] + b) + ', Compared With ' + compareWith[a] + '"><button class="rb" name="IBC" value="' + str(peptideIndices[0] + b) + '||' + compareWith[a] + '||' + comment + '">_</button></td>')
            else:
                g = (i[a][b] / maxI) ** 2
                g = int(g * 255)
                comment = "Peptide: " + peptideOI[b] + ", ID: " + str(i[a][b]) +  ", Peptide Index: " + str(peptideIndices[0] + b) + ", Compared With " + compareWith[a]
                w.write('\n\t\t<td style="background-color:rgb(0,' + str(g) + ',0)" title="Peptide: ' + peptideOI[b] + ', ID: ' + str(i[a][b]) + ', Peptide Index: ' + str(peptideIndices[0] + b) + ', Compared With ' + compareWith[a] + '"><button name="IBC" value="' + str(peptideIndices[0] + b) + '||' + compareWith[a] + '||' + comment + '">' + str(i[a][b]) + '</button></td>')
        w.write('\n\t</tr>')
    w.write('\n</table>')
    w.write('\n\t</tr>')
    w.write('\n</table>')
    w.close()

    # Generate heatmap HTML fo hit frequency
    w = open('users/dirs/' + whoami + '/results/hm-frequency.html', 'w')
    w.write('<table>\n\t<tr>\n\t\t<th class="headcol">Frequency</th>')
    for a in range(0, peptideIndices[1] - peptideIndices[0] + 1):
        w.write('\n\t\t<th>' + str(peptideIndices[0] + a) + '</th>')
    w.write('\n\t</tr>')
    for a in range(0, compareWith.__len__()):
        w.write('\n\t<tr>\n\t\t<th class="headcol">' + compareWith[a] + '</th>')
        for b in range(0, allPeptides.__len__()):
            if allPeptides[b][a] == 0:
                comment = "Peptide: " +  peptideOI[b] + ", (No Hit Detected), Peptide Index: " + str(peptideIndices[0] + b) + ", Compared With " + compareWith[a]
                w.write('\n\t\t<td style="background-color:rgb(255,0,0)" title="Peptide: ' + peptideOI[b] + ', (No Hit Detected), Peptide Index: ' + str(peptideIndices[0] + b) + ', Compared With ' + compareWith[a] + '"><button class="rb" name="IBC" value="' + str(peptideIndices[0] + b) + '||' + compareWith[a] + '||' + comment + '">_</button></td>')
            else:
                g = (allPeptides[b][a] / maxF) ** 2
                g = int(g * 255)
                comment = "Peptide: " + peptideOI[b] + ", Frequency: " + str(allPeptides[b][a]) +  ", Peptide Index: " + str(peptideIndices[0] + b) + ", Compared With " + compareWith[a]
                w.write('\n\t\t<td style="background-color:rgb(0,' + str(g) + ',0)" title="Peptide: ' + peptideOI[b] + ', Frequency: ' + str(allPeptides[b][a]) + ', Peptide Index: ' + str(peptideIndices[0] + b) + ', Compared With ' + compareWith[a] + '"><button name="IBC" value="' + str(peptideIndices[0] + b) + '||' + compareWith[a] + '||' + comment + '">' + str(allPeptides[b][a]) + '</button></td>')
        w.write('\n\t</tr>')
    w.write('\n</table>')
    w.close()
except Exception as e:
    print(e)
    exit(1)

# Duplicate the request ticket
request = []
r = open('users/dirs/' + whoami + '/tmp/request', 'r')
for line in r.readlines():
    request.append(line)
r.close()
w = open('users/dirs/' + whoami + '/results/request.txt', 'w')
for a in range(0, request.__len__()):
    w.write(request[a])
w.close()

# Zip the results directory
shutil.make_archive('users/dirs/' + whoami + '/zip/' + saveas, 'zip', 'users/dirs/' +  whoami + '/results')

# Clean up after itself
filelist = [f for f in os.listdir('users/dirs/' + whoami + '/results')]
for f in filelist:
    os.remove(os.path.join('users/dirs/' + whoami + '/results', f))

print('Completed with no error')