import allure
import pytest

from commons.parametrize_util import read_testcase
from commons.request_util import RequestUtil


@allure.epic("DX接口自动化平台")
@allure.feature("中控后台-基础功能")
class TestExample:

    @pytest.mark.run(order=1)
    @pytest.mark.parametrize("caseinfo", read_testcase("api/ams_manager/auth/ams_manager_login.yaml"))
    @pytest.mark.ams_manager
    def test_ams_manager_login(self, caseinfo):
        RequestUtil().standard_yaml("ams_manager", caseinfo)

    # @pytest.mark.ams_manager
    # @pytest.mark.run(order=2)
    # @pytest.mark.parametrize("caseinfo", read_testcase("testsuite/example/merchant_manage_list.yaml"))
    # @pytest.mark.ams_manager
    # def test_merchant_manage_list(self, caseinfo):
    #     RequestUtil().standard_yaml("ams_manager", caseinfo)
