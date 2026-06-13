# SMCP Plugin

**GitHub:** [smcp_plugin](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/smcp_plugin) · [`qr_studio/cli.py`](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/blob/main/smcp_plugin/qr_studio/cli.py) (AGPL-3.0)

Sanctum MCP-compatible CLI exposing QR Code Studio API tools to agents.

## Discovery

```bash
python3 smcp_plugin/qr_studio/cli.py --describe
```

Clone from [smcp_plugin on GitHub](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/smcp_plugin) or run from a full repo checkout.

Returns JSON with plugin metadata and command parameter schemas.

## Commands

| Command | Description |
|---------|-------------|
| `health` | `GET /api/v1/health.php` |
| `normalize` | Validate content (`--content`, optional `--type`) |
| `generate` | Generate QR (`--content`, `--format`, `--ecl`, …) |
| `tool-help` | Map intent keywords to recommended commands |

## Examples

```bash
export QR_STUDIO_BASE_URL=https://qr.decisionsciencecorp.com

python3 smcp_plugin/qr_studio/cli.py health

python3 smcp_plugin/qr_studio/cli.py normalize --content 'hello@gnail.com' --type email

python3 smcp_plugin/qr_studio/cli.py generate \
  --content 'https://decisionsciencecorp.com' \
  --format png --ecl M
```

With API key:

```bash
python3 smcp_plugin/qr_studio/cli.py --api-key "$QR_STUDIO_API_KEY" generate --content 'https://example.com'
```

## MCP server wiring

Point your SMCP governor at [`smcp_plugin/qr_studio/cli.py`](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/blob/main/smcp_plugin/qr_studio/cli.py) in a checkout of the repo:

```
python3 /path/to/qr.decisionsciencecorp.com/smcp_plugin/qr_studio/cli.py
```

Use `--describe` for tool registration. Subcommands map 1:1 to REST endpoints.

## Agents

See [agents.md](agents) for the full integration guide.

## License

AGPL-3.0
