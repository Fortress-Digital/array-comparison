<?php

namespace Fortress\ArrayComparison\Tests;

use Fortress\ArrayComparison\ArrayComparison;
use PHPUnit\Framework\TestCase;

final class ArrayComparisonTest extends TestCase
{
    public function testIdenticalArraysShowNoDifference(): void
    {
        $sut = new ArrayComparison();
        $expected = ['test' => true];
        $actual = $expected;

        self::assertEquals([], $sut->getDiff($expected, $actual));
    }

    /**
     * @dataProvider simpleDataProvider
     */
    public function testSimpleArrayReturnsDiff(array|string $expected, array|string $actual, array $result): void
    {
        $sut = new ArrayComparison();

        self::assertEquals($result, $sut->getDiff($expected, $actual));
    }

    public function testAssociativeArrayReturnsDiffWhenNestedFieldAdded(): void
    {
        $expected = [
            'zip' => 'BA1124',
            'city' => 'Cardiff',
            'user' => [
                'name' => 'Jeff',
            ],
        ];

        $actual = [
            'zip' => 'BA1124',
            'user' => [
                'name' => 'Jeff',
                'surname' => 'Smith',
            ],
            'city' => 'Cardiff',
            'county' => 'Cardiff',
        ];

        $sut = new ArrayComparison();

        self::assertEquals([
            'added' => [
                'county' => 'Cardiff',
                'user' => [
                    'surname' => 'Smith',
                ]
            ],
        ], $sut->getDiff($expected, $actual));
    }


    public function testAssociativeArrayReturnsDiffWhenNestedFieldRemoved(): void
    {
        $expected = [
            'zip' => 'BA1124',
            'county' => 'Cardiff',
            'city' => 'Cardiff',
            'user' => [
                'name' => 'Jeff',
                'surname' => 'Smith',
            ],
        ];

        $actual = [
            'zip' => 'BA1124',
            'user' => [
                'name' => 'Jeff',
            ],
            'city' => 'Cardiff',
        ];

        $sut = new ArrayComparison();

        self::assertEquals([
            'removed' => [
                'county' => 'Cardiff',
                'user' => [
                    'surname' => 'Smith',
                ]
            ],
        ], $sut->getDiff($expected, $actual));
    }

    public function testAssociativeArrayReturnsDiffWhenNestedFieldsChanged(): void
    {
        $expected = [
            'zip' => 'BA1124',
            'county' => 'Cardiff',
            'city' => 'Cardiff',
            'user' => [
                'name' => 'Jeff',
                'surname' => 'Smith',
            ],
        ];

        $actual = [
            'zip' => 'BA2125',
            'county' => 'Cardiff',
            'city' => 'Cardiff',
            'user' => [
                'name' => 'John',
                'surname' => 'Smith',
            ],
        ];

        $sut = new ArrayComparison();

        self::assertEquals([
            'changed' => [
                'user' => [
                    'name' => [
                        'old' => 'Jeff',
                        'new' => 'John',
                    ],
                ],
                'zip' => [
                    'old' => 'BA1124',
                    'new' => 'BA2125',
                ]
            ],
        ], $sut->getDiff($expected, $actual));
    }

    public function testNestedArrayListReturnedAsFullArrayWhenAddedRemovedOrEdited(): void
    {
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

        $sut = new ArrayComparison();

        self::assertEquals([
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
        ], $sut->getDiff($expected, $actual));
    }

    public function testCollectionsOfAssociativeArraysReturnAsNested(): void
    {
        $expected = [
            'object' => [
                'collection' => [
                    [
                        'name' => 'Item 1',
                    ],
                    [
                        'name' => 'Item 2',
                    ],
                ],
            ],
        ];

        $actual = [
            'object' => [
                'collection' => [
                    [
                        'name' => 'Item 1',
                    ],
                    [
                        'name' => 'Item 2 (updated)',
                    ],
                    [
                        'name' => 'Item 3',
                    ],
                ],
            ],
        ];

        $sut = new ArrayComparison();

        self::assertEquals([
            'added' => [
                'object' => [
                    'collection' => [
                        2 => [
                            'name' => 'Item 3',
                        ],
                    ],
                ],
            ],
            'changed' => [
                'object' => [
                    'collection' => [
                        1 => [
                            'name' => [
                                'new' => 'Item 2 (updated)',
                                'old' => 'Item 2',
                            ]
                        ],
                    ],
                ],
            ]
        ], $sut->getDiff($expected, $actual));
    }

    public function testInitStaticClass(): void
    {
        $expected = ['test' => true];
        $actual = $expected;

        self::assertEquals([], ArrayComparison::init()->getDiff($expected, $actual));
    }

    public function testInvalidExpectedJsonThrowsError(): void
    {
        $this->expectException(\JsonException::class);

        $actual = ['test' => true];

        ArrayComparison::init()->getDiff("Invalid", $actual);
    }

    public function testInvalidActualJsonThrowsError(): void
    {
        $this->expectException(\JsonException::class);

        $expected = ['test' => true];

        ArrayComparison::init()->getDiff($expected, "Invalid");
    }

    public function simpleDataProvider(): array
    {
        return [
            # Edited oranges to pears
            [
                'expected' => [
                    'apples',
                    'oranges',
                ],
                'actual' => [
                    'apples',
                    'pears',
                ],
                'result' => [
                    'changed' => [
                        1 => [
                            'old' => 'oranges',
                            'new' => 'pears',
                        ]
                    ]
                ]
            ],
            # Adds plums
            [
                'expected' => [
                    'apples',
                    'oranges',
                ],
                'actual' => [
                    'apples',
                    'oranges',
                    'plums',
                ],
                'result' => [
                    'added' => [
                        2 => 'plums',
                    ],
                ]
            ],
            # Removes oranges
            [
                'expected' => [
                    'apples',
                    'oranges',
                ],
                'actual' => [
                    'apples',
                ],
                'result' => [
                    'removed' => [
                        1 => 'oranges',
                    ],
                ]
            ],
            # Accepts Json
            [
                'expected' => json_encode([
                    'apples',
                    'oranges',
                ]),
                'actual' => json_encode([
                    'apples',
                ]),
                'result' => [
                    'removed' => [
                        1 => 'oranges',
                    ],
                ]
            ],
        ];
    }
}
