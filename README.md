# Payment Paralysis Prototype

This repository hosts the source code, datasets, diagrams, and documentation for the final-year Bachelor of Business Information Technology capstone project at Strathmore University.

## Project

**Title:** Ensemble Machine Learning for Mitigating Payment Paralysis in Kenyan Small and Medium Enterprises: A Decision Support Prototype

**Researcher:** Sandra Minoo Mbithi (167253)

**Supervisor:** Dr Allan Omondi

**Institution:** Strathmore University, School of Computing and Engineering Sciences

## Purpose

This project builds a decision support prototype that uses ensemble machine learning (Random Forest and Gradient Boosting) to sort the clients of Kenyan SMEs into low, medium, and high credit risk tiers, then recommends a specific risk control action for each tier through a prescriptive analytics dashboard. The aim is to help Kenyan SMEs screen their business clients before extending trade credit, which is identified in the literature as a major contributor to payment paralysis.

## Repository structure

- `web/` — Laravel web application (Blade templates, Alpine.js, Tailwind CSS)
- `ml/` — Python machine learning work (data cleaning, notebooks, trained models)
- `docs/` — proposal, final report, user manual, and test plans

## Technology stack

| Layer | Tool |
|---|---|
| Frontend | Laravel Blade with Alpine.js and Tailwind |
| Backend | Laravel 13 (PHP 8.5) |
| Machine learning | Python with scikit-learn |
| Database | PostgreSQL 18 |
| Dashboard | Chart.js |

## Running this project locally

1. Clone the repository.
2. `cd web`, run `composer install`, then `npm install`.
3. Copy `.env.example` to `.env` and set the PostgreSQL database name, username and password.
4. Run `php artisan key:generate`.
5. Run `php artisan migrate`.
6. Run `npm run build` then `php artisan serve`.
7. Visit `http://127.0.0.1:8000` and register an account.

## Progress

- [x] Iteration 1: registration captures the SME profile
- [x] Iteration 2: client, invoice and monthly budget management
- [ ] Iteration 3: data preparation for model training
- [ ] Iteration 4: model training and evaluation
- [ ] Iteration 5: prescriptive dashboard integration

## Repeatability

Access to this repository is provided to promote repeatability of the results. Another researcher should be able to clone the repository, follow the setup instructions above, and reproduce the working prototype.

## License

Academic project. All rights reserved during the assessment period.