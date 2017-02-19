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
                "category": "project",
                "name": "some_project_1"
            },
            {
                "category": "paid",
                "name": "yes"
            }
        ]
    },
    {
        "metric": "Product.Some.Metric2",
        "slices": "..."
    }
]
```
