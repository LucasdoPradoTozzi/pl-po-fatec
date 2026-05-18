# Operations Lab Architecture

## Functional Flow

1. User describes LP problem in natural language.
2. Livewire calls `GenerateLinearModelFromDescription`.
3. `GitHubAiClient` sends prompt to GitHub AI endpoint.
4. `ModelGenerationParser` extracts and validates JSON schema.
5. User edits generated model.
6. On resolve, user chooses mode in modal:
   - Academic: `AcademicSimplexService` with full iteration history.
   - Industrial: `OrToolsClient` -> FastAPI -> OR-Tools.
7. Results are shown in modal while preserving page state.

## State Contract

Livewire state uses:

- `aiGenerated`
- `userEdited`
- `validationState`
- `academicResult`
- `industrialResult`
- `activeModal`
- `dirtyState`

When any problem field changes:

- invalidate cached solver outputs
- clear previous results
- reset simplex navigation index
- close active modal
- mark `dirtyState=true`

## Academic Mode Limits

Academic mode is disabled with explicit reason when:

- variables > 4
- constraints > 6
- objective type is not maximize
- integer programming detected
- non-linear characteristics detected
- constraints are not in `<=` form

## Expected AI JSON Schema

```json
{
  "title": "",
  "problem_summary": "",
  "is_valid_lp": true,
  "academic_mode_allowed": true,
  "complexity_reason": "",
  "warnings": [],
  "objective": {
    "type": "maximize",
    "expression": ""
  },
  "variables": [
    {
      "name": "x1",
      "meaning": ""
    }
  ],
  "constraints": [
    {
      "id": 1,
      "expression": ""
    }
  ],
  "non_negativity": [
    ""
  ],
  "detected_characteristics": {
    "integer_programming": false,
    "non_linear": false,
    "has_conditional_logic": false,
    "has_multiple_objectives": false
  }
}
```

## Components by Responsibility

- `DTO`: data contract and mapping
- `Support`: schema validation and state helper
- `Actions`: orchestration/use-cases
- `Services/AI`: LLM transport and safe parse
- `Services/Solvers/Academic`: educational simplex logic
- `Services/Solvers/Industrial`: OR-Tools HTTP integration
- `Livewire`: presentation and interaction state
