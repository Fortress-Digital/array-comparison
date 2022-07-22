
# Array Comparison

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fortress/array-comparison.svg?style=flat-square)](https://packagist.org/packages/fortress/array-comparison)
[![Tests](https://github.com/Fortress-Digital/array-comparison/actions/workflows/run-tests.yaml/badge.svg)](https://github.com/Fortress-Digital/array-comparison/actions/workflows/run-tests.yaml)

Compare two associative arrays and see the difference between them.

Supports nested associative arrays and array collections.

## Installation

You can install the package via composer:

```bash
composer require fortress/array-comparison
```

## Usage

```php
$expected = [
    'object' => [
        'to_be_removed' => [
            'item1',
            'item2',
        ],
        'to_be_changed' => [
            'item3',
            'item4',
        ]
    ]
];

$actual = [
    'object' => [
        'to_be_added' => [
            'item3',
            'item4',
        ],
        'to_be_changed' => [
            'item5',
            'item6',
        ],
    ]
];

$comparison = new Fortress\ArrayComparison();
$comparison->getDiff($expected, $actual);
```

Results in:

``` 
[
    'added' => [
        'object' => [
            'to_be_added' => [
                'item3',
                'item4',
            ],
        ]
    ],
    'removed' => [
        'object' => [
            'to_be_removed' => [
                'item1',
                'item2',
            ],
        ]
    ],
    'changed' => [
        'object' => [
            'to_be_changed' => [
                'old' => [
                    'item3',
                    'item4',
                ],
                'new' => [
                    'item5',
                    'item6',
                ],
            ],
        ],
    ],
]
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
