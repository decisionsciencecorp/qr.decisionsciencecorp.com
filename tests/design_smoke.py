"""Design + functional smoke for QR Code Studio.

Run against a local static server (default http://127.0.0.1:8765). Captures
mobile + desktop screenshots and exercises the four content types end-to-end.

Usage:
    python3 -m http.server 8765 -d ../public &
    /path/to/.venv/bin/python tests/design_smoke.py
"""

from __future__ import annotations

import os
import pathlib
import sys
import time

from playwright.sync_api import sync_playwright

URL = os.environ.get("QR_SMOKE_URL", "http://127.0.0.1:8765/")
ARTIFACTS = pathlib.Path(__file__).parent / "artifacts"
ARTIFACTS.mkdir(exist_ok=True)


def shoot(page, name: str) -> pathlib.Path:
    out = ARTIFACTS / name
    page.screenshot(path=str(out), full_page=True)
    return out


def expect(condition: bool, label: str) -> None:
    status = "PASS" if condition else "FAIL"
    print(f"  [{status}] {label}")
    if not condition:
        raise AssertionError(label)


def run_case(page, label: str, value: str, expected_encoded_substr: str,
             expect_suggestion: bool = False, force_type: str | None = None) -> None:
    print(f"\n— {label}")
    if force_type:
        page.click(f'.type-pill[data-type="{force_type}"]')
        time.sleep(0.1)
    else:
        # Reset to auto each case.
        page.click('.type-pill[data-type="auto"]')
        time.sleep(0.1)

    page.fill("#content", value)
    time.sleep(0.35)  # debounce buffer
    encoded = page.inner_text("#encoded-value")
    expect(expected_encoded_substr in encoded,
           f'encoded contains "{expected_encoded_substr}" (got: {encoded!r})')

    banner_visible = page.is_visible("#suggestion-banner")
    if expect_suggestion:
        expect(banner_visible, "suggestion banner shown")
    else:
        expect(not banner_visible, "no suggestion banner")

    state = page.get_attribute("#qr-stage", "data-state")
    expect(state == "ready", f"qr-stage state ready (got {state!r})")
    canvas_size = page.evaluate(
        "() => ({w: document.getElementById('qr-canvas').width, "
        "       h: document.getElementById('qr-canvas').height})"
    )
    expect(canvas_size["w"] > 0 and canvas_size["h"] > 0,
           f"canvas non-empty {canvas_size}")


def main() -> int:
    with sync_playwright() as pw:
        browser = pw.chromium.launch()

        # Desktop
        ctx = browser.new_context(viewport={"width": 1280, "height": 800},
                                  device_scale_factor=2)
        page = ctx.new_page()
        print("→ desktop 1280×800")
        page.goto(URL, wait_until="networkidle")
        shoot(page, "desktop-empty.png")

        run_case(page, "URL: bare domain → infer https://",
                 "decisionsciencecorp.com",
                 "https://decisionsciencecorp.com")
        shoot(page, "desktop-url-bare.png")

        run_case(page, "URL: TLD typo .con → .com (suggestion)",
                 "example.con",
                 "https://example.con",
                 expect_suggestion=True)
        shoot(page, "desktop-url-tld-typo.png")

        run_case(page, "Email: gnail.com → gmail.com suggestion",
                 "alex@gnail.com",
                 "mailto:alex@gnail.com",
                 expect_suggestion=True)
        shoot(page, "desktop-email-typo.png")

        run_case(page, "Email: clean address (no suggestion)",
                 "hello@decisionsciencecorp.com",
                 "mailto:hello@decisionsciencecorp.com")

        run_case(page, "Phone: tel: encoding",
                 "+1 555 010 0123",
                 "tel:+15550100123")

        run_case(page, "Plain text (forced type)",
                 "Decision science wins.",
                 "Decision science wins.",
                 force_type="text")

        # Accept-suggestion flow
        print("\n— Suggestion accept flow")
        page.click('.type-pill[data-type="auto"]')
        page.fill("#content", "alex@gnail.com")
        time.sleep(0.35)
        expect(page.is_visible("#suggestion-banner"), "banner appears for typo")
        page.click("#suggestion-accept")
        time.sleep(0.35)
        new_value = page.input_value("#content")
        expect(new_value == "alex@gmail.com",
               f'accept replaces input (got {new_value!r})')
        expect(not page.is_visible("#suggestion-banner"),
               "banner hides after accept")
        shoot(page, "desktop-after-accept.png")

        ctx.close()

        # Mobile
        ctx = browser.new_context(
            viewport={"width": 390, "height": 844},
            device_scale_factor=3,
            user_agent=("Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) "
                        "AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148"),
        )
        page = ctx.new_page()
        print("\n→ mobile 390×844")
        page.goto(URL, wait_until="networkidle")
        shoot(page, "mobile-empty.png")
        page.fill("#content", "qr.decisionsciencecorp.com")
        time.sleep(0.35)
        shoot(page, "mobile-url.png")
        page.fill("#content", "alex@gnail.com")
        time.sleep(0.35)
        shoot(page, "mobile-email-typo.png")

        # Layout check: studio cards stack on mobile (no horizontal overflow)
        scroll = page.evaluate("() => document.documentElement.scrollWidth")
        client = page.evaluate("() => document.documentElement.clientWidth")
        expect(scroll <= client + 1,
               f"no horizontal overflow on mobile (scroll {scroll} vs client {client})")

        ctx.close()
        browser.close()

    print("\n✓ all smoke checks passed")
    print(f"  artifacts: {ARTIFACTS}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
