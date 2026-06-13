#!/usr/bin/env python3
"""
QR Code Studio — SMCP Plugin CLI (AGPL-3.0-or-later)

Exposes normalize/generate API tools for Sanctum MCP hosts.
"""

from __future__ import annotations

import argparse
import json
import os
import sys
import urllib.error
import urllib.request
from typing import Any, Dict, List, Optional

PLUGIN_VERSION = "1.0.0"
DEFAULT_BASE = os.environ.get("QR_STUDIO_BASE_URL", "https://qr.decisionsciencecorp.com").rstrip("/")


def resolve_api_key(explicit: Optional[str]) -> str:
    if explicit and str(explicit).strip():
        return str(explicit).strip()
    return os.environ.get("QR_STUDIO_API_KEY", "").strip()


def api_request(
    method: str,
    path: str,
    body: Optional[Dict[str, Any]] = None,
    *,
    base_url: str,
    api_key: str,
) -> Dict[str, Any]:
    url = f"{base_url.rstrip('/')}{path}"
    headers = {"Accept": "application/json"}
    data = None
    if body is not None:
        headers["Content-Type"] = "application/json"
        data = json.dumps(body).encode("utf-8")
    if api_key:
        headers["X-API-Key"] = api_key

    req = urllib.request.Request(url, data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=45) as resp:
            raw = resp.read().decode("utf-8")
    except urllib.error.HTTPError as e:
        raw = e.read().decode("utf-8", errors="replace")
    except urllib.error.URLError as e:
        return {"status": "error", "error": f"Connection error: {e.reason}", "error_type": "network"}

    try:
        parsed = json.loads(raw)
    except json.JSONDecodeError:
        return {"status": "error", "error": "Invalid JSON from API", "error_type": "parse"}

    if not parsed.get("success"):
        return {
            "status": "error",
            "error": parsed.get("error", "API error"),
            "error_type": parsed.get("error_code", "api"),
            "response": parsed,
        }
    return {"status": "success", **{k: v for k, v in parsed.items() if k != "success"}}


def cmd_health(args: argparse.Namespace) -> Dict[str, Any]:
    return api_request("GET", "/api/v1/health.php", base_url=args.base_url, api_key=args.api_key)


def cmd_normalize(args: argparse.Namespace) -> Dict[str, Any]:
    return api_request(
        "POST",
        "/api/v1/normalize.php",
        {"content": args.content, "type": args.type},
        base_url=args.base_url,
        api_key=args.api_key,
    )


def cmd_generate(args: argparse.Namespace) -> Dict[str, Any]:
    body: Dict[str, Any] = {
        "content": args.content,
        "type": args.type,
        "format": args.format,
        "ecl": args.ecl,
        "cell_size": args.cell_size,
        "margin": args.margin,
        "logo_size_pct": args.logo_size_pct,
    }
    if args.logo_base64:
        body["logo_base64"] = args.logo_base64
    return api_request("POST", "/api/v1/generate.php", body, base_url=args.base_url, api_key=args.api_key)


def cmd_tool_help(args: argparse.Namespace) -> Dict[str, Any]:
    q = (args.query or "").lower()
    rec: List[str] = []
    if any(w in q for w in ("health", "ping", "status", "alive")):
        rec.append("health")
    if any(w in q for w in ("valid", "normal", "email", "url", "typo", "suggest")):
        rec.append("normalize")
    if any(w in q for w in ("qr", "png", "svg", "image", "generat", "render")):
        rec.append("generate")
    if not rec:
        rec = ["normalize", "generate", "health"]
    return {
        "status": "success",
        "query": args.query,
        "recommended_commands": rec,
        "documentation": f"{args.base_url}/dev/docs.php?path=agents",
        "openapi": f"{args.base_url}/api/v1/openapi.json",
    }


