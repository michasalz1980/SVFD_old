#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import struct
import sys
from datetime import datetime
from typing import Any, Dict, List

try:
    from zoneinfo import ZoneInfo
except ImportError:  # pragma: no cover
    ZoneInfo = None

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(SCRIPT_DIR, "modules"))
sys.path.insert(0, SCRIPT_DIR)

from pyModbusTCP.client import ModbusClient  # noqa: E402
import pymysql.cursors  # noqa: E402

from water_counter_utils import calculate_consumption, to_number  # noqa: E402

EXIT_SUCCESS = 0
EXIT_CONFIG_ERROR = 1
EXIT_MODBUS_READ_ERROR = 2
EXIT_DATABASE_ERROR = 3
EXIT_DATA_VALIDATION_ERROR = 4
EXIT_GENERAL_ERROR = 99


def print_headers() -> None:
    print("Content-Type: text/plain; charset=utf-8")
    print()


def log_error(message: str) -> None:
    print(message, file=sys.stderr)


def load_config():
    config_path = os.path.join(SCRIPT_DIR, "config.py")
    if not os.path.exists(config_path):
        raise FileNotFoundError("Die Konfigurationsdatei 'config.py' wurde nicht gefunden.")
    # pylint: disable=import-outside-toplevel
    import config  # type: ignore

    return config


def get_counter_register_configs(config_module: Any) -> List[Dict[str, Any]]:
    configured = getattr(config_module, "frischwasser_register_configs", [])
    counter_configs = [cfg for cfg in configured if cfg.get("column") == "counter"]
    if counter_configs:
        return counter_configs

    # Fallback für alte Installationen
    return [{"address": 20, "length": 4, "column": "counter"}]


def read_counter(client: ModbusClient, register_configs: List[Dict[str, Any]]) -> float | None:
    counter_value: float | None = None
    for register in register_configs:
        address = int(register.get("address", 0))
        length = int(register.get("length", 0))
        column = register.get("column", "counter")

        if length <= 0:
            log_error(f"Ungültige Register-Länge für {column}: {length}")
            continue

        response = client.read_holding_registers(address, length)
        if not response:
            log_error(f"Keine Modbus-Antwort für {column} (Adresse {address}, Länge {length}).")
            continue
        if len(response) != length:
            log_error(
                f"Unvollständige Modbus-Antwort für {column}: "
                f"erwartet {length}, erhalten {len(response)}."
            )
            continue

        try:
            if length == 4:
                raw_value = struct.unpack(">Q", struct.pack(">HHHH", *response))[0]
            elif length == 2:
                raw_value = struct.unpack(">I", struct.pack(">HH", *response))[0]
            elif length == 1:
                raw_value = int(response[0])
            else:
                log_error(f"Nicht unterstützte Register-Länge für {column}: {length}")
                continue
        except struct.error as exc:
            log_error(f"Konvertierungsfehler für {column}: {exc}")
            continue

        parsed = to_number(raw_value)
        if parsed is None:
            log_error(f"Counter-Wert konnte nicht numerisch geparst werden: {raw_value}")
            continue
        counter_value = parsed

    return counter_value


def main() -> int:
    print_headers()
    de_timezone = None
    if ZoneInfo is not None:
        try:
            de_timezone = ZoneInfo("Europe/Berlin")
        except Exception:  # pylint: disable=broad-except
            de_timezone = None

    try:
        config = load_config()
    except Exception as exc:  # pylint: disable=broad-except
        print(f"Fehler beim Laden der Konfiguration: {exc}")
        log_error(f"CONFIG_ERROR: {exc}")
        return EXIT_CONFIG_ERROR

    server_ip = getattr(config, "modbus_server_ip", "iykjlt0jy435sqad.myfritz.net")
    server_port = int(getattr(config, "modbus_server_port", 8502))
    unit_id = int(getattr(config, "modbus_unit_id", 1))
    register_configs = get_counter_register_configs(config)

    if not register_configs:
        print("Fehler: Keine Register-Konfiguration für 'counter' gefunden.")
        log_error("VALIDATION_ERROR: register_configs is empty")
        return EXIT_CONFIG_ERROR

    client = ModbusClient(host=server_ip, port=server_port, unit_id=unit_id, auto_open=True, debug=False)
    connection = None
    try:
        counter_value = read_counter(client, register_configs)
        if counter_value is None:
            print("Fehler: Counter-Wert konnte nicht gelesen werden. Insert wird übersprungen.")
            log_error("MODBUS_READ_ERROR: counter missing, skipping insert")
            return EXIT_MODBUS_READ_ERROR

        connection = pymysql.connect(
            host=config.db_host,
            user=config.db_user,
            password=config.db_password,
            database=config.db_database,
            cursorclass=pymysql.cursors.DictCursor,
            autocommit=False,
        )

        with connection.cursor() as cursor:
            cursor.execute("SELECT counter FROM ffd_frischwasser ORDER BY id DESC LIMIT 1")
            last_counter_row = cursor.fetchone() or {}
            previous_counter = last_counter_row.get("counter")

            consumption, reason = calculate_consumption(counter_value, previous_counter)
            if consumption is None:
                connection.rollback()
                print("Fehler: Counter-Werte ungültig, Insert wird übersprungen.")
                log_error(
                    f"VALIDATION_ERROR: current={counter_value}, previous={previous_counter}, reason={reason}"
                )
                return EXIT_DATA_VALIDATION_ERROR

            if reason is not None:
                log_error(
                    f"COUNTER_WARNING: current={counter_value}, previous={previous_counter}, reason={reason}"
                )

            now_de = datetime.now(de_timezone).replace(second=0, microsecond=0) if de_timezone else datetime.now().replace(second=0, microsecond=0)
            source = "sensor"
            sql = (
                "INSERT INTO ffd_frischwasser (datetime, counter, consumption, source) "
                "VALUES (%s, %s, %s, %s)"
            )
            cursor.execute(sql, (now_de, counter_value, consumption, source))
            connection.commit()

        print("Daten erfolgreich eingefügt.")
        print(f"counter={counter_value}, consumption={consumption}")
        return EXIT_SUCCESS

    except pymysql.MySQLError as exc:
        if connection:
            connection.rollback()
        print("Fehler: Datenbankzugriff fehlgeschlagen.")
        log_error(f"DATABASE_ERROR: {exc}")
        return EXIT_DATABASE_ERROR
    except Exception as exc:  # pylint: disable=broad-except
        if connection:
            connection.rollback()
        print(f"Fehler: Unerwarteter Fehler ({exc}).")
        log_error(f"GENERAL_ERROR: {exc}")
        return EXIT_GENERAL_ERROR
    finally:
        try:
            client.close()
        except Exception:  # pylint: disable=broad-except
            pass
        if connection:
            connection.close()


if __name__ == "__main__":
    sys.exit(main())
