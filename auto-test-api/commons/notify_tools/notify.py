import os

import requests
import commons.notify_tools.tools as tools
import json


class TelegramBot:
    flow_name = None
    def __init__(self):
        self.chats_id = {
            'prod': self.product_chat_id,
            'pre': self.pipline_chat_id,
            'test': self.pipline_chat_id,
            'dev': self.pipline_chat_id,
            'debug': self.debug_chat_id
        }
        self.chats_token = {
            'prod': self.product_token,
            'pre': self.pipline_token,
            'test': self.pipline_token,
            'dev': self.pipline_token,
            'debug': self.debug_token
        }

    @classmethod
    def set_flow_name(cls, flow_name):
        cls.flow_name = flow_name

    def product_chat_id(self):
        return '-1001615966271'

    def pipline_chat_id(self):
        return '-1001615966271'

    def debug_chat_id(self):
        return '-1001615966271'

    def product_token(self):
        return '6612500913:AAFm8i4sn1_84OLtKZ6I62xwGN4TZ-1cn_g'

    def pipline_token(self):
        return '6612500913:AAFm8i4sn1_84OLtKZ6I62xwGN4TZ-1cn_g'

    def debug_token(self):
        return '6612500913:AAFm8i4sn1_84OLtKZ6I62xwGN4TZ-1cn_g'

    def default_id(self):
        return self.chat_id

    def default_token(self):
        return self.token

    def getToken(self):
        return self.chats_token.get(os.environ['env'], self.default_token)()

    def getId(self):
        return self.chats_id.get(os.environ['env'], self.default_id)()

    @staticmethod
    def append_failed_case(url, body, response):
        post_dict = \
            {"url": url,
             "body": body,
             "response": response.text}
        tools.append_file("./logs/failed_case_struct.log", json.dumps(post_dict) + ",")

    @staticmethod
    def ifsendmsg(result):
        if os.environ['env'] in ['dev', 'test', 'pre', 'prod']:
            return 1
        else:
            if result != 0:
                return 1
            else:
                return 0

    @staticmethod
    def sendTelegram(result):
        flow_name = TelegramBot.flow_name
        env = os.environ['env']
        if TelegramBot.ifsendmsg(result) == 0:
            return
        failed_names = tools.read_file("./logs/failed_case_struct.log")
        failed_names = str(failed_names).rstrip(",")
        failed_names = failed_names + "]"
        json_data = json.loads(failed_names)
        mark = ""
        for i in json_data:
            ifi = json.dumps(i, indent=2, ensure_ascii=False).encode("utf-8").decode("utf-8")
            mark = mark + \
                   (f"""
        ```
        {ifi}
        ```
        """)
        # report = f"{configpy.buildurl}htmlreport/index.html"
        # console = f"{configpy.buildurl}console"
        report = "report_url"
        console = "console_url"
        failed_names = mark
        source = ['_', '[', ']', '(', ')', '~', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!']
        for i in source:
            failed_names = failed_names.replace(i, '\\' + i)
            report = report.replace(i, '\\' + i)
            console = console.replace(i, '\\' + i)
        if result == 0:
            text = f"""
        *【自动化测试】*
        模块：{flow_name}
        测试环境：{env}
        任务执行结果：✅ 测试成功
        执行时间：{tools.get_formatted_time()}
        日志：[构建日志]({console})
        报表：[测试报告]({report})
        """
        else:
            text = f"""
        *【自动化测试】*
        模块：{flow_name}
        测试环境：{env}
        任务执行结果：❌ 测试失败
        执行时间：{tools.get_formatted_time()}
        日志：[构建日志]({console})
        报表：[测试报告]({report})
        失败用例：
        {failed_names}
        """
        # print(text)
        body = {
            "text": text,
            "parse_mode": "MarkdownV2",
            "chat_id": TelegramBot().getId()
        }
        url = "https://api.telegram.org/bot{}/sendMessage".format(TelegramBot().getToken())
        requests.packages.urllib3.disable_warnings()
        response = requests.post(url, json=body)
        print(response.text)
