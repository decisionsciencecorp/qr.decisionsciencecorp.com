# Python SDK

**GitHub:** [sdk/python](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/sdk/python) · [`qr_studio/client.py`](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/blob/main/sdk/python/qr_studio/client.py) (AGPL-3.0)

## Requirements

- Python 3.9+
- Standard library only (`urllib`)

## Install

From a clone of [sdk/python](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/sdk/python):

```bash
pip install -e sdk/python
```

Or add `sdk/python` to `PYTHONPATH`.

## Usage

```python
from qr_studio import QrStudioClient

client = QrStudioClient("https://qr.decisionsciencecorp.com")
# client = QrStudioClient(base_url, api_key="...")

print(client.health())
norm = client.normalize("hello@gnail.com", type_="email")
gen = client.generate("https://decisionsciencecorp.com", format="png")

with open("qr.png", "wb") as f:
    f.write(gen.decode_image())
```

## Methods

| Method | Description |
|--------|-------------|
| `health()` | Service metadata |
| `normalize(content, type_="auto")` | Validate payload |
| `generate(content, **kwargs)` | PNG/SVG generation |

`generate()` kwargs: `type_`, `format`, `ecl`, `cell_size`, `margin`, `logo_base64`, `logo_size_pct`.

`GenerateResult.decode_image()` returns raw bytes from `data_base64`.

## Environment

| Variable | Purpose |
|----------|---------|
| `QR_STUDIO_BASE_URL` | Default base URL |
| `QR_STUDIO_API_KEY` | Optional API key |

## License

AGPL-3.0 — see [Licensing](licensing).
