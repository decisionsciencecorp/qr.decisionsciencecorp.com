#!/usr/bin/env python3
"""API smoke tests — run: python3 tests/api_smoke.py [base_url]"""
from __future__ import annotations

import base64
import json
import sys
import urllib.error
import urllib.request

BASE = (sys.argv[1] if len(sys.argv) > 1 else "https://qr.decisionsciencecorp.com").rstrip("/")


def post(path: str, body: dict, *, allow_error: bool = False) -> dict:
    data = json.dumps(body).encode()
    req = urllib.request.Request(
        f"{BASE}{path}",
        data=data,
        headers={"Content-Type": "application/json", "Accept": "application/json"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return json.loads(r.read().decode())
    except urllib.error.HTTPError as e:
        payload = e.read().decode()
        if allow_error:
            try:
                return json.loads(payload)
            except json.JSONDecodeError:
                return {"success": False, "error": payload, "http_status": e.code}
        raise


def get(path: str) -> dict:
    with urllib.request.urlopen(f"{BASE}{path}", timeout=30) as r:
        return json.loads(r.read().decode())


def main() -> int:
    health = get("/api/v1/health.php")
    assert health.get("success"), health
    assert health.get("service") == "qr-code-studio"

    norm = post("/api/v1/normalize.php", {"content": "hello@gnail.com", "type": "email"})
    assert norm.get("success"), norm
    assert norm.get("type") == "email"
    assert "gmail" in (norm.get("suggestion") or "")

    gen_png = post(
        "/api/v1/generate.php",
        {"content": "https://example.com", "format": "png", "ecl": "M"},
        allow_error=True,
    )
    if gen_png.get("success"):
        raw = base64.b64decode(gen_png["data_base64"])
        assert raw[:8] == b"\x89PNG\r\n\x1a\n", "expected PNG magic"
    else:
        gen_svg = post("/api/v1/generate.php", {"content": "https://example.com", "format": "svg", "ecl": "M"})
        assert gen_svg.get("success"), gen_svg
        svg = base64.b64decode(gen_svg["data_base64"]).decode()
        assert "<svg" in svg.lower(), "expected SVG output (GD may be unavailable locally)"

    print(f"OK — API smoke passed against {BASE}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
