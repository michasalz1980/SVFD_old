#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import sys
import unittest


SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PYTHON_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, ".."))
if PYTHON_DIR not in sys.path:
    sys.path.insert(0, PYTHON_DIR)

from water_counter_utils import calculate_consumption, to_number


class WaterCounterUtilsTests(unittest.TestCase):
    def test_to_number_handles_invalid_values(self):
        self.assertIsNone(to_number(None))
        self.assertIsNone(to_number("abc"))
        self.assertIsNone(to_number(True))

    def test_consumption_for_first_entry_defaults_to_zero(self):
        consumption, reason = calculate_consumption(123.0, None)
        self.assertEqual(consumption, 0.0)
        self.assertEqual(reason, "missing_previous_counter")

    def test_consumption_for_counter_reset_defaults_to_zero(self):
        consumption, reason = calculate_consumption(90.0, 100.0)
        self.assertEqual(consumption, 0.0)
        self.assertEqual(reason, "counter_reset_or_rollover")

    def test_consumption_calculation_with_valid_values(self):
        consumption, reason = calculate_consumption("150.5", "100.0")
        self.assertAlmostEqual(consumption, 50.5)
        self.assertIsNone(reason)

    def test_missing_current_counter_is_validation_error(self):
        consumption, reason = calculate_consumption(None, 100.0)
        self.assertIsNone(consumption)
        self.assertEqual(reason, "missing_current_counter")


if __name__ == "__main__":
    unittest.main()
