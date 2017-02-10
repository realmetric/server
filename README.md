# API points:
### POST: /track
```json
[
    {
        "metric": "Product.Some.Metric",
        "value": 123.78,
        "time": "",
        "slices": [
            {
                "name": "project",
                "value": "some_project_1"
            },
            {
                "name": "paid",
                "value": "yes"
            }
        ]
    },
    {
        "metric": "Product.Some.Metric2",
        "slices": "..."
    }
]
```
