#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import sys
import unittest


SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PYTHON_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, ".."))
if PYTHON_DIR not in sys.path:
    sys.path.insert(0, PYTHON_DIR)

from cgi_getWeatherToMySQL import extract_values


class WeatherIngestionTests(unittest.TestCase):
    def test_extract_values_with_valid_payload(self):
        payload = {
            "main": {
                "temp": 20.1,
                "feels_like": 19.0,
                "temp_min": 18.5,
                "temp_max": 21.2,
                "pressure": 1012,
                "humidity": 61,
            },
            "clouds": {"all": 32},
            "rain": {"1h": 0.5},
        }

        values = extract_values(payload)
        self.assertEqual(len(values), 10)
        self.assertEqual(values[1], 20.1)
        self.assertEqual(values[7], 32)
        self.assertEqual(values[8], 0.5)
        self.assertEqual(values[9], 0.0)

    def test_extract_values_requires_main(self):
        payload = {"clouds": {"all": 10}}
        with self.assertRaises(ValueError):
            extract_values(payload)

    def test_extract_values_defaults_rain_fields(self):
        payload = {
            "main": {
                "temp": 12,
                "feels_like": 11,
                "temp_min": 10,
                "temp_max": 13,
                "pressure": 1010,
                "humidity": 70,
            },
            "clouds": {"all": 90},
        }
        values = extract_values(payload)
        self.assertEqual(values[8], 0.0)
        self.assertEqual(values[9], 0.0)


if __name__ == "__main__":
    unittest.main()
