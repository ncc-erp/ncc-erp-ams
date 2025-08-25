import os

import yaml

prefix_path = 'auto-test-api'
class YamlUtil:

    def read_test_case_yaml(self, path):
        with open(os.getcwd() + '\\testcases\\' + path, encoding="utf-8") as f:
            value = yaml.load(stream=f, Loader=yaml.FullLoader)
            return value

    def read_yaml(self, path):
        with open(os.getcwd() + path, encoding="utf-8") as f:
            value = yaml.load(stream=f, Loader=yaml.FullLoader)
            return value

    def read_extracl_yaml(self):
        extract = os.getcwd() + '/extract.yaml'
        with open(extract, encoding="utf-8") as f:
            value = yaml.load(stream=f, Loader=yaml.FullLoader)
            return value

    def write_yaml(self, data):
        with open(os.getcwd() + "/extract.yaml", encoding="utf-8", mode="a") as f:
            yaml.dump(data, stream=f, allow_unicode=True)

    def write_bet_yaml(self, key, data):
        values = self.read_yaml("/bet.yaml")
        if values is not None:
            if key in values:
                values.update({key: data})
                with open(os.getcwd() +  "/bet.yaml", encoding='utf-8', mode='w') as f:
                    yaml.safe_dump(values, stream=f, default_flow_style=False)
            else:
                with open(os.getcwd() + "/bet.yaml", encoding='utf-8', mode='a') as f:
                    yaml.safe_dump({key: data}, stream=f, default_flow_style=False)
        else:
            with open(os.getcwd() + "/bet.yaml", encoding='utf-8', mode='w') as f:
                yaml.safe_dump({key: data}, stream=f, default_flow_style=False)


    def clean_extract_yaml(self):
        with open(os.getcwd() + "/extract.yaml", encoding="utf-8", mode="w") as f:
            f.truncate()
    def clean_bet_yaml(self):
        with open(os.getcwd()+ "/bet.yaml", encoding="utf-8", mode="w") as f:
            f.truncate()
