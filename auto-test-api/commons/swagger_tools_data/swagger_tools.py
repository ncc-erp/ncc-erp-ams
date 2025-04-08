import logging
import os

import requests
import yaml

from jinja2 import Template

from commons.yaml_util import YamlUtil

logger = logging.getLogger(__name__)


def sendSwaggerRequest(yaml_path):
    project_path = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
    dataValue = YamlUtil().read_yaml(yaml_path)
    tag_map = dataValue["tag_map"]
    req_url = dataValue["req_url"]
    base_url_path = dataValue["base_url_path"]
    module_path = dataValue["module_path"]
    class_module_name = dataValue["class_module_name"]
    module_desc_name = dataValue["module_desc_name"]
    module_name = dataValue["module_name"]
    sub_module_desc_name = dataValue["sub_module_desc_name"]
    mark = dataValue["mark"]
    case_file_path = project_path + "/testcases/" + module_path
    data_file_path = project_path + "/datas/" + module_path
    file_data_root_path = "/datas/" + module_path
    res = requests.request("get", url=req_url)
    result = res.json()
    base_path = result["basePath"]
    if base_url_path is not None:
        base_path = base_url_path + base_path
    paths = result["paths"]
    definitions = result["definitions"]
    creat_class_list = []
    case_num = 0
    for path, detail in paths.items():
        params_name = "data"
        name = ""
        reqMethod = ""
        parameterize = "name-code-assert_str"
        url = base_path + path
        params = {}
        file_name = firstCharUp(path.split("/")[-2]) + firstCharUp(path.split("/")[-1])
        sub_path = ""
        print(url)
        flag = False
        for method, req in detail.items():

            reqMethod = method
            if "post" == reqMethod:
                params_name = "json"
            if "get" == reqMethod:
                params_name = "params"
            name = req["tags"][0] + "-" + req["summary"]
            if req["tags"][0] not in tag_map:
                flag = True
                break
            sub_path = tag_map[req["tags"][0]]
            parameters = req["parameters"]
            for param in parameters:
                if param["in"] == "body":
                    reqName = ""
                    if "type" in param["schema"]:
                        for idx, value in param["schema"].items():
                            if "originalRef" in value:
                                reqName = value["originalRef"]
                    elif "originalRef" in param["schema"]:
                        reqName = param["schema"]["originalRef"]
                    else:
                        reqName = param["schema"]["$ref"].split("/")[-1]
                    if reqName in definitions:
                        reqVo = definitions[reqName]
                        properties = reqVo["properties"]
                        for key, property in properties.items():
                            parameterize = parameterize + "-" + key
                            value = "$ddt{" + key + "}"
                            params[key] = value
            creat_class_obj = {"method_name": file_name,
                               "test_case_path": module_path + sub_path + "/" + file_name + ".yaml",
                               "tag": req["tags"][0],
                               "test_title": req["summary"]}
            creat_class_list.append(creat_class_obj)
        if flag:
            continue
        create_case_yaml(case_file_path + sub_path, parameterize, file_name, name, reqMethod, url, params_name,
                         params, data_file_path + sub_path, file_data_root_path + sub_path)
        case_num = case_num + 1
    creat_class_map = process_class_map_class_list(creat_class_list, tag_map)
    print("add case num {}".format(case_num))
    for title, value in creat_class_map.items():
        class_name = firstCharUp(class_module_name) + firstCharUp(title)
        create_class(class_name=title[0].upper() + title[1:],
                     module_desc_name=module_desc_name,
                     module_name=module_name,
                     sub_module_desc_name=sub_module_desc_name,
                     grandson_module_desc_name=title,
                     items=value,
                     py_name=case_file_path + title + "/" + "test_" + class_name,
                     mark=mark)


def firstCharUp(oriString):
    if oriString is None or '' == oriString:
        return oriString
    return oriString[0].upper() + oriString[1:]


def process_class_map_class_list(creat_class_list, tag_map):
    creat_class_map = {}
    for sub_creat_class in creat_class_list:
        tag = tag_map[sub_creat_class["tag"]]
        tag_sub_creat_class_list = []
        if tag in creat_class_map:
            tag_sub_creat_class_list = creat_class_map[tag]

        tag_sub_creat_class_list.append(sub_creat_class)
        creat_class_map[tag] = tag_sub_creat_class_list
    return creat_class_map


