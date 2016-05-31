#!/usr/bin/env python
#coding=utf8
import threading
import Queue
import os
import hashlib
import subprocess
import json
import time
import sys

#新添加的文件
new_files = []
#修改的文件
diff_files = []
#clone的目录
clone_dir = '/tmp/fish'+time.strftime('%Y%m%d%H%M%S')
#git的源目录
git_path = "https://github.com/bin/fish.git"

#框架url
frame_urls = [
    'src/frame/Load.php',
    'src/frame/base/Exception.php',
    'src/frame/base/App.php',
    'src/frame/base/DI.php',
    'src/frame/base/Object.php',
    'src/frame/base/Request.php',
    'src/frame/web/Controller.php',
    'src/frame/web/Request.php',
    'src/frame/web/Response.php',
    'src/frame/web/App.php',
    'src/frame/console/App.php',
    'src/frame/console/Console.php',
    'src/frame/console/Request.php'
]

#组件库url
libs_urls = [
    'src/libs/db/DbExpression.php',
    'src/libs/db/DB.php',
    'src/libs/db/Query.php',
    'src/libs/db/Transaction.php',
    'src/libs/log/EmailTarget.php',
    'src/libs/log/FileTarget.php',
    'src/libs/log/Loger.php',
    'src/libs/log/LogTarget.php',
    'src/libs/base/Controller.php',
]

#工具urls
utils_urls = [
    'src/libs/utils/Validator.php',
    'src/libs/utils/ValidateTrait.php',
    'src/libs/utils/ArrayUtil.php',
    'src/libs/utils/MailUtil.php',
    'update.py',
]

urls = frame_urls + libs_urls + utils_urls

q = Queue.Queue(0)
basepath = os.getcwd()

def worker():
    while True:
        item = q.get()
        getFile(item)
        q.task_done()

#从git克隆到本地
def cloneFromGit():
    global clone_dir,git_path
    try:
        subprocess.call('/usr/bin/git clone '+git_path+' '+clone_dir,shell=True)
    except Exception as e:
        print e
        sys.exit(1)

#比对处理文件
def getFile(url):
    global q,basepath,clone_dir,new_files,diff_files
    src_file = clone_dir+'/'+url.strip('/')
    filename = basepath+'/'+url.lstrip('/')
    if os.path.exists(filename):
        with open(filename,'r') as f:
            filestr = f.read()
        with open(src_file,'r') as f:
            srcstr = f.read()
        oldmd5 = hashlib.new('md5',filestr).hexdigest()
        newmd5 = hashlib.new('md5',srcstr).hexdigest()
        if oldmd5 != newmd5:
            diff_files.append(url)
            with open(filename,'w') as f:
                f.write(srcstr)
    else:
        new_files.append(url)
        dir = os.path.dirname(filename)
        try:
            subprocess.call('/bin/mkdir -p '+dir,shell=True)
            subprocess.call('/bin/cp '+src_file+' '+filename,shell=True)
        except Exception as e:
            print e
    print 'File '+url+' upgrade finished#!'

if __name__ == '__main__':
    cloneFromGit()
    print 'update start...'
    for i in range(len(urls)/2):
        t = threading.Thread(target=worker)
        t.setDaemon(True)
        t.start()
    for url in urls:
       q.put(url)
    q.join()
    #删除克隆的目录
    subprocess.call('/bin/rm -rf '+clone_dir,shell=True)
    msgs = {
        "new_files":new_files,
        "update_files":diff_files,
    }
    print "\nResult:"
    print json.dumps(msgs,indent=2)
    


