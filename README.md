# Payment Paralysis Prototype

This repository hosts the source code, datasets, diagrams, and documentation for the final-year Bachelor of Business Information Technology IS II project at Strathmore University.

## Project

**Title:** Ensemble Machine Learning for Mitigating Payment Paralysis in Kenyan Small and Medium Enterprises: A Decision Support Prototype

**Researcher:** Sandra Minoo Mbithi (167253)

**Supervisor:** Dr Allan Omondi

**Institution:** Strathmore University, School of Computing and Engineering Sciences

## Purpose

This project builds a decision support prototype that uses ensemble machine learning (Random Forest and Gradient Boosting) to sort the clients of Kenyan SMEs into low, medium, and high credit risk tiers, then recommends a specific risk control action for each tier through a prescriptive analytics dashboard. The aim is to help Kenyan SMEs screen their business clients before extending trade credit, which is identified in the literature as a major contributor to payment paralysis.

## Repository Structure

- `/laravel-app` — Laravel web application (Blade templates, Alpine.js)
- `/ml-service` — Python machine learning microservice with trained models
- `/notebooks` — Jupyter notebooks for data exploration and model evaluation
- `/datasets` — links to the four public datasets and any cleaned versions
- `/diagrams` — UML diagrams, wireframes, and the conceptual framework
- `/docs` — proposal, final report, user manual, and test plans

## Technology Stack

- Frontend: Laravel Blade with Alpine.js
- Backend: Laravel 11 (PHP 8.3)
- Machine learning: Python 3.12 with scikit-learn
- Explainability: SHAP
- Database: PostgreSQL 16
- Dashboard: Chart.js

## Repeatability

Access to this repository is provided to promote repeatability of the results. Another researcher should be able to clone the repository, follow the setup instructions and reproduce the trained models and the working prototype.

## License

Academic project, all rights reserved during the assessment period.
