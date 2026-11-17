from fastapi.testclient import TestClient
from app import app

client = TestClient(app)

CLIENT = {'sector': 'Retail', 'employees': 5, 'years_in_operation': 3}
INVOICE = {'amount': 40000, 'issue_date': '2026-10-01', 'due_date': '2026-10-31'}


def paid(n, days_late=0):
    """n earlier invoices, each settled before 1 October 2026."""
    rows = []
    for i in range(n):
        m = i + 1
        rows.append({'amount': 40000, 'issue_date': f'2026-0{m}-01', 'due_date': f'2026-0{m}-28',
                     'payment_date': f'2026-0{m + 1}-{1 + days_late:02d}' if days_late else f'2026-0{m}-27'})
    return rows


def assess(history):
    return client.post('/assess', json={'client': CLIENT, 'invoice': INVOICE, 'history': history})


def test_health():
    r = client.get('/health')
    assert r.status_code == 200
    assert r.json()['behaviour_features'] == 15


def test_assess_rejects_empty_request():
    assert client.post('/assess', json={}).status_code == 422


def test_new_client_is_never_rated_low():
    r = assess([]).json()
    assert r['model_used'] == 'profile'
    assert r['tier'] in ('Medium', 'High')


def test_two_settled_invoices_use_profile_model():
    assert assess(paid(2)).json()['model_used'] == 'profile'


def test_three_settled_invoices_use_behaviour_model():
    assert assess(paid(3)).json()['model_used'] == 'behaviour'


def test_invoice_paid_after_issue_date_is_not_counted():
    history = paid(2) + [{'amount': 40000, 'issue_date': '2026-08-01', 'due_date': '2026-08-31',
                          'payment_date': '2026-11-05'}]
    r = assess(history).json()
    assert r['settled_invoices'] == 2
    assert r['model_used'] == 'profile'


def test_result_has_tier_reasons_and_advice():
    r = assess(paid(4)).json()
    assert r['tier'] in ('Low', 'Medium', 'High')
    assert 0 <= r['probability_late'] <= 1
    assert len(r['explanation']) > 0
    assert r['recommendation']
