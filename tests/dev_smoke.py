"""DEV site smoke — requires PHP built-in server (not static-only).

Usage:
    cd public && php -S 127.0.0.1:8765 &
    python3 tests/dev_smoke.py
"""

from __future__ import annotations

import os
import pathlib
import sys

from playwright.sync_api import sync_playwright

BASE = os.environ.get("QR_SMOKE_URL", "http://127.0.0.1:8765/").rstrip("/") + "/"
ARTIFACTS = pathlib.Path(__file__).parent / "artifacts"
ARTIFACTS.mkdir(exist_ok=True)


def main() -> int:
    with sync_playwright() as pw:
        browser = pw.chromium.launch()

        for label, vw, vh in (("desktop-dev", 1280, 800), ("mobile-dev", 390, 844)):
            ctx = browser.new_context(viewport={"width": vw, "height": vh}, device_scale_factor=2)
            page = ctx.new_page()

            page.goto(BASE + "dev/index.php", wait_until="networkidle")
            assert "DEV" in page.inner_text("body")
            assert page.locator('a[href*="path=agents"]').count() >= 1
            page.screenshot(path=str(ARTIFACTS / f"{label}-hub.png"), full_page=True)

            page.goto(BASE + "dev/docs.php?path=agents", wait_until="networkidle")
            assert "agents.md" in page.inner_text("body").lower()
            page.screenshot(path=str(ARTIFACTS / f"{label}-agents.png"), full_page=True)

            page.goto(BASE + "dev/docs.php?path=api", wait_until="networkidle")
            assert "normalize.php" in page.inner_text("body")
            page.screenshot(path=str(ARTIFACTS / f"{label}-api.png"), full_page=True)

            ctx.close()

        browser.close()

    print(f"OK — DEV smoke passed ({BASE})")
    print(f"  artifacts: {ARTIFACTS}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
