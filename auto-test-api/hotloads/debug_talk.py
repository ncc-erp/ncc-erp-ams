import base64
import datetime
import hashlib
import inspect
import json
import logging
import os
import random
import string
import time
import pymysql
import pyotp
import pytest
import yaml
import calendar
from commons.enum_util import CalcMode
from jsonpath_ng import jsonpath, parse

logger = logging.getLogger(__name__)


class DebugTalk:

    def list(self, form):
        if form is not None and form != 'None':
            return form.split("-")
        return None

    def objList(self, form):
        if form is not None and form != 'None':
            return form.split("-")
        return None

    def after_exec(self, func_name):
        class_name = str(func_name).split(".")[0]
        method_name = str(func_name).split(".")[1]
        caseCode = str(func_name).split(".")[2]
        module = __import__(class_name)
        func = getattr(module, method_name)
        func(caseCode)

    def login_google_verify_code(self, googleAuthCode):
        totp = pyotp.TOTP(googleAuthCode)
        return totp.now()

    def get_google_verify_code(self, module_name, userLevel):
        if userLevel is None or '' == userLevel or 'default' == userLevel:
            env_google_key = module_name + '_default_googleAuthCode'
        else:
            env_google_key = module_name + '_' + userLevel + '_googleAuthCode'
        googleAuthCode = os.environ[env_google_key]
        return self.login_google_verify_code(googleAuthCode)

    def get_now_time(self):
        return str(datetime.datetime.now().strftime("%Y-%m-%d") + " 23:59:59")

    def get_now_time_sub_day(self, days):
        return str((datetime.datetime.now() - datetime.timedelta(days=int(days))).strftime("%Y-%m-%d %H:%M:%S"))

    def get_now_time_sub_day_format(self, days, format):
        return str((datetime.datetime.now() - datetime.timedelta(days=int(days))).strftime(format))

    def get_random_time(self):
        return str(int(time.time()))[1:6]

    def get_random_number(self, mix, max):
        rm = random.randint(int(mix), int(max))
        return str(rm)

    def get_verify_code(self, mobile):
        code = "example_code"
        return code

    def get_random_string(self, length):
        characters = string.ascii_letters
        return "".join(random.choices(characters, k=int(length))).lower()

    def whether_read_extract_data(self, data, extract_key):
        if data is None or len(data) == 0:
            return self.read_extract_data(extract_key)
        else:
            return data

    def read_bet_data(self, key):
        if key is None or key == '':
            return None
        with open(os.getcwd() + "/bet.yaml", encoding='utf-8') as f:
            value = yaml.safe_load(stream=f)
            if value is not None and key in value:
                result = value[key]
                if result is not None:
                    return result
                logger.warning("接口关联, key={}, 数据不存在.")

    def read_extract_data(self, key):
        if key is None or key == '':
            return None
        with open(os.getcwd() + "/extract.yaml", encoding="utf-8") as f:
            value = yaml.load(stream=f, Loader=yaml.FullLoader)
            if value is not None and key in value:
                result = value[key]
                if result is not None:
                    return result
                pytest.skip("接口关联, key={}, 数据不存在, 跳过当前执行的case".format(key))
            else:
                if key == 'proxyCardId':
                    return self.read_config('client_server', 'proxyCardId')
                logger.error("接口关联，数据不存在：key={}.已存储数据={}".format(key, value))
                pytest.skip("接口关联数据不存在: key={}, 跳过当前执行的case".format(key))

    def read_extracts_data(self, caseCode, key):
        with open(os.getcwd() + "/extract.yaml", encoding="utf-8") as f:
            value = yaml.load(stream=f, Loader=yaml.FullLoader)
            if value is not None and caseCode in value and key in value[caseCode]:
                return value[caseCode][key]
            else:
                logger.error("接口关联，数据不存在：key={}.已存储数据={}".format(key, value))

    def read_config(self, one_node, two_node):
        with open(os.getcwd() + "/config.yaml", encoding="utf-8") as f:
            value = yaml.load(stream=f, Loader=yaml.FullLoader)
            result = value[os.environ['env']][one_node][two_node]
            return result

    def read_config_for_three(self, one_node, two_node, three_node):
        with open(os.getcwd() + "/config.yaml", encoding="utf-8") as f:
            value = yaml.load(stream=f, Loader=yaml.FullLoader)
            result = value[os.environ['env']][one_node][two_node][three_node]
            return result

    def md5(self, args):
        utf8_str = str(args).encode("utf-8")
        md5_str = hashlib.md5(utf8_str).hexdigest()
        return md5_str

    def double_md5(self, args):
        utf8_str = str(args).encode("utf-8")
        md5_str = hashlib.md5(utf8_str).hexdigest().upper()
        md5_pwd = hashlib.md5(md5_str.encode("utf-8")).hexdigest()
        return md5_pwd.upper()

    def double_concat_md5(self, args):
        utf8_str = str(args).encode("utf-8")
        md5_str = hashlib.md5(utf8_str).hexdigest().upper()
        md5_pwd = hashlib.md5(md5_str.encode("utf-8")).hexdigest().upper()
        md5_str = f"{md5_str}|{md5_pwd}"
        return md5_str

    def base64(self, args):
        utf8_str = str(args).encode("utf-8")
        base64_str = base64.b64encode(utf8_str).decode("utf-8")
        return base64_str.upper()

    def get_timestamp_begin(self, days_ago):
        days_ago = int(days_ago)
        current_timestamp = int(time.time())
        # 计算当前时间在指定时区的偏移量
        offset_seconds = 8 * 3600
        # 计算N天前0点的时间戳
        return (current_timestamp - (current_timestamp % 86400) - offset_seconds - (days_ago - 1) * 86400) * 1000

    def get_timestamp_now(self):
        return int(round(time.time() * 1000))

    def get_today_begin(self, args):
        if int(args) == 1:
            now_time = time.strftime("%Y-%m-%d", time.localtime())
            return now_time + " 00:00:00"
        else:
            current_timestamp = int(time.time())
            # 计算当前时间在指定时区的偏移量
            offset_seconds = 8 * 3600
            return (current_timestamp - (current_timestamp % 86400) - offset_seconds) * 1000

    def get_today_end(self, args):
        if int(args) == 1:
            now_time = time.strftime("%Y-%m-%d", time.localtime())
            return now_time + " 23:59:59"
        else:
            current_timestamp = int(time.time())
            # 计算当前时间在指定时区的偏移量
            offset_seconds = 8 * 3600
            return (current_timestamp - (current_timestamp % 86400) + 86399 - offset_seconds) * 1000

    def read_sql(self, sqlPath):
        with open(os.getcwd() + "/sql.yaml", encoding="utf-8") as f:
            value = yaml.load(stream=f, Loader=yaml.FullLoader)
            result = value[os.environ['env']][sqlPath]
            return self.replace_value(result)

    def execute_sql(self, module_name, sqlPath):

        sql = self.read_sql(sqlPath)["sql"]
        database = self.read_sql(sqlPath)["database"]
        logger.info("执行sql,database=" + database)
        logger.info("执行sql,sql=" + sql)
        db = pymysql.connect(host=self.read_config(module_name, "host"),
                             user=self.read_config(module_name, "username"),
                             password=self.read_config(module_name, "password"),
                             port=self.read_config(module_name, "port"),
                             database=database,
                             cursorclass=pymysql.cursors.DictCursor
                             )
        cursor = db.cursor()
        cursor.execute("SET @@allow_auto_random_explicit_insert = true")
        cursor.execute(sql)
        result = cursor.fetchall()
        logger.info("执行sql,返回=" + str(result))
        db.commit()
        db.close()

    def replace_sub_value(self, value):

        for key in list(value.keys()):
            sub_value = value[key]
            if isinstance(sub_value, dict):
                self.replace_sub_value(sub_value)
            elif isinstance(sub_value, list):
                for list_sub_value in sub_value:
                    self.replace_sub_value(list_sub_value)
            else:
                if sub_value is None:
                    return sub_value
                else:
                    value[key] = self.replace_data(sub_value)

        return value

    def replace_value(self, data):
        if isinstance(data, dict):
            return self.replace_sub_value(data)
        else:
            return self.replace_data(data)

    def get_replace_data(self, ori_value):
        start_index = ori_value.index("${")
        end_index = ori_value.index("}", start_index)
        old_value = ori_value[start_index:end_index + 1]
        function_name = old_value[2:old_value.index('(')]
        args_value1 = old_value[old_value.index('(') + 1:old_value.index(')')]
        if args_value1 != "":
            args_value2 = args_value1.split(',')
            # print(function_name, args_value2)
            return getattr(self, function_name)(*args_value2)
        else:
            if self.check_params(function_name):
                return getattr(self, function_name)(None)
            else:
                return getattr(self, function_name)()

    def check_params(self, method):
        self_method = self.__getattribute__(method)
        params = inspect.signature(self_method).parameters
        return len(params) > 0

    def get_replace_data_from_index(self, str_data, positions):
        # 如果是非字符串类型的，返回对应的类型，不能返回字符串
        is_obj = False
        if str_data.startswith("${") and str_data.endswith("}"):
            is_obj = True
        for position in reversed(positions):
            old_value = ''
            for i in range(position, len(str_data)):
                cs = str_data[i]
                old_value = old_value + cs
                if '}' == cs:
                    new_value = self.get_replace_data(old_value)
                    if position == positions[0] and is_obj:
                        return new_value
                    else:
                        str_data = str_data.replace(old_value, str(new_value))
                    break
        return str_data

    def replace_data(self, str_data):
        positions = self.find_all_occurrences(str_data, "${")
        if len(positions) == 0:
            return str_data
        return self.get_replace_data_from_index(str_data, positions)

    def find_all_occurrences(self, ori_string, sub_string):
        positions = []
        start = 0
        while True:
            position = ori_string.find(sub_string, start)
            if position == -1:
                break
            positions.append(position)
            start = position + 1
        return positions

    def get_today(self):
        return time.strftime("%Y-%m-%d", time.localtime())

    def get_today_sub_day(self, days):
        return str((datetime.datetime.now() - datetime.timedelta(days=int(days))).strftime("%Y-%m-%d"))

    def get_yyyymmdd(self, days):
        return str((datetime.datetime.now() - datetime.timedelta(days=int(days))).strftime("%Y%m%d"))

    def get_yyyymm(self, days):
        return str((datetime.datetime.now() - datetime.timedelta(days=int(days))).strftime("%Y%m"))

    # 获取上个月第一天日期
    def get_first_day_of_last_month(self):
        # 获取当前日期
        today = datetime.datetime.now()

        # 计算上个月的第一天
        first_day_of_last_month = datetime.datetime(today.year, today.month, 1) - datetime.timedelta(days=1)
        first_day_of_last_month = datetime.datetime(first_day_of_last_month.year, first_day_of_last_month.month, 1)

        # 格式化日期字符串
        first_day_str = first_day_of_last_month.strftime('%Y%m%d')

        return str(first_day_str)

    # 获取本月最后一天日期
    def get_last_day_of_current_month(self):
        # 获取当前日期
        today = datetime.datetime.now()

        # 获取这个月的最大天数
        _, last_day = calendar.monthrange(today.year, today.month)

        # 计算这个月的最后一天
        last_day_of_current_month = datetime.datetime(today.year, today.month, last_day)

        # 格式化日期字符串
        last_day_str = last_day_of_current_month.strftime('%Y%m%d')

        return str(last_day_str)

    def get_client_token(self):
        if 'client_server_token' in os.environ:
            token = os.environ['client_server_token']
        else:
            token = ''
        return token

    def condition_filter(self, reference1, reference2, condition):
        if condition == CalcMode.GREATER_THAN.value:
            if reference1 > reference2:
                return reference1
            else:
                raise ValueError("reference1：{}, reference2：{}, 不满足条件：> ".format(reference1, reference2))
        elif condition == CalcMode.LESS_THAN.value:
            if reference1 < reference2:
                return reference1
            else:
                raise ValueError("reference1：{}, reference2：{}, 不满足条件：<".format(reference1, reference2))
        elif condition == CalcMode.EQUALS_THAN.value:
            if reference1 == reference2:
                return reference1
            else:
                raise ValueError("reference1：{}, reference2：{}, 不满足条件：=".format(reference1, reference2))
        else:
            raise ValueError(
                '请传入正确的条件，目前支持：CalcMode.GREATER_THAN=6,CalcMode.LESS_THAN=7,CalcMode.EQUALS_THAN=8')


    def init_json(self, path):
        with open(path, encoding="utf-8", mode="w") as f:
            f.truncate()
            data = {}
            json.dump(data, f, indent=2)

    def pop_json(self, path, pop_key):
        data = None
        with open(path, 'r') as f:
            data = json.load(f)
        if pop_key in data:
            pop_value = data.pop(pop_key)
            with open(path, 'w') as f:
                json.dump(data, f, indent=2)
            return pop_value
        return None

    def update_json(self, path, key, value):
        data = None
        with open(path, 'r', encoding='utf-8') as f:
            data = json.load(f)
        if data is not None:
            data[f'{key}'] = value
            with open(path, 'w', encoding='utf-8') as f:
                json.dump(data, f, ensure_ascii=False, indent=2)

    def jsonpath_replace_json(self, path, replace_key, replace_value, expression):
        data = None
        with open(path, 'r') as f:
            data = json.load(f)
        if replace_key:
            data = data.get(replace_key)
        path_expr = parse(expression)
        for match in path_expr.find(data):
            match.context.value[match.path.fields[0]] = replace_value
        return json.dumps(data, indent=2)

    def rebate_json_convert(self, userType, userName):
        path = os.path.join(os.getcwd(), "variable.json")
        res = {}
        rebateRateList = []
        res.update({"userType": userType})
        res.update({"userName": userName})
        with open(path, 'r', encoding='utf-8') as f:
            data = json.load(f)
        proxyRebateRate = data['proxyRebateRate']
        if proxyRebateRate:
            proxyRebateRate = list(proxyRebateRate)[0]
            rebate = {
                "id": proxyRebateRate['rebateRateId'],
                "platformFlag": proxyRebateRate['platformFlag'],
                "parentProxyId": proxyRebateRate['parentProxyId'],
                "zhanchengFlag": 0,
                "texasRebate": proxyRebateRate['texasRebate'],
                "texasInsuranceRebate": proxyRebateRate['texasInsuranceRebate'],
                "valueAddedServiceRebate": proxyRebateRate['valueAddedServiceRebate'],
                "actualPersonRebate": proxyRebateRate['actualPersonRebate'],
                "sportsRebate": int(proxyRebateRate['sportsRebate']) + 0.01,
                "lotteryTicketRebate": proxyRebateRate['lotteryTicketRebate'],
                "chessRebate": proxyRebateRate['chessRebate'],
                "esportsRebate": proxyRebateRate['esportsRebate'],
                "electronicRebate": proxyRebateRate['electronicRebate'],
                "sportsARebate": proxyRebateRate['sportsARebate'],
                "sportsBRebate": proxyRebateRate['sportsBRebate'],
                "sportsCRebate": proxyRebateRate['sportsCRebate'],
                "sportsDRebate": proxyRebateRate['sportsDRebate']
            }
            zhancheng = {
                "id": proxyRebateRate['zhanchengRateId'],
                "platformFlag": proxyRebateRate['platformFlag'],
                "parentProxyId": proxyRebateRate['parentProxyId'],
                "zhanchengFlag": 1,
                "texasRebate": proxyRebateRate['texasRebate'],
                "texasInsuranceRebate": proxyRebateRate['texasInsuranceRebate'],
                "valueAddedServiceRebate": proxyRebateRate['valueAddedServiceRebate'],
                "actualPersonRebate": proxyRebateRate['actualPersonZhancheng'],
                "sportsRebate": proxyRebateRate['sportsZhancheng'],
                "lotteryTicketRebate": proxyRebateRate['lotteryTicketZhancheng'],
                "chessRebate": proxyRebateRate['chessZhancheng'],
                "esportsRebate": proxyRebateRate['esportsZhancheng'],
                "electronicRebate": proxyRebateRate['electronicZhancheng']
            }
            rebateRateList.append(rebate)
            rebateRateList.append(zhancheng)
            res.update({"rebateRateList": rebateRateList})
        return res