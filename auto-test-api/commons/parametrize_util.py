import json
import logging
import os
import yaml

from commons.yaml_util import YamlUtil

logger = logging.getLogger(__name__)

platform = os.name
if platform == 'posix':
    path_separator = '/'
elif platform == 'nt':
    path_separator = '\\'
else:
    # 默认使用 '/'
    path_separator = '/'

def read_testcase(yaml_name, yaml_data_name=None, **keyword):
    with open(os.path.join(os.getcwd(), yaml_name), mode='r', encoding='utf-8') as f:
        caseinfo = yaml.load(f, yaml.FullLoader)
        # if len(caseinfo) >= 2:  # 判断yaml用例文件中有几条用例，当用例大于等于2时，直接返回caseinfo 待处理
        #     return caseinfo
        # else:  # 当等于1时，因为数据驱动后的caseinfo是字典列表我们就需要对caseinfo解包
        # 自定义参数化数据
        if yaml_data_name:
            parameterize = caseinfo[0]['parameterize']
            key = list(parameterize.keys())[0]
            parameterize[key] = yaml_data_name
            caseinfo[0]['parameterize'] = parameterize
        if "parameterize" in dict(*caseinfo).keys():
            new_caseinfo = ddt(*caseinfo, **keyword)
            return new_caseinfo
        else:
            return caseinfo


def ddt(caseinfo, **keyword):
    if "parameterize" in caseinfo.keys():
        caseinfo_str = json.dumps(caseinfo)  # 判断parameterize是否在caseinfo中
        for param_key, param_value in caseinfo["parameterize"].items():
            # key_list = param_key.split("-")  # 将param_key转成列表
            data_list = YamlUtil().read_yaml(param_value)
            key_list = data_list[0]
            # 读取param_value
            # 规范yaml数据文件的写法
            length_flag = True
            i = 1
            while i < len(data_list):
                data = data_list[i]
                if 'caseCode' in keyword.keys() and keyword['caseCode'] not in data:
                    data_list.pop(i)
                else:
                    i += 1
                if len(data) != len(key_list):
                    logging.warning("映射用例数据失败：data={} 和key_list={}；不匹配".format(data, key_list))
                    length_flag = False
                    break
            # 替换值
            new_caseinfo = []
            if length_flag:
                for x in range(1, len(data_list)):  # 循环数据的行数
                    temp_caseinfo = caseinfo_str
                    for y in range(0, len(data_list[x])):  # 循环数据列
                        if data_list[0][y] in key_list:
                            # 替换原始的yaml里面的$ddt{}
                            if isinstance(data_list[x][y], int) or isinstance(data_list[x][y], float):
                                temp_caseinfo = temp_caseinfo.replace('"$ddt{' + data_list[0][y] + '}"',
                                                                      str(data_list[x][y]))
                            else:
                                temp_caseinfo = temp_caseinfo.replace("$ddt{" + data_list[0][y] + "}",
                                                                      str(data_list[x][y]))
                    temp_caseinfo = json.loads(temp_caseinfo)
                    if "request" in temp_caseinfo:
                        request = temp_caseinfo["request"]
                        for key in list(request.keys()):
                            value = request[key]
                            if isinstance(value, dict):
                                for subKey in list(value.keys()):
                                    subValue = value[subKey]
                                    if subValue == "":
                                        del temp_caseinfo["request"][key][subKey]
                    new_caseinfo.append(temp_caseinfo)
        return new_caseinfo
    else:
        return caseinfo
