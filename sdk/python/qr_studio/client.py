"""QR Code Studio HTTP client (AGPL-3.0-or-later)."""

from __future__ import annotations

import base64
import json
import os
import urllib.error
import urllib.request
from dataclasses import dataclass
from typing import Any, Dict, Optional


class QrStudioError(Exception):
    def __init__(self, message: str, http_status: int = 0, error_code: Optional[str] = None):
        super().__init__(message)
        self.http_status = http_status
        self.error_code = error_code


@dataclass
class GenerateResult:
    raw: Dict[str, Any]

    @property
    def data_base64(self) -> str:
        return str(self.raw.get("data_base64", ""))

    @property
    def mime_type(self) -> str:
        return str(self.raw.get("mime_type", "application/octet-stream"))

    def decode_image(self) -> bytes:
        return base64.b64decode(self.data_base64)


class QrStudioClient:
    def __init__(
        self,
        base_url: Optional[str] = None,
        api_key: Optional[str] = None,
        timeout: int = 30,
    ):
        self.base_url = (base_url or os.environ.get("QR_STUDIO_BASE_URL") or "https://qr.decisionsciencecorp.com").rstrip("/")
        self.api_key = api_key or os.environ.get("QR_STUDIO_API_KEY") or None
        self.timeout = timeout

    def health(self) -> Dict[str, Any]:
        return self._request("GET", "/api/v1/health.php")

    def normalize(self, content: str, type_: str = "auto") -> Dict[str, Any]:
        return self._request("POST", "/api/v1/normalize.php", {"content": content, "type": type_})

    def generate(self, content: str, **kwargs: Any) -> GenerateResult:
        body: Dict[str, Any] = {"content": content}
        key_map = {
            "type_": "type",
            "format": "format",
            "ecl": "ecl",
            "cell_size": "cell_size",
            "margin": "margin",
            "logo_base64": "logo_base64",
            "logo_size_pct": "logo_size_pct",
        }
        for k, api_k in key_map.items():
            if k in kwargs and kwargs[k] is not None:
                body[api_k] = kwargs[k]
            elif api_k in kwargs and kwargs[api_k] is not None:
                body[api_k] = kwargs[api_k]
        data = self._request("POST", "/api/v1/generate.php", body)
        return GenerateResult(data)

    def _request(self, method: str, path: str, body: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        url = f"{self.base_url}{path}"
        headers = {"Accept": "application/json"}
        data = None
        if body is not None:
            headers["Content-Type"] = "application/json"
            data = json.dumps(body).encode("utf-8")
        if self.api_key:
            headers["X-API-Key"] = self.api_key

        req = urllib.request.Request(url, data=data, headers=headers, method=method)
        try:
            with urllib.request.urlopen(req, timeout=self.timeout) as resp:
                raw = resp.read().decode("utf-8")
                status = resp.status
        except urllib.error.HTTPError as e:
            raw = e.read().decode("utf-8", errors="replace")
            status = e.code
        except urllib.error.URLError as e:
            raise QrStudioError(f"Connection error: {e.reason}") from e

        try:
            parsed = json.loads(raw)
        except json.JSONDecodeError as e:
            raise QrStudioError(f"Invalid JSON (HTTP {status})", status) from e

        if not parsed.get("success"):
            raise QrStudioError(
                str(parsed.get("error", "API error")),
                status,
                parsed.get("error_code"),
            )
        return parsed
