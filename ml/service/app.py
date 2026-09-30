"""
Risk assessment service for the payment paralysis prototype.

Laravel sends a client, the invoice being considered, and that client's earlier
invoices. This service decides which model applies, builds exactly the features
the notebooks trained on, and returns a calibrated probability, a tier, a
recommended action and a SHAP explanation.

Run with:  uvicorn app:app --reload --port 8001
"""
from datetime import date
from typing import List, Optional

import numpy as np
import pandas as pd
import joblib
import shap
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

MODELS = '../models/'
SETTLED_INVOICES_FOR_BEHAVIOUR = 3          # the handover rule

# Sector names used in the registration form, mapped to the two digit
# industry codes the profile model was trained on.
SECTOR_TO_CODE = {
    'agriculture': 11, 'mining and quarrying': 21, 'utilities': 22,
    'construction': 23, 'manufacturing': 31, 'wholesale': 42, 'retail': 44,
    'transport and logistics': 48, 'information and technology': 51,
    'financial services': 52, 'real estate': 53, 'professional services': 54,
    'administrative and support services': 56, 'education': 61,
    'health services': 62, 'arts and recreation': 71, 'hospitality': 72,
    'other services': 81,
}

FEATURE_LABELS = {
    'loanamount': 'Invoice amount', 'termdays': 'Payment terms',
    'prior_count': 'Earlier invoices', 'prior_settled': 'Invoices already settled',
    'prior_late_share': 'Share settled late', 'prior_early_share': 'Share settled early',
    'prior_avg_days_late': 'Average days late', 'prior_max_days_late': 'Worst days late',
    'last_days_late': 'Days late on the most recent invoice',
    'last3_late_share': 'Late share of the last three invoices',
    'prior_avg_amount': 'Usual invoice amount', 'amount_vs_avg': 'This amount against the usual',
    'days_since_last_closed': 'Days since the last settlement',
    'relationship_days': 'Length of the trading relationship', 'month': 'Month of issue',
    'sector': 'Business sector', 'is_new_business': 'Business under two years old',
    'size_band': 'Business size band',
}

app = FastAPI(title='Payment Paralysis Risk Service', version='1.0')

behaviour = joblib.load(MODELS + 'behaviour_model.joblib')
profile = joblib.load(MODELS + 'profile_model.joblib')
B_FEATURES, P_FEATURES = behaviour['features'], profile['features']
B_LOW, B_HIGH = behaviour['cutoffs']
P_LOW, P_HIGH = profile['cutoffs']


def _inner_tree_model(calibrated):
    """Reach the tree model inside a calibrated pipeline so SHAP can read it."""
    est = calibrated.calibrated_classifiers_[0].estimator
    est = getattr(est, 'estimator', est)                 # unwrap FrozenEstimator
    return est.named_steps['model'] if hasattr(est, 'named_steps') else est


BEHAVIOUR_CAL = behaviour['calibrated_models'][behaviour['chosen']]
BEHAVIOUR_TREE = _inner_tree_model(BEHAVIOUR_CAL)
PROFILE_CAL = profile['model']
PROFILE_TREE = _inner_tree_model(PROFILE_CAL)


class HistoryInvoice(BaseModel):
    amount: float
    issue_date: date
    due_date: date
    payment_date: Optional[date] = None       # None means still unpaid


class Client(BaseModel):
    sector: Optional[str] = None
    employees: Optional[int] = None
    years_in_operation: Optional[float] = None


class Invoice(BaseModel):
    amount: float
    issue_date: date
    due_date: date


class AssessmentRequest(BaseModel):
    client: Client
    invoice: Invoice
    history: List[HistoryInvoice] = Field(default_factory=list)


def _tier(p: float, low: float, high: float) -> str:
    return 'Low' if p < low else ('Medium' if p < high else 'High')


def _behaviour_features(req: AssessmentRequest, settled: pd.DataFrame) -> pd.DataFrame:
    """Build the fifteen features notebook 01 trained on, from settled invoices only."""
    issued = pd.Timestamp(req.invoice.issue_date)
    days_late = (settled.payment_date - settled.due_date).dt.days

    row = {
        'loanamount': req.invoice.amount,
        'termdays': (req.invoice.due_date - req.invoice.issue_date).days,
        'prior_count': len(req.history),
        'prior_settled': len(settled),
        'prior_late_share': float((days_late > 0).mean()),
        'prior_early_share': float((days_late < -3).mean()),
        'prior_avg_days_late': float(days_late.mean()),
        'prior_max_days_late': float(days_late.max()),
        'last_days_late': float(days_late.iloc[-1]),
        'last3_late_share': float((days_late.tail(3) > 0).mean()),
        'prior_avg_amount': float(settled.amount.mean()),
        'days_since_last_closed': (issued - settled.payment_date.max()).days,
        'relationship_days': (issued - settled.issue_date.min()).days,
        'month': req.invoice.issue_date.month,
    }
    row['amount_vs_avg'] = req.invoice.amount / row['prior_avg_amount'] if row['prior_avg_amount'] else np.nan
    return pd.DataFrame([row])[B_FEATURES]


def _profile_features(client: Client) -> pd.DataFrame:
    name = (client.sector or '').strip().lower()
    code = SECTOR_TO_CODE.get(name, SECTOR_TO_CODE['other services'])
    n = client.employees
    if n is None:
        size_band = 1
    else:
        size_band = 0 if n <= 4 else (1 if n <= 20 else (2 if n <= 50 else 3))
    is_new = 1 if client.years_in_operation is None else int(client.years_in_operation < 2)
    return pd.DataFrame([{'sector': code, 'is_new_business': is_new,
                          'size_band': size_band}])[P_FEATURES]


def _explain(tree_model, X: pd.DataFrame, top: int = 5):
    values = shap.TreeExplainer(tree_model).shap_values(X)
    values = np.asarray(values)
    if values.ndim == 3:                       # Random Forest returns one set per class
        values = values[:, :, 1]
    values = values[0]
    order = np.argsort(np.abs(values))[::-1][:top]
    return [{'feature': X.columns[i],
             'label': FEATURE_LABELS.get(X.columns[i], X.columns[i]),
             'value': float(X.iloc[0, i]),
             'impact': round(float(values[i]), 4),
             'direction': 'increases risk' if values[i] > 0 else 'lowers risk'}
            for i in order]


def _recommendation(tier: str, settled_count: int) -> str:
    if settled_count == 0:
        return ('No settled invoices yet. Start with a small first order or ask for a deposit, '
                'then relax the terms as invoices are settled on time.')
    return {
        'Low': 'Standard credit terms are appropriate for this client.',
        'Medium': 'Offer credit with a shorter payment window and follow up before the due date.',
        'High': 'Ask for a deposit or payment on delivery before releasing the goods.',
    }[tier]


@app.get('/health')
def health():
    return {'status': 'ok',
            'behaviour_model': behaviour['chosen'],
            'behaviour_features': len(B_FEATURES),
            'profile_features': len(P_FEATURES)}


@app.post('/assess')
def assess(req: AssessmentRequest):
    issued = pd.Timestamp(req.invoice.issue_date)

    settled = pd.DataFrame([h.model_dump() for h in req.history])
    if not settled.empty:
        for col in ('issue_date', 'due_date', 'payment_date'):
            settled[col] = pd.to_datetime(settled[col])
        # only invoices already paid before this one was issued: no peeking ahead
        settled = settled[settled.payment_date.notna() & (settled.payment_date < issued)]
        settled = settled.sort_values('payment_date')

    settled_count = len(settled)

    if settled_count >= SETTLED_INVOICES_FOR_BEHAVIOUR:
        X = _behaviour_features(req, settled)
        probability = float(BEHAVIOUR_CAL.predict_proba(X)[:, 1][0])
        tier = _tier(probability, B_LOW, B_HIGH)
        explanation = _explain(BEHAVIOUR_TREE, X)
        model_used, floor_applied = 'behaviour', False
    else:
        X = _profile_features(req.client)
        probability = float(PROFILE_CAL.predict_proba(X)[:, 1][0])
        tier = _tier(probability, P_LOW, P_HIGH)
        explanation = _explain(PROFILE_TREE, X)
        model_used = 'profile'
        # a client with nothing settled is never rated below Medium
        floor_applied = settled_count == 0 and tier == 'Low'
        if floor_applied:
            tier = 'Medium'

    return {
        'model_used': model_used,
        'settled_invoices': settled_count,
        'probability_late': round(probability, 4),
        'confidence': round(probability * 100, 1),
        'tier': tier,
        'medium_floor_applied': floor_applied,
        'recommendation': _recommendation(tier, settled_count),
        'explanation': explanation,
    }