# import fcntl
import time
def lock_file(file_path):
    """
    对文件加锁

    Args:
        file_path: 文件路径

    Returns:
        文件句柄
    """

    fd = open(file_path, "w", encoding="utf-8")
    # 使用 LOCK_EX 选项进行排他锁
    # fcntl.flock(fd, fcntl.LOCK_EX)
    return fd
def lock_filea(file_path):

    fd = open(file_path, "a", encoding="utf-8")
    # 使用 LOCK_EX 选项进行排他锁
    # fcntl.flock(fd, fcntl.LOCK_EX)
    return fd
def unlock_file(fd):
    """
    对文件解锁

    Args:
        fd: 文件句柄
    """

    # fcntl.flock(fd, fcntl.LOCK_UN)
    fd.close()
def write_file(file_name, args):
    # 获取文件句柄
    fd = lock_file(file_name)
    fd.truncate(0)
    # 写入内容
    fd.write(args)
    # 解锁文件
    unlock_file(fd)
def append_file(file_name, args):
    # 获取文件句柄
    fd = lock_filea(file_name)
    # 写入内容
    fd.write(args)
    # 解锁文件
    unlock_file(fd)

def read_file(file_name):
    try:
        with open(file_name, "r", encoding="utf-8") as f:
            data = f.read()
        return data
    except Exception as e:
        print(e)

def get_formatted_time():
    # 获取当前时间
    now = time.time()
    # 格式化时间
    formatted_time = time.strftime("%Y/%m/%d %H:%M:%S", time.localtime(now))
    return formatted_time