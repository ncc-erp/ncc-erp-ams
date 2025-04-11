import copy
import json
import logging
import os
import time

import jsonpath
import pytest
import requests
from requests_toolbelt import MultipartEncoder

from commons.assert_util import AssertUtil
from hotloads.debug_talk import DebugTalk
from commons.yaml_util import YamlUtil

logger = logging.getLogger(__name__)


class RequestUtil:
    sess = requests.session()

    def standard_yaml(self, module, caseinfo):
        copy_caseinfo = copy.deepcopy(caseinfo)
        caseinfo_keys = caseinfo.keys()
        auto_page = False
        params = []
        if 'autoPage' in caseinfo_keys:
            auto_page = caseinfo['autoPage']
        if 'env' in caseinfo_keys and caseinfo['env'] != os.environ['env']:
            logger.warning("当前env={},caseinfo_env={}. 跳过本次执行".format(os.environ['env'], caseinfo['env']))
            pytest.skip("当前env={},caseinfo_env={}. 跳过执行本次case".format(os.environ['env'], caseinfo['env']))
            return
        if "name" in caseinfo_keys and "request" in caseinfo_keys and "validate" in caseinfo_keys:
            cs_request = caseinfo['request']
            validate = caseinfo['validate']
            judgement = True
            if 'judgement' in caseinfo:
                if caseinfo['judgement'] == 'False':
                    judgement = False
            validata_field = []
            if 'validate_field' in caseinfo and judgement:
                validata_field = caseinfo['validate_field']
            for index, er in enumerate(validate):
                for item, value in er.items():
                    if item == 'contains' and ('${' in value or '$ddt{' in value):
                        er[item] = DebugTalk().replace_value(value)
                validate[index] = er
            name = caseinfo['name']
            cs_request_keys = cs_request.keys()
            if "method" in cs_request_keys and "url" in cs_request_keys:
                self.exec_before_sql(module, caseinfo)
                method = cs_request.pop("method")
                if method == "GET" or method == "get":
                    params = cs_request['params']
                elif method == "POST" or method == "post":
                    params = cs_request['json']
                url = "{}{}".format(DebugTalk().read_config(module, "base_url"), cs_request.pop("url"))
                if self.skip_filter(module, url):
                    pytest.skip("##{}接口已加入skip过滤, url：{}".format(name, url))
                    return
                logger.info("##{}接口##请求开始".format(name))
                user_level = 'default'
                if 'userLevel' in caseinfo_keys and '' != caseinfo['userLevel']:
                    user_level = caseinfo['userLevel']
                res = self.send_request(module, method, url, user_level, **cs_request)
                # return_text = res.text
                AssertUtil().assert_result(name=name, expect_result=validate, expect_field=validata_field,
                                           actual_result=res.json(), url=url, body=cs_request, response=res)
                self.extractSaveResult(caseinfo, res)
                self.verify_extract_params(caseinfo, params, res)
                self.json_extract(caseinfo, res)
                self.exec_after(caseinfo)
                self.exec_after_sql(module, caseinfo)
                self.exec_after_token(module, caseinfo)
                if auto_page and 'pageNum' in params and isinstance(res.json()['data'], dict):
                    data = res.json()['data']
                    if 'pageNum' in data:
                        page_num = data['pageNum']
                        total_page = data['totalPage']
                        if page_num < total_page:
                            page_num = page_num + 1
                            copy_caseinfo['request']['json']['pageNum'] = str(page_num)
                            res = self.standard_yaml(module, copy_caseinfo)
                return res
            else:
                logger.error("二级关键字必须包含:method,url")
        else:
            logger.error("一级关键字必须包含:name,request,validate")

    def skip_filter(self, module, request_url):
        yaml_values = YamlUtil().read_yaml("/skip.yaml")
        if yaml_values is None:
            return False
        skip_urls = yaml_values[module]['urls']
        if skip_urls is None:
            return False
        for url in skip_urls:
            filter_url = "{}{}".format(DebugTalk().read_config(module, 'base_url'), url)
            if filter_url == request_url:
                return True
        return False

    def exec_before_sql(self, module, caseinfo):
        if 'beforeSql' in caseinfo and '' != caseinfo['beforeSql']:
            DebugTalk().execute_sql(module, caseinfo['beforeSql'])

    def exec_after_sql(self, module, caseinfo):
        if 'afterSql' in caseinfo and '' != caseinfo['afterSql']:
            DebugTalk().execute_sql(module, caseinfo['afterSql'])

    def exec_after_token(self, module, caseinfo):
        if 'afterToken' in caseinfo and '' != caseinfo['afterToken']:
            token = module + "_" + "token"
            if DebugTalk().read_extract_data(caseinfo['afterToken']) != None:
                os.environ[token] = DebugTalk().read_extract_data(caseinfo['afterToken'])

    def exec_after(self, caseinfo):
        if 'after' in caseinfo and '' != caseinfo['after']:
            DebugTalk().after_exec(caseinfo['after'])

    # 查看是否需要接口关联数据 存储数据
    def extractSaveResult(self, caseinfo, res):
        isolation_prefix = None
        if 'isolationPrefix' in caseinfo:
            isolation_prefix = caseinfo['isolationPrefix']
        if "extract" in caseinfo:
            paths = caseinfo["extract"]
            for key, path in paths.items():
                extract = jsonpath.jsonpath(res.json(), path)
                if isolation_prefix is not None:
                    key = isolation_prefix + "_" + key
                if extract:
                    YamlUtil().write_yaml({key: extract[0]})

                else:
                    logger.error("接口关联写入数据失败：需要数据={}，返回={}".format(key, res.json()))
        if "extractList" in caseinfo:
            paths = caseinfo["extractList"]
            for key, path in paths.items():
                extract = jsonpath.jsonpath(res.json(), path)
                if isolation_prefix is not None:
                    key = isolation_prefix + "_" + key
                if extract:
                    YamlUtil().write_yaml({key: extract})

                else:
                    logger.error("接口关联写入数据失败：需要数据={}，返回={}".format(key, res.json()))
        if "extracts" in caseinfo:
            paths = caseinfo["extracts"]
            for key, path in paths.items():
                extract = jsonpath.jsonpath(res.json(), path)
                if extract:
                    saveData = {
                        caseinfo['caseCode']: {
                            key: extract[0]
                        }
                    }
                    YamlUtil().write_yaml(saveData)
                else:
                    logger.error("接口关联写入数据失败：需要数据={}，返回={}".format(key, res.json()))

    def verify_extract_params(self, caseinfo, params, res):
        if 'verify_extract' in caseinfo:
            extracts = caseinfo['verify_extract']
            isolation_enable = False
            user_level = ''
            if 'isolation_enable' in extracts:
                isolation_enable = extracts.pop('isolation_enable')
            if 'userLevel' in caseinfo:
                user_level = caseinfo['userLevel']
            for item, extract_keys in extracts.items():
                if item.startswith('from_') and item.endswith('params'):
                    for key in extract_keys:
                        params_key = extract_keys[key]
                        if params_key in params:
                            if isolation_enable and user_level is not None and user_level != "":
                                key = user_level + "_" + key
                            YamlUtil().write_yaml({key: params[params_key]})
                if item.startswith('from_') and item.endswith('response'):
                    for key in extract_keys:
                        params_key = extract_keys[key]
                        extract_data = jsonpath.jsonpath(res.json(), params_key)
                        if isolation_enable and user_level is not None and user_level != "":
                            key = user_level + "_" + key
                        YamlUtil().write_yaml({key: extract_data[0]})

    def json_extract(self, caseinfo, res):
        path = os.path.join(os.getcwd(), "variable.json")
        if 'json_extract' in caseinfo:
            extracts = caseinfo['json_extract']
            for item, expression in extracts.items():
                match = jsonpath.jsonpath(res.json(), expression)
                if match:
                    DebugTalk().update_json(path, item, match[0])


    def send_request(self, module, method, url, user_level, **kwargs):
        token = module + "_" + "token"
        if user_level is not None and user_level != 'default':
            token = module + "_" + user_level + "_" + "token"
        if token in os.environ:
            token = os.environ[token]

        method = str(method).lower()
        headers = self.header(str(int(round(time.time() * 1000))), token)
        replacekwargs = DebugTalk().replace_value(kwargs)
        url = DebugTalk().replace_value(url)
        headers = DebugTalk().replace_value(headers)
        logger.info("url:{}".format(url))
        logger.info("headers:{}".format(headers))
        logger.info("method:{}".format(method))
        if "data" in replacekwargs:
            data = replacekwargs["data"]
            for key in list(data.keys()):
                value = data[key]
                if value is None or value == '':
                    replacekwargs["data"].pop(key)
            logger.info("data:{}".format(replacekwargs["data"]))
        elif "json" in replacekwargs:
            json_data = replacekwargs["json"]
            for key in list(json_data.keys()):
                value = json_data[key]
                if value is None or value == '':
                    replacekwargs['json'].pop(key)
            logger.info("json:{}".format(replacekwargs["json"]))
        elif "file" in replacekwargs:
            file_path = replacekwargs["file"]["file_path"]
            with open(os.getcwd() + file_path, 'rb') as f:
                file_content = f.read()
            file = (os.path.basename(os.getcwd() + file_path), file_content, 'image/jpeg')
            if 'eventId' in replacekwargs["file"]:
                files = {
                    'files': file,
                    'eventId': replacekwargs["file"]["eventId"],
                }
            # elif '/dx-client-server/setting/upload' in url:
            #     files = {
            #         'file': file
            #     }
            else:
                files = {
                    'file': file
                }
            encoder = MultipartEncoder(files)
            headers["Content-Type"] = encoder.content_type
            replacekwargs["data"] = encoder
            replacekwargs.pop('file')
        elif "params" in replacekwargs:
            params = replacekwargs["params"]
            for key in list(params.keys()):
                value = params[key]
                if value is None or value == '':
                    replacekwargs["params"].pop(key)
            logger.info("params:{}".format(replacekwargs["params"]))
        requests.packages.urllib3.disable_warnings()
        res = self.sess.request(method, url=url, headers=headers, verify=False, timeout=15, **replacekwargs)
        return res

    def header(self, time1, token):
        headers = {
            "Authorization": "Bearer {}".format(token),
            "Content-Type": "application/json; charset=utf-8",
            "sec-ch-ua-mobile": "?0",
            "sec-ch-ua-platform": "Windows",
            "sec-fetch-dest": "empty",
            "sec-fetch-mode": "cors",
            "sec-fetch-site": "same-origin",
            # ,
            # "Referer": "https://integrative-web-fat.ak12.cc/",
            # "x-forwarded-for": "integrative-web-fat.ak12.cc"
        }
        return headers
