from __future__ import annotations

import re
import time
from typing import Dict, List, Tuple

from fastapi import FastAPI, HTTPException
from ortools.linear_solver import pywraplp
from pydantic import BaseModel

app = FastAPI(title="OR-Tools Solver API", version="1.0.0")

TERM_PATTERN = re.compile(r"([+\-]?\d*\.?\d*)\*?([a-zA-Z]\w*)")


class ProblemRequest(BaseModel):
    problem: dict


def parse_linear_expression(expression: str, variables: List[str]) -> List[float]:
    normalized = expression.replace(" ", "")
    if not normalized:
        raise ValueError("Expression is empty")

    terms = re.findall(r"[+\-]?[^+\-]+", normalized)
    coefficients = [0.0] * len(variables)

    for term in terms:
        match = TERM_PATTERN.fullmatch(term)
        if not match:
            raise ValueError(f"Unsupported term: {term}")

        coef_raw, var_name = match.groups()
        if var_name not in variables:
            raise ValueError(f"Unknown variable: {var_name}")

        if coef_raw in ("", "+"):
            coef = 1.0
        elif coef_raw == "-":
            coef = -1.0
        else:
            coef = float(coef_raw)

        idx = variables.index(var_name)
        coefficients[idx] += coef

    return coefficients


def parse_constraint(expression: str, variables: List[str]) -> Tuple[List[float], str, float]:
    operator = None
    for candidate in ("<=", ">=", "="):
        if candidate in expression:
            operator = candidate
            break

    if operator is None:
        raise ValueError("Constraint must contain <=, >= or =")

    lhs, rhs = expression.split(operator, 1)
    rhs_value = float(rhs.strip())
    lhs_coefficients = parse_linear_expression(lhs.strip(), variables)

    return lhs_coefficients, operator, rhs_value


@app.get("/health")
def health() -> dict:
    return {"status": "ok"}


@app.post("/solve")
def solve(payload: ProblemRequest) -> dict:
    problem = payload.problem

    try:
        variables_data = problem.get("variables", [])
        constraints_data = problem.get("constraints", [])
        objective = problem.get("objective", {})

        variable_names = [item["name"] for item in variables_data]
        if not variable_names:
            raise ValueError("No variables provided")

        solver = pywraplp.Solver.CreateSolver("GLOP")
        if not solver:
            raise ValueError("Could not initialize OR-Tools solver")

        x = {name: solver.NumVar(0.0, solver.infinity(), name) for name in variable_names}

        c = parse_linear_expression(objective.get("expression", ""), variable_names)
        obj = solver.Objective()
        for name, coeff in zip(variable_names, c):
            obj.SetCoefficient(x[name], coeff)

        if objective.get("type", "maximize") == "minimize":
            obj.SetMinimization()
        else:
            obj.SetMaximization()

        for c_item in constraints_data:
            coeffs, op, rhs = parse_constraint(c_item.get("expression", ""), variable_names)

            expr = solver.Sum(coeffs[i] * x[variable_names[i]] for i in range(len(variable_names)))

            if op == "<=":
                solver.Add(expr <= rhs)
            elif op == ">=":
                solver.Add(expr >= rhs)
            else:
                solver.Add(expr == rhs)

        start = time.perf_counter()
        status = solver.Solve()
        elapsed = time.perf_counter() - start

        if status not in (pywraplp.Solver.OPTIMAL, pywraplp.Solver.FEASIBLE):
            return {
                "status": "infeasible_or_unbounded",
                "objective_value": None,
                "variables": {},
                "metrics": {
                    "solve_time_sec": round(elapsed, 6),
                    "iterations": solver.iterations(),
                },
            }

        return {
            "status": "optimal" if status == pywraplp.Solver.OPTIMAL else "feasible",
            "objective_value": solver.Objective().Value(),
            "variables": {name: x[name].solution_value() for name in variable_names},
            "metrics": {
                "solve_time_sec": round(elapsed, 6),
                "iterations": solver.iterations(),
            },
        }
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc))
    except Exception as exc:
        raise HTTPException(status_code=500, detail=f"Solver error: {exc}")
