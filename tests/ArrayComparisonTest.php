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
        $sut = new ArrayComparison();

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
        $sut = new ArrayComparison();

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
        $sut = new ArrayComparison();

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

    public function testAssociativeArrayReturnsDiffForMultilayerChanges(): void
    {
        $sut = new ArrayComparison();

        $expected = [
            'address' => [
                'town' => 'Cardiff',
                'county' => 'Cardiff',
                'zip' => 'AA1123',
                'country' => [
                    'name' => 'United Kingdom',
                    'code' => 'UK',
                ]
            ],
            'name' => 'Jeff',
            'surname' => 'Smith',
        ];

        $actual = [
            'address' => [
                'town' => 'Cardiff',
                'county' => 'Cardiff County',
                'postcode' => 'AA1123',
                'country' => [
                    'name' => 'Wales',
                    'countryCode' => 'Cym',
                ]
            ],
            'test' => [
                'name' => 'Nested assoc',
                'object' => [
                    'test' => 'true',
                ],
            ],
            'name' => 'John',
            'familyName' => 'Smith',
        ];

        self::assertEquals([
            'removed' => [
                'address' => [
                    'zip' => 'AA1123',
                    'country' => [
                        'code' => 'UK',
                    ],
                ],
                'surname' => 'Smith',
            ],
            'changed' => [
                'address' => [
                    'county' => [
                        'old' => 'Cardiff',
                        'new' => 'Cardiff County',
                    ],
                    'country' => [
                        'name' => [
                            'old' => 'United Kingdom',
                            'new' => 'Wales',
                        ]
                    ],
                ],
                'name' => [
                    'old' => 'Jeff',
                    'new' => 'John',
                ],
            ],
            'added' => [
                'address' => [
                    'country' => [
                        'countryCode' => 'Cym',
                    ],
                    'postcode' => 'AA1123',
                ],
                'familyName' => 'Smith',
                'test' => [
                    'name' => 'Nested assoc',
                    'object' => [
                        'test' => 'true',
                    ],
                ]
            ],
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
