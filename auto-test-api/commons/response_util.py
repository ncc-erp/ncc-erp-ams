
class ResponseUtil:

    @classmethod
    def accumulate_field_value(cls, data_list, field_name):
        """
        累积列表中某个字段的值
        :param data_list: 包含字典的列表
        :param field_name: 要累积的字段名
        :return: 字段值的累积值
        """
        total_value = 0

        for item in data_list:
            total_value += item.get(field_name, 0)

        return total_value