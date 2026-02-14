#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import sys
import unittest


SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PYTHON_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, ".."))
if PYTHON_DIR not in sys.path:
    sys.path.insert(0, PYTHON_DIR)

from counter_safety_utils import calculate_safe_consumption


class CounterSafetyUtilsTests(unittest.TestCase):
    def test_missing_counter_key_in_previous_row_is_handled(self):
        consumption, current_counter, warnings = calculate_safe_consumption(
            123.4,
            {"unexpected": 1},
        )
        self.assertEqual(consumption, 0.0)
        self.assertEqual(current_counter, 123.4)
        self.assertIn("missing_counter_key", warnings)

    def test_tuple_previous_row_is_supported(self):
        consumption, current_counter, warnings = calculate_safe_consumption(
            "20",
            (10,),
        )
        self.assertEqual(consumption, 10.0)
        self.assertEqual(current_counter, 20.0)
        self.assertEqual(warnings, [])

    def test_invalid_current_counter_defaults_to_zero(self):
        consumption, current_counter, warnings = calculate_safe_consumption(
            None,
            {"counter": 5},
        )
        self.assertEqual(consumption, 0.0)
        self.assertEqual(current_counter, 0.0)
        self.assertIn("missing_or_invalid_current_counter", warnings)
        self.assertIn("counter_reset_or_rollover", warnings)

    def test_counter_rollover_is_clamped_to_zero(self):
        consumption, current_counter, warnings = calculate_safe_consumption(
            99,
            {"counter": 100},
        )
        self.assertEqual(consumption, 0.0)
        self.assertEqual(current_counter, 99.0)
        self.assertIn("counter_reset_or_rollover", warnings)


if __name__ == "__main__":
    unittest.main()

