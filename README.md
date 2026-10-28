# Payment Paralysis Prototype

This repository hosts the source code, diagrams and documentation for the final year Bachelor of Business Information Technology capstone project at Strathmore University.

## Project

**Title:** Ensemble Machine Learning for Mitigating Payment Paralysis in Kenyan Small and Medium Enterprises: A Decision Support Prototype

**Researcher:** Sandra Minoo Mbithi (167253)

**Supervisor:** Dr Allan Omondi

**Institution:** Strathmore University, School of Computing and Engineering Sciences

## Purpose

This project builds a decision support prototype that uses ensemble machine learning (Random Forest and Gradient Boosting) to sort the clients of Kenyan SMEs into low, medium and high risk tiers of paying late. For each tier it recommends a risk control action and explains the result with SHAP. The aim is to help Kenyan SMEs screen their business clients before extending trade credit, which the literature identifies as a major contributor to payment paralysis.

## Repository structure

| Folder | Contents |
|---|---|
| `web/` | Laravel web application (Blade, Alpine.js, Tailwind CSS) |
| `ml/notebooks/` | The four Jupyter notebooks used to train and test the models |
| `ml/service/` | FastAPI service that loads the trained models and returns assessments |
| `ml/data/raw/` | Place the downloaded datasets here (not stored in Git) |
| `ml/models/` | Trained models are saved here when the notebooks run (not stored in Git) |
| `ml/reports/` | Results tables and figures produced by the notebooks |
| `docs/` | Proposal, final report, user manual and test plans |

## Technology stack

| Layer | Tool |
|---|---|
| Frontend | Laravel Blade with Alpine.js and Tailwind |
| Backend | Laravel 13 (PHP 8.5) |
| Machine learning | Python 3.14 with scikit-learn, imbalanced-learn and SHAP |
| Model service | FastAPI with Uvicorn |
| Database | PostgreSQL 18 |
| Dashboard | Chart.js |

## Datasets

The datasets are not included in this repository because of their size and licence terms. Download each one and save it in `ml/data/raw/` with the file name shown.

| Dataset | File name | Source | Licence | Used in |
|---|---|---|---|---|
| Loan Default Prediction Challenge (SuperLender, Nigeria) | `trainperf.csv`, `trainprevloans.csv` | Zindi | Zindi competition terms, not redistributed | Notebook 01 |
| SBA National loans (US small businesses) | `SBAnational.csv` | Kaggle, from Li, Mickel and Taylor (2018) | See source page | Notebook 02 |
| Customer invoices | `customer_invoice_kaggle.csv` | Kaggle | See source page | Notebook 03 |
| Default of Credit Card Clients (Taiwan) | `default_of_credit_card_clients.xls` | UCI Machine Learning Repository, Yeh (2009) | CC BY 4.0 | Notebook 04 |

### What each notebook does

| Notebook | Purpose |
|---|---|
| `01_behaviour_model.ipynb` | Trains the behaviour model for clients with at least three settled invoices |
| `02_profile_model.ipynb` | Trains the profile model for new clients using sector, size and business age |
| `03_method_test_invoices.ipynb` | Runs the same method on business invoices to test that it works on invoice data |
| `04_benchmark_uci.ipynb` | Checks the pipeline against published results to confirm the code is correct |

## Running this project locally

### 1. Train the models
1. `cd ml`, then create and activate a virtual environment (`python3 -m venv venv` and `source venv/bin/activate`).
2. Run `pip install -r requirements.txt`.
3. Download the datasets into `ml/data/raw/` as listed above.
4. Run notebooks 01 and 02 from top to bottom. They save `behaviour_model.joblib` and `profile_model.joblib` in `ml/models/`.

### 2. Start the model service
1. `cd ml/service`
2. Run `uvicorn app:app --reload --port 8001`
3. Check `http://127.0.0.1:8001/health` shows both models loaded.

### 3. Start the web application
1. `cd web`, run `composer install`, then `npm install`.
2. Copy `.env.example` to `.env` and set the PostgreSQL database name, username and password, plus `ML_SERVICE_URL=http://127.0.0.1:8001`.
3. Run `php artisan key:generate`, then `php artisan migrate`.
4. In one terminal run `npm run dev`, and in another run `php artisan serve`.
5. Visit `http://127.0.0.1:8000` and register an account.

## Progress

- [x] Iteration 1: registration captures the SME profile
- [x] Iteration 2: client, invoice and monthly budget management
- [x] Iteration 3: data preparation for model training
- [x] Iteration 4: model training and evaluation
- [x] Iteration 5: prescriptive dashboard integration
- [ ] Iteration 6: reports, export and testing

## Repeatability

Access to this repository is provided to promote repeatability of the results. Another researcher should be able to clone the repository, download the datasets listed above, run the notebooks and reproduce both the models and the working prototype.

## License

Academic project. All rights reserved during the assessment period.