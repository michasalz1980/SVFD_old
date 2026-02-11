# config.py

# Modbus Konfiguration
modbus_server_ip = "iykjlt0jy435sqad.myfritz.net"
modbus_server_port = 8502
modbus_unit_id = 1

register_configs = [
    {"address": 110, "length": 4, "column": "counter"},
    {"address": 120, "length": 4, "column": "operational_health"},
    {"address": 140, "length": 4, "column": "operational_time"},
    {"address": 170, "length": 4, "column": "feed_actual"},
    {"address": 180, "length": 4, "column": "dc_power_input1"},
    {"address": 190, "length": 4, "column": "dc_power_input2"}
]

# Datenbank Konfiguration
db_host = 'localhost'
db_user = 'svfd_Schedule'
db_password = 'rq*6X4s82'
db_database = 'svfd_schedule'