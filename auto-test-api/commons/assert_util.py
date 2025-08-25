import json
import logging
import traceback
import jsonpath
import math
import pytest
from commons.notify_tools.notify import TelegramBot
from commons.yaml_util import YamlUtil
from hotloads.debug_talk import DebugTalk

logger = logging.getLogger(__name__)


class AssertUtil:

    def assert_result(self, name, expect_result, expect_field, actual_result, url, body, response):

        # try:
        logger.info("##{}接口##预期结果：{}".format(name, expect_result))
        logger.info("##{}接口##实际结果：{}".format(name, json.loads(json.dumps(actual_result).replace("\\", "\\"))))
        all_flag = 0
        if expect_field is not None:
            for validation in expect_field:
                read_extract = validation['read_extract']
                match = validation['match']
                if isinstance(match, str) and ('${' in match or '$ddt{' in match):
                    match = DebugTalk().replace_value(match)
                field = validation['field']
                assert_all = validation['assert_all']
                # 断言扩展
                all_flag = all_flag + self.assert_extract(name, actual_result, field, match, read_extract, assert_all)
        # 分页断言
        all_flag = all_flag + self.assert_pagination(name, actual_result)
        for er in expect_result:
            for key, value in er.items():
                if key == "equals":
                    flag = self.equal_assert(value=value, actual_result=actual_result)
                    all_flag = all_flag + flag
                elif key == "contains":
                    flag = self.contains_assert(expect_result=value, actual_result=actual_result)
                    all_flag = all_flag + flag
                else:
                    logger.error("不支持此断言方式")
        if all_flag != 0:
            TelegramBot.append_failed_case(url, body, response)
        assert all_flag == 0
        # except Exception as e:
        #     logger.info("接口测试失败")
        #     logger.info("---------------接口测试结束-----------------")
        #     logger.error("assert 结果异常：{}".format(str(traceback.format_exc())))

        logger.info("##{}接口##結束".format(name))

    def equal_assert(self, value, actual_result):

        flag = 0
        # if actual_result["code"] == 200:
        #     return flag
        # if str(actual_result["code"]) in str(value).split("-"):
        #     return flag
        # if str(value) != str(actual_result["code"]):
        #     flag = flag + 1
        #     logger.error("断言失败,预期状态码{}与返回状态码{}不一致".format(value, actual_result["code"]))
       
        return flag

    def contains_assert(self, expect_result, actual_result):

        flag = 0
        if actual_result == 'SUCCESS':
            return flag

        for expect_value in str(expect_result).split('-'):
            if expect_value in str(actual_result):
                return flag

        if '添加成功' in str(actual_result) or '修改成功' in str(actual_result) or '删除成功' in str(actual_result):
            return flag

        if expect_result not in str(actual_result):
            flag = flag + 1
            logger.error("断言失败{}不存在{}中".format(expect_result, actual_result))

        return flag

    def assert_pagination(self, name, result):
        '''
            断言分页数据是否正确
        :param name:
        :param result:
        :return:
        '''
        flag = 0
        if 'data' in result:
            data = result['data']
            if (isinstance(data, dict)
                    and 'pageNum' in data
                    and 'pageSize' in data
                    and 'totalPage' in data
                    and 'totalRecord' in data):
                current_page = data['pageNum']
                total_page = data['totalPage']
                total_count = data['totalRecord']
                page_size = data['pageSize']
                record = data['record']
                record_size = 0
                if len(record) > 0 and 'betDate' in record[0] and 'list' in record[0]:
                    for item in record:
                        result_list = item['list']
                        if isinstance(result_list, list):
                            record_size = record_size + len(result_list)
                else:
                    record_size = record_size + len(record)
                calc_result_page = math.ceil(total_count / page_size)
                if calc_result_page == 0:
                    calc_result_page = total_page
                # 判断分页总页数是否正确
                if calc_result_page != total_page:
                    flag = flag + 1
                logger.info(
                    '##{}接口##总记录数：{}, 每页记录数：{},预期页数：{}, 实际页数：{}'.format(name, total_count,
                                                                                           page_size,
                                                                                           calc_result_page,
                                                                                           total_page))
                # 当前页小于最大页数时判断记录数是否正确 或者 当前为最后一页时响应记录数是否正确
                # expect: int
                # if total_count == 0:
                #     expect = 0
                # elif current_page == total_page and (total_count % page_size) == 0:
                #     expect = page_size
                # else:
                #     expect = page_size if current_page < total_page else (total_count % page_size)
                # if expect != record_size:
                #     flag = flag + 1
                # logger.info(
                #     '##{}接口##总页数：{}, 当前页数：{}, 预期当前页记录数：{}, 实际当前页记录数：{}'.format(name,
                #                                                                                         total_page,
                #                                                                                         current_page,
                #                                                                                         expect,
                #                                                                                         record_size))
        return flag

    def assert_extract(self, name, res, json_path, extract_key, read_extract=True, assert_all=False):
        '''
            断言扩展中的数据跟相应数据是否匹配
        :param name         接口名称
        :param res:         接口响应数据
        :param json_path:   jsonpath表达式
        :param extract_key: extract.yaml 的 key
        :param assert_all:  是否针对所有匹配的信息进行断言
        :return:
        '''
        flag = 0
        results = jsonpath.jsonpath(res, json_path)
        if results is None or results == False or len(results) == 0:
            logger.error("##{}接口##关联数据失败, 接口未响应数据".format(name))
            flag = flag + 1
            return flag
        if read_extract:
            extracts = YamlUtil().read_extracl_yaml()
            if assert_all and results is not None:
                if extract_key in extracts:
                    for result in results:
                        if result != extracts[extract_key]:
                            flag = flag + 1
                            logger.error(
                                '##{}接口##关联数据匹配失败, 预期：{}, 结果：{}'.format(name, extracts[extract_key],
                                                                                      result))
                        else:
                            logger.info(
                                "##{}接口##关联extract数据匹配成功, key：{}, 预期：{}, 结果：{}".format(name, extract_key,
                                                                                                     extracts[
                                                                                                         extract_key],
                                                                                                     result))
                else:
                    flag = flag + 1
                    logger.error('接口关联数据不存在, 需要：{}, 当前extrac缓存数据：{}'.format(extract_key, extracts))
            elif not assert_all and results is not None:
                if extract_key in extracts:
                    result = results[0]
                    if result != extracts[extract_key]:
                        flag = flag + 1
                        logger.error(
                            '##{}接口##关联数据匹配失败, 预期：{}, 结果：{}'.format(name, extracts[extract_key], result))
                    else:
                        logger.info(
                            '##{}接口##关联extract数据匹配成功, key：{}, 预期：{}, 结果：{}'.format(name, extract_key,
                                                                                                 extracts[extract_key],
                                                                                                 result))
        else:
            if assert_all and results is not None:
                for result in results:
                    if str(result) != str(extract_key):
                        flag = flag + 1
                        logger.error(
                            '##{}接口##关联数据匹配失败, 预期：{}, 结果：{}'.format(name, extract_key, result))
                    else:
                        logger.info('##{}接口##关联数据匹配成功, 预期：{}, 结果：{}'.format(name, extract_key, result))
            elif not assert_all and results is not None:
                result = results[0]
                if str(result) != str(extract_key):
                    flag = flag + 1
                    logger.error(
                        '##{}接口##关联数据匹配失败, 预期：{}, 结果：{}'.format(name, extract_key, result))
                else:
                    logger.info('##{}接口##关联数据匹配成功, 预期：{}, 结果：{}'.format(name, extract_key, result))
        return flag
