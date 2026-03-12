# Evidence Directory

Save curl test output files here.

Each file should contain the full HTTP response (headers + body) from a curl test.

## How to save evidence

```bash
curl -i [options] > docs/evidence/filename.txt 2>&1
```

See `tests/curl-tests.md` for the complete list of commands.
