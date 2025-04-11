from enum import Enum


# 计算模式的枚举类型
class CalcMode(Enum):
    RESULT_ALL = 1  # 匹配所有响应数据
    RESULT_FIRST = 2  # 匹配第一个响应数据
    RESULT_LAST = 3  # 匹配最后一个响应数据
    MATCH_RESULT_FIELD = 4  # 匹配单个结果的字段
    MATCH_ALL_RESULT_FIELD = 5  # 匹配所有结果的字段
    GREATER_THAN = 6  # 大于
    LESS_THAN = 7  # 小于
    EQUALS_THAN = 8  # 等于
