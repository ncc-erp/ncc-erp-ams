# Environment

    python3
    allure-2.24.1(需要配置 path：***\allure-2.24.1\bin)

# Install Dependencies

```
cd auto-test-api/
pip install -r requirements.txt
```

# Start

```
python .\run.py --env test -m "last"
Jenkins run script:
sh "python3 ./run.py --alluredir=${WORKSPACE}/target/temps   --clean-alluredir -m \"v1_case or client_server\"  --env test"
To run on different platforms, enable the corresponding login location in the conftest.py file:
# login(tmp_path_factory, "proxy_manager")
login(tmp_path_factory, "merchant_manager")
# login(tmp_path_factory, "client_server")
```

# Folder Description

- **commons：** Encapsulation of common files
- **datas：** YAML data sources
- **hotloads:** Hot loading
- **log:** Log folder
- **report：** Reports
- **temps：** Temporary Allure reports
- **testsuite:** A collection of test cases that are intended to be used to test a software program to show that it has some specified set of behaviors
- **config.yaml** Global configuration file
- **conftest.py** Global fixture file
- **extract.yaml** Interface-related variables
- **pytest.ini** Global pytest configuration file
- **run.py** Global run file

---

# Test Case Specifications

1. Keywords required in `case.yaml`：
   ```
     name,request,validate;
     In request, must include：method，url
   ```
2. Parameter Passing
   - GET：Pass parameters via params
   - POST：
     - JSON: Pass parameters via json
     - Form: Pass parameters via data
   - File upload: Pass parameters via files
3. Interface Parameter Association
   ```
   Interface generating parameters:
    Single case: Add in case.yaml: extract: key1: $..token (jsonpath regex)
    Single case storing List: Add in case.yaml: extractList: key1: $..token (jsonpath regex)
    Multiple cases: Add caseCode in data.yaml and assign a unique value across the project
                  Add in case.yaml: extracts: key2: $..token (jsonpath regex)
   Interface using parameters:
    Single case: Add in case.yaml: key2:${read_extract_data(key1)}
    Multiple cases: Add fromCaseCode in data.yaml, matching the caseCode of the parameter-generating data.yaml
                    Add in case.yaml: key2:${read_extracts_data($ddt{fromCaseCode},key2)}
   ```
4. Interface Sequence Association

   ```
     Example: Interface A needs to call Interface B after completion, and Interface C also needs to call Interface B:
        1: In Interface A's data.yaml, add "after" and assign it the value: moduleName.methodName.caseCode of Interface B’s case (.py file)
            Define this method as a module method
        2: In Interface C, follow the same steps as Interface A
   ```

5. Hot Loading

   - Create a DebugTalk Python file in hotloads, create a class, and ensure the return method is a string
     Example:
     ```
       def get_random_number(self, mix, max):
         rm = random.randint(int(mix), int(max))
         return str(rm)
     ```
   - Usage in test cases:
     ```
      json:{"id":${get_random_number(1000,9999)}}
     ```

6. Assertions (supports equals and contains)
   ```
    Default: code=200 and msg='success' indicate success
   ```
   ```
     validate:
    - equals: $ddt{code}
    - contains: $ddt{assert_str}
   ```
7. Test Case Parameter Settings

- Writing in `data.yaml`:

  ```
  # Corresponding test case file: admin sign_login
  - ['name','userLevel','userName','userPwd','loginReference','deviceNo']
  - ['Login1','default','blaze001','abcd1234','https://integrative-web-fat.ak12.cc/','7c7ed73ef2309f5b08e394e1a8541f3a']
  - ['Login2','audit01','blaze002','abcd1234','https://integrative-web-fat.ak12.cc/','7c7ed73ef2309f5b08e394e1a8541f3a']
  - ['Login3','audit02','blaze003','abcd1234','https://integrative-web-fat.ak12.cc/','7c7ed73ef2309f5b08e394e1a8541f3a']
  Multi-user login in a single system:
    If blaze001 is needed, no action is required
    If blaze002 is needed, add 'userLevel' in _data.yaml with value 'audit01'
                          Add in _case.yaml: userLevel: $ddt{userLevel}
    If blaze003 is needed, add 'userLevel' in _data.yaml with value 'audit02'
                          Add in _case.yaml: userLevel: $ddt{userLevel}
  Single-user login in a single system:
    userLevel can be omitted or left unassigned
  For None values, assign an empty string ''
  For list values, separate multiple data with '-' in data.yaml
                  Use $list($ddt{fieldName}) in case.yaml
                  Use 'None' for empty values
  ```

- Writing in `case.yaml`:
  ```
   parameterize:
     path: /datas/product_manage/admin/sign_login_data.yaml
  ```
- Importing variables in `case.yaml`:
  ```
   $ddt{userName}
  ```

8. Marker Settings：

   ```
    markers =
    prod: Production
    test: Test
    last: New
    merchant_manager: Merchant backend
    client_server: Credit backend
    proxy_manager: Proxy backend
    query_case: Query-type interface
    v1_case: v1 interface

    Use in pytest.ini startup command: -m last
    Full v1 case: -m v1_case
    Production use: -m query_case (runs query-type interface tests for 3 platforms)
    Run v1_case for a specific platform: -m "v1_case and proxy_manager" (logical OR, AND, NOT)

   ```

9. Swagger Tools Usage：
   ```
   The tool parses swagger/api-docs JSON to generate corresponding xx.py, _本当に.yaml, and _data.yaml files
   1: Create an xx_swagger_data.yaml file in commons/swagger_tools_data
   2: In swagger_tools.py main method, import the xx_swagger_data file
    req_url: swagger/api-docs
    module_path: File generation path -merchant_manager/fund/
    class_module_name: Case file class name merchantManagerFund -> TestMerchantManagerFundxxApi
    module_desc_name: Project description -> Merchant backend
    module_name: Project abbreviation (used to distinguish related files) -> merchant_manager
    mark: Test case marker -> merchant_manager_fund
    sub_module_desc_name: Module name -> Fund module
    base_url_path: Prepended path for the full URL -> xxx_gateway
    tag_map: Swagger tag conversion map (convert Chinese tags to English for path generation) -> Refer to other files for details
   ```
10. Case Types for Different Environments：

```
- [ 'test', '会员资金明细-注单状态已结算','1700064000000','1700323199999' ,'' ,'1','10','1','932762661258043421','二十一点' ]
- [ 'dev','会员资金明细-体育分类记录','1701360000000','1701446399999' ,'ty' ,'1','10','3','938408480524021844','虚拟赛马' ]
```

Writing in the case .py file:

```
if 'env' in caseinfo and os.environ['env'] == caseinfo['env']:
      RequestUtil().standard_yaml("client_server", caseinfo)
else:
      pytest.skip("skip this test case")
```

Add the env field in the case `.yaml` file:

```
env: $ddt{env}
```

11. Execute SQL:

```
  1. In the root directory, add the SQL to be executed in the sql.yaml file, categorized by environment
    database: Database name
    sql: SQL to execute
  2. In the corresponding data file, add beforeSql: Execute before the request, afterSql: Execute after the request
  3. In the corresponding YAML file, add beforeSql: #ddt{beforeSql} / afterSql: #ddt{afterSql}

```
