#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations

from typing import Any, Dict, List, Optional, Tuple


def _to_float(value: Any) -> Optional[float]:
    if value is None or isinstance(value, bool):
        return None
    try:
        return float(value)
    except (TypeError, ValueError):
        return None


def _extract_previous_counter(previous_row: Any) -> Tuple[Optional[float], Optional[str]]:
    if previous_row is None:
        return None, "missing_previous_counter"

    if isinstance(previous_row, dict):
        if "counter" not in previous_row:
            return None, "missing_counter_key"
        parsed = _to_float(previous_row.get("counter"))
        if parsed is None:
            return None, "invalid_previous_counter"
        return parsed, None

    if isinstance(previous_row, (list, tuple)):
        if not previous_row:
            return None, "empty_previous_counter_row"
        parsed = _to_float(previous_row[0])
        if parsed is None:
            return None, "invalid_previous_counter"
        return parsed, None

    parsed = _to_float(previous_row)
    if parsed is None:
        return None, "invalid_previous_counter"
    return parsed, None


def calculate_safe_consumption(
    current_counter_raw: Any, previous_row: Any
) -> Tuple[float, float, List[str]]:
    warnings: List[str] = []

    current_counter = _to_float(current_counter_raw)
    if current_counter is None:
        current_counter = 0.0
        warnings.append("missing_or_invalid_current_counter")

    previous_counter, previous_reason = _extract_previous_counter(previous_row)
    if previous_reason:
        warnings.append(previous_reason)
        return 0.0, current_counter, warnings

    consumption = current_counter - previous_counter
    if consumption < 0:
        warnings.append("counter_reset_or_rollover")
        return 0.0, current_counter, warnings

    return consumption, current_counter, warnings

