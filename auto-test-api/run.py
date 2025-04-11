import os
import time
from commons.notify_tools.notify import TelegramBot
import pytest

if __name__ == '__main__':
    runcode = pytest.main()
    # TelegramBot.sendTelegram(runcode.value)
    # time.sleep(1)
    # 生成allure报告
    os.system("allure generate ./temps -o ./reports --clean")
    # jenkins生成测试报告 注释上面内容
