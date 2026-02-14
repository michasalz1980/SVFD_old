#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from typing import Optional, Tuple


def to_number(value) -> Optional[float]:
    if value is None:
        return None
    if isinstance(value, bool):
        return None
    try:
        return float(value)
    except (TypeError, ValueError):
        return None


def calculate_consumption(current_counter, previous_counter) -> Tuple[Optional[float], Optional[str]]:
    current = to_number(current_counter)
    if current is None:
        return None, "missing_current_counter"

    previous = to_number(previous_counter)
    if previous is None:
        return 0.0, "missing_previous_counter"

    delta = current - previous
    if delta < 0:
        return 0.0, "counter_reset_or_rollover"

    return delta, None