def create_case_yaml(base_path, parameterize, filePath, name, method, url, params_name, data,
                     base_data_path, file_data_root_path):
    case_data_case = file_data_root_path + "/" + filePath + "_data" + '.yaml'
    case_yaml_data = [
        {"name": "$ddt{name}",
         "parameterize":
             {"path": case_data_case},
         "validate": [
             {
                 "equals": "$ddt{code}"
             },
             {
                 "contains": "$ddt{assert_str}"
             }
         ],
         "request": {
             "method": method,
             "url":  url,
             params_name: data}
         }

    ]
    if not os.path.exists(base_path):
        os.makedirs(base_path)
    case_yaml_path = base_path + '/' + filePath + '.yaml'
    if not os.path.exists(case_yaml_path):
        with open(base_path + '/' + filePath + '.yaml', encoding="utf-8", mode='w') as f:
            yaml.dump(case_yaml_data, f, allow_unicode=True)
    else:
        print("case yaml exists：" + case_yaml_path)

    ## 写入data
    if not os.path.exists(base_data_path):
        os.makedirs(base_data_path)
    ##- ['name', 'memberId', 'staticsDate']
    source_value = name + "," + "200" + "," + "SUCCESS"
    for item in parameterize.split("-"):
        if item not in ['name', 'code', 'assert_str']:
            source_value = source_value + "," + item

    parameter_title_yaml_data = ["[" + parameterize.replace("-", ",") + "]", "[" + source_value + "]"]
    data_yaml_path = base_data_path + '/' + filePath + '_data.yaml'
    if not os.path.exists(data_yaml_path):
        with open(data_yaml_path, encoding="utf-8", mode='w') as f:
            yaml.dump(parameter_title_yaml_data, f, allow_unicode=True)
    else:
        print("data yaml exists：" + data_yaml_path)


def create_class(class_name,
                 module_desc_name,
                 module_name,
                 sub_module_desc_name,
                 grandson_module_desc_name,
                 items,
                 py_name,
                 mark):
    template = Template('''
import allure
import pytest      
from commons.parametrize_util import read_testcase
from commons.request_util import RequestUtil

        
@allure.epic("{{module_desc_name}}")
@allure.feature("{{sub_module_desc_name}}")
@allure.story("{{grandson_module_desc_name}}")        
class Test{{class_name}}Api:     
{% for item in items %}
    @pytest.mark.{{mark}}
    @allure.title("{{item.test_title}}")
    @allure.severity(allure.severity_level.NORMAL)
    @pytest.mark.parametrize("caseinfo", read_testcase("{{item.test_case_path}}"))
    @pytest.mark.run(order=2)
    def test_{{item.method_name}}(self, caseinfo):
        RequestUtil().standard_yaml("{{module_name}}", caseinfo)  
{% endfor %}
''')
    file_code = template.render(class_name=class_name, module_desc_name=module_desc_name, module_name=module_name,
                                sub_module_desc_name=sub_module_desc_name,
                                grandson_module_desc_name=grandson_module_desc_name,
                                items=items, mark=mark)
    with open(py_name + ".py", encoding="utf-8", mode='w') as file:
        file.write(file_code)


if __name__ == '__main__':
    proxy_manager = "/proxy_manager_swagger_data.yaml"
    merchant_manager_proxy = "/merchant_manager_proxy_swagger_data.yaml"
    merchant_manager_fund = "/merchant_manager_fund_swagger_data.yaml"
    merchant_manager_report = "/merchant_manager_report_swagger_data.yaml"
    merchant_manager_member = "/merchant_manager_member_swagger_data.yaml"
    merchant_manager_game = "/merchant_manager_game_swagger_data.yaml"
    merchant_manager_management = "/merchant_manager_management_swagger_data.yaml"
    merchant_manager_merchant = "/merchant_manager_merchant_swagger_data.yaml"
    merchant_manager_system = "/merchant_manager_system_swagger_data.yaml"
    merchant_manager_riskmanage = "/merchant_manager_riskmanage_swagger_data.yaml"
    merchant_manager_common = "/merchant_manager_common_swagger_data.yaml"
    client_server_proxyapp = "/client_server_proxyapp_swagger_data.yaml"
    client_server_gameapp = "/client_server_gameapp_swagger_data.yaml"
    client_server_commonapp = "/client_server_commonapp_swagger_data.yaml"
    client_server_walletapp = "/client_server_walletapp_swagger_data.yaml"
    client_server_paybackapp = "/client_server_paybackapp_swagger_data.yaml"
    sendSwaggerRequest(client_server_commonapp)
