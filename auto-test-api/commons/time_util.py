from datetime import datetime

class TimeUtil:
    @staticmethod
    def timestamp_to_yyyymmdd(timestamp_str):
        """
        Convert a timestamp to a formatted yyyymmdd string.

        Parameters:
        - timestamp (int): The input timestamp in milliseconds.

        Returns:
        int: The formatted yyyymmdd int.
        """
        # 将字符串类型的时间戳转换为整数
        timestamp = int(timestamp_str)
        dt_object = datetime.fromtimestamp(timestamp / 1000)
        formatted_date = dt_object.strftime("%Y%m%d")
        return int(formatted_date)

    @staticmethod
    def get_today_date():
        today = datetime.now()
        formatted_date = today.strftime("%Y%m%d")
        return int(formatted_date)

# 示例用法
timestamp_input = '1706595914648'
formatted_date_output = TimeUtil.timestamp_to_yyyymmdd(timestamp_input)

print("Input Timestamp:", timestamp_input)
print("Formatted Date:", formatted_date_output)