def get_description() -> Dict[str, Any]:
    return {
        "plugin": {
            "name": "qr_studio",
            "version": PLUGIN_VERSION,
            "description": "QR Code Studio — validate payloads and generate QR images via REST API",
        },
        "commands": [
            {
                "name": "health",
                "description": "Service health and endpoint index",
                "parameters": [
                    {"name": "base-url", "type": "string", "description": "API base URL", "required": False},
                    {"name": "api-key", "type": "string", "description": "Optional API key", "required": False},
                ],
            },
            {
                "name": "normalize",
                "description": "Validate and normalize QR content (URL, email, tel, text)",
                "parameters": [
                    {"name": "content", "type": "string", "description": "Raw content", "required": True},
                    {"name": "type", "type": "string", "description": "auto|url|email|tel|text", "required": False},
                    {"name": "base-url", "type": "string", "required": False},
                    {"name": "api-key", "type": "string", "required": False},
                ],
            },
            {
                "name": "generate",
                "description": "Generate PNG or SVG QR code (base64 in response)",
                "parameters": [
                    {"name": "content", "type": "string", "required": True},
                    {"name": "type", "type": "string", "required": False},
                    {"name": "format", "type": "string", "description": "png|svg", "required": False},
                    {"name": "ecl", "type": "string", "description": "L|M|Q|H", "required": False},
                    {"name": "cell-size", "type": "integer", "required": False},
                    {"name": "margin", "type": "integer", "required": False},
                    {"name": "logo-base64", "type": "string", "required": False},
                    {"name": "logo-size-pct", "type": "integer", "required": False},
                    {"name": "base-url", "type": "string", "required": False},
                    {"name": "api-key", "type": "string", "required": False},
                ],
            },
            {
                "name": "tool-help",
                "description": "Map intent keywords to recommended commands",
                "parameters": [
                    {"name": "query", "type": "string", "description": "Intent keywords", "required": False},
                    {"name": "base-url", "type": "string", "required": False},
                ],
            },
        ],
    }


def main() -> int:
    parser = argparse.ArgumentParser(description="QR Code Studio SMCP Plugin")
    parser.add_argument("--describe", action="store_true", help="Output plugin JSON for SMCP discovery")
    parser.add_argument("--base-url", dest="base_url", default=None, help=f"Base URL (default: {DEFAULT_BASE})")
    parser.add_argument("--api-key", dest="api_key", default=None, help="Optional API key")

    sub = parser.add_subparsers(dest="command")

    sub.add_parser("health", help="API health")

    p_norm = sub.add_parser("normalize", help="Normalize content")
    p_norm.add_argument("--content", required=True)
    p_norm.add_argument("--type", default="auto")

    p_gen = sub.add_parser("generate", help="Generate QR image")
    p_gen.add_argument("--content", required=True)
    p_gen.add_argument("--type", default="auto")
    p_gen.add_argument("--format", default="png", choices=["png", "svg"])
    p_gen.add_argument("--ecl", default="M", choices=["L", "M", "Q", "H"])
    p_gen.add_argument("--cell-size", type=int, default=8)
    p_gen.add_argument("--margin", type=int, default=4)
    p_gen.add_argument("--logo-base64", default=None)
    p_gen.add_argument("--logo-size-pct", type=int, default=22)

    p_help = sub.add_parser("tool-help", help="Intent → command hints")
    p_help.add_argument("--query", default="")

    args = parser.parse_args()

    if args.describe:
        print(json.dumps(get_description(), indent=2))
        return 0

    if not args.command:
        parser.print_help()
        return 2

    args.base_url = (args.base_url or DEFAULT_BASE).rstrip("/")
    args.api_key = resolve_api_key(args.api_key)

    handlers = {
        "health": cmd_health,
        "normalize": cmd_normalize,
        "generate": cmd_generate,
        "tool-help": cmd_tool_help,
    }
    result = handlers[args.command](args)
    print(json.dumps(result, indent=2))
    return 0 if result.get("status") == "success" else 1


if __name__ == "__main__":
    sys.exit(main())
