"""Send ten assessment requests to the model service at the same time (NFR02)."""
import time
from concurrent.futures import ThreadPoolExecutor
import httpx

URL = 'http://127.0.0.1:8001/assess'
HISTORY = [{'amount': 40000, 'issue_date': f'2026-0{m}-01', 'due_date': f'2026-0{m}-28',
            'payment_date': f'2026-0{m}-27'} for m in range(1, 6)]


def one_user(n):
    body = {'client': {'sector': 'Retail', 'employees': 5, 'years_in_operation': 3},
            'invoice': {'amount': 30000 + n * 5000, 'issue_date': '2026-10-01', 'due_date': '2026-10-31'},
            'history': HISTORY}
    start = time.perf_counter()
    r = httpx.post(URL, json=body, timeout=60)
    ms = (time.perf_counter() - start) * 1000
    return n, r.status_code, r.json().get('tier'), ms


start = time.perf_counter()
with ThreadPoolExecutor(max_workers=10) as pool:
    results = list(pool.map(one_user, range(1, 11)))
total = (time.perf_counter() - start) * 1000

for n, status, tier, ms in results:
    print(f'User {n:2}: status {status}, tier {tier}, {ms:.0f} ms')
ok = sum(1 for r in results if r[1] == 200)
print(f'\n{ok} of 10 requests answered without an error')
print(f'All ten finished in {total:.0f} ms, slowest {max(r[3] for r in results):.0f} ms')
