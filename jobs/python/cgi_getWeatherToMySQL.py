#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import sys
from datetime import datetime

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(SCRIPT_DIR, "modules"))
sys.path.insert(0, SCRIPT_DIR)

try:
    import requests
except ImportError:
    requests = None

try:
    import pymysql
    import pymysql.cursors
except ImportError:
    pymysql = None

REQUEST_EXCEPTION = requests.RequestException if requests is not None else Exception
MYSQL_EXCEPTION = pymysql.MySQLError if pymysql is not None else Exception

EXIT_SUCCESS = 0
EXIT_CONFIG_ERROR = 1
EXIT_HTTP_ERROR = 2
EXIT_DATABASE_ERROR = 3
EXIT_DATA_VALIDATION_ERROR = 4
EXIT_GENERAL_ERROR = 99

DEFAULT_LAT = "51.085620"
DEFAULT_LON = "7.192630"
DEFAULT_API_KEY = "3263986d38dc6001ed46f3b327841ac4"
DEFAULT_API_BASE_URL = "https://api.openweathermap.org/data/2.5/weather"


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


def to_float(value, field_name: str) -> float:
    try:
        return float(value)
    except (TypeError, ValueError):
        raise ValueError(f"Ungueltiger numerischer Wert fuer '{field_name}': {value}")


def to_int(value, field_name: str) -> int:
    try:
        return int(value)
    except (TypeError, ValueError):
        raise ValueError(f"Ungueltiger Ganzzahl-Wert fuer '{field_name}': {value}")


def build_api_url(config_module) -> str:
    lat = str(getattr(config_module, "weather_lat", DEFAULT_LAT))
    lon = str(getattr(config_module, "weather_lon", DEFAULT_LON))
    api_key = str(getattr(config_module, "weather_api_key", DEFAULT_API_KEY))
    base_url = str(getattr(config_module, "weather_api_base_url", DEFAULT_API_BASE_URL))
    return f"{base_url}?lat={lat}&lon={lon}&units=metric&APPID={api_key}"


def fetch_weather_data(url: str, timeout_seconds: int):
    if requests is None:
        raise RuntimeError("Das Modul 'requests' ist nicht installiert.")

    response = requests.get(url, timeout=timeout_seconds)
    response.raise_for_status()
    payload = response.json()
    if not isinstance(payload, dict):
        raise ValueError("API-Antwort hat kein gueltiges JSON-Objekt geliefert.")
    return payload


def extract_values(payload) -> tuple:
    main = payload.get("main")
    clouds = payload.get("clouds")
    rain = payload.get("rain", {})

    if not isinstance(main, dict):
        raise ValueError("Fehlendes Objekt 'main' in Wetterdaten.")
    if not isinstance(clouds, dict):
        raise ValueError("Fehlendes Objekt 'clouds' in Wetterdaten.")
    if not isinstance(rain, dict):
        rain = {}

    current_time = datetime.now().strftime("%Y-%m-%d %H:%M:00")
    return (
        current_time,
        to_float(main.get("temp"), "main.temp"),
        to_float(main.get("feels_like"), "main.feels_like"),
        to_float(main.get("temp_min"), "main.temp_min"),
        to_float(main.get("temp_max"), "main.temp_max"),
        to_int(main.get("pressure"), "main.pressure"),
        to_int(main.get("humidity"), "main.humidity"),
        to_int(clouds.get("all", 0), "clouds.all"),
        to_float(rain.get("1h", 0.0), "rain.1h"),
        to_float(rain.get("3h", 0.0), "rain.3h"),
    )


def insert_weather_row(config_module, values: tuple) -> None:
    if pymysql is None:
        raise RuntimeError("Das Modul 'pymysql' ist nicht installiert.")

    connection = pymysql.connect(
        host=config_module.db_host,
        user=config_module.db_user,
        password=config_module.db_password,
        database=config_module.db_database,
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=False,
    )

    sql = (
        "INSERT INTO ffd_weather "
        "(dateTime, temp, feels_like, temp_min, temp_max, pressure, humidity, cloud, rain_1h, rain_3h) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
    )

    try:
        with connection.cursor() as cursor:
            cursor.execute(sql, values)
        connection.commit()
    except Exception:
        connection.rollback()
        raise
    finally:
        connection.close()


def main() -> int:
    print_headers()

    try:
        config = load_config()
    except Exception as exc:  # pylint: disable=broad-except
        print(f"Fehler beim Laden der Konfiguration: {exc}")
        log_error(f"CONFIG_ERROR: {exc}")
        return EXIT_CONFIG_ERROR

    api_url = build_api_url(config)
    timeout_seconds = int(getattr(config, "weather_request_timeout_seconds", 15))

    try:
        payload = fetch_weather_data(api_url, timeout_seconds)
        values = extract_values(payload)
        insert_weather_row(config, values)
        print("OK: Wetterdaten erfolgreich eingefuegt.")
        return EXIT_SUCCESS
    except REQUEST_EXCEPTION as exc:
        print("Fehler: Wetter-API konnte nicht abgefragt werden.")
        log_error(f"HTTP_ERROR: {exc}")
        return EXIT_HTTP_ERROR
    except MYSQL_EXCEPTION as exc:
        print("Fehler: Datenbankzugriff fehlgeschlagen.")
        log_error(f"DATABASE_ERROR: {exc}")
        return EXIT_DATABASE_ERROR
    except ValueError as exc:
        print(f"Fehler: Ungueltige Wetterdaten ({exc}).")
        log_error(f"VALIDATION_ERROR: {exc}")
        return EXIT_DATA_VALIDATION_ERROR
    except RuntimeError as exc:
        print(f"Fehler: {exc}")
        log_error(f"RUNTIME_ERROR: {exc}")
        return EXIT_CONFIG_ERROR
    except Exception as exc:  # pylint: disable=broad-except
        print(f"Fehler: Unerwarteter Fehler ({exc}).")
        log_error(f"GENERAL_ERROR: {exc}")
        return EXIT_GENERAL_ERROR


if __name__ == "__main__":
    sys.exit(main())
