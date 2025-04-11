import asyncio
import json
import logging
import os
import time

import jsonpath
import pytest
from filelock import FileLock
from hotloads.debug_talk import DebugTalk

from commons.parametrize_util import read_testcase
from commons.request_util import RequestUtil
from commons.yaml_util import YamlUtil
import commons.notify_tools.tools as tools

logger = logging.getLogger(__name__)


def pytest_addoption(parser):
    parser.addoption("--env", default="test", choices=["dev", "test", "pre", "prod"], help="env parameter")
    parser.addoption("--buildurl", default="https://dzdepjen.ak12.cc/job/dx_autotest_v1_prod/", help="buildurl parameter")



@pytest.fixture(scope='session', autouse=True)
def set_env(request, tmp_path_factory):
    os.environ['env'] = request.config.getoption("--env")
    os.environ['buildurl'] = request.config.getoption("--buildurl")
    # login(tmp_path_factory, "proxy_manager")
    # login(tmp_path_factory, "merchant_manager")
    # login(tmp_path_factory, "client_server")


@pytest.fixture(scope="session", autouse=True)
def clean_extract():
    YamlUtil().clean_extract_yaml()
    # YamlUtil().clean_bet_yaml()

@pytest.fixture(scope="session", autouse=True)
def clean_json():
    DebugTalk().init_json(os.path.join(os.getcwd(), 'variable.json'))


@pytest.fixture(scope="session", autouse=True)
def failed_case_file_log():
    tools.write_file("./logs/failed_case_struct.log", "[")


# @pytest.fixture(scope="session", autouse=True)
# def login_first(base_url):
#     caseinfo = read_testcase("client_server/sign/sign_login.yaml")
#     res = RequestUtil().standard_yaml(base_url, caseinfo[0])
#     data = res.json()
#     token = jsonpath.jsonpath(data["data"], '$..token')[0]
#     YamlUtil().write_yaml({'token': token})


def login(tmp_path_factory, module):
    fn = tmp_path_factory.mktemp('login') / (module + 'data.json')
    with FileLock(str(fn) + '.lock'):
        if fn.is_file():
            time.sleep(1)
        else:
            fn.write_text(json.dumps("token"))
            set_token(module)


def set_token(module):
    if module == "proxy_manager":
        try:
            set_proxy_manager_token()
        except Exception as e:
            logger.error("登录失败：proxy_manager", e)
    if module == "merchant_manager":
        try:
            set_merchant_manager_token()
        except Exception as e:
            logger.error("登录失败：merchant_manager", e)
    if module == "client_server":
        try:
            set_client_server_token()
        except Exception as e:
            logger.error("登录失败：client_server", e)


def set_proxy_manager_token():
    module_name = "proxy_manager"
    caseinfo = read_testcase("proxy_manager/login/sign_login.yaml")
    get_proxy_manager_token(caseinfo, module_name)


def set_merchant_manager_token():
    module_name = "merchant_manager"
    caseinfo = read_testcase("merchant_manager/common/login.yaml")
    get_token(caseinfo, module_name)


def set_client_server_token():
    module_name = "client_server"
    caseinfo = read_testcase("client_server/login/sign_login.yaml")
    get_token(caseinfo, module_name)


def get_token(caseinfo, module_name):
    if isinstance(caseinfo, list):
        for case in caseinfo:
            if 'env' in case and os.environ['env'] == case['env']:
                res = RequestUtil().standard_yaml(module_name, case)
                resData = res.json()
                token = jsonpath.jsonpath(resData["data"], '$..token')[0]
                if 'userLevel' in case and case['userLevel'] != '' and case['userLevel'] != 'default':
                    os.environ[module_name + '_' + case['userLevel'] + '_token'] = token
                    if 'googleAuthCode' in case:
                        os.environ[module_name + '_' + case['userLevel'] + '_googleAuthCode'] = case[
                            'googleAuthCode']
                else:
                    os.environ[module_name + '_token'] = token
                    if 'googleAuthCode' in case:
                        os.environ[module_name + '_default_googleAuthCode'] = case['googleAuthCode']
    else:
        if 'env' in caseinfo and os.environ['env'] == caseinfo['env']:
            res = RequestUtil().standard_yaml(module_name, caseinfo)
            resData = res.json()
            token = jsonpath.jsonpath(resData["data"], '$..token')[0]
            if 'userLevel' in caseinfo and caseinfo['userLevel'] != '' and caseinfo['userLevel'] != 'default':
                os.environ[module_name + '_' + caseinfo['userLevel'] + '_token'] = token
                if 'googleAuthCode' in caseinfo:
                    os.environ[module_name + '_' + caseinfo['userLevel'] + '_googleAuthCode'] = caseinfo['googleAuthCode']
            else:
                os.environ[module_name + '_token'] = token
                if 'googleAuthCode' in caseinfo:
                    os.environ[module_name + '_default_googleAuthCode'] = caseinfo['googleAuthCode']
        # else:
        #     pytest.skip("当前环境：{}, case环境：{}, 跳过执行".format(os.environ['env'], caseinfo['env']))


def get_proxy_manager_token(caseinfo, module_name):
    if isinstance(caseinfo, list):
        for case in caseinfo:
            if 'env' in case and os.environ['env'] == case['env']:
                res = RequestUtil().standard_yaml(module_name, case)
                resData = res.json()
                token = resData["data"]["token"]
                if 'userLevel' in case and case['userLevel'] != '' and case['userLevel'] != 'default':
                    os.environ[module_name + '_' + case['userLevel'] + '_token'] = token
                else:
                    os.environ[module_name + '_token'] = token
    else:
        if 'env' in caseinfo and os.environ['env'] == caseinfo['env']:
            res = RequestUtil().standard_yaml(module_name, caseinfo)
            resData = res.json()
            token = resData["data"]["token"]
            if 'userLevel' in caseinfo and caseinfo['userLevel'] != '' and caseinfo['userLevel'] != 'default':
                os.environ[module_name + '_' + caseinfo['userLevel'] + '_token'] = token
            else:
                os.environ[module_name + '_token'] = token
    # else:
    #     pytest.skip("当前环境：{}, case环境：{}, 跳过执行".format(os.environ['env'], caseinfo['env']))
