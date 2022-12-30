# Activate query parameter request validation
⚠ _**Please note:** This feature is still experimental as it might be missing validation of certain query parameter types._

Add the following YAML configuration to activate the query parameter request validation.

```yaml
# config/packages/nijens_openapi.yaml
nijens_openapi:
    # ...

    validation:
        enabled: true

        parameter_validation: true
```
