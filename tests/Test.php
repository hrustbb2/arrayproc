<?php

namespace hrustbb2\tests;

use PHPUnit\Framework\TestCase;
use hrustbb2\arrayproc\ArrayProcessor;

class Test extends TestCase {

    public function testMap()
    {
        $data = [
            (object) ['m_id' => 1, 'm_name' => 'n1', 'city_id' => 1, 'city_name' => 'cityName1', 'house_id' => 1, 'house_name' => 'hn1'],
            (object) ['m_id' => 1, 'm_name' => 'n1', 'city_id' => 1, 'city_name' => 'cityName1', 'house_id' => 2, 'house_name' => 'hn2'],
            (object) ['m_id' => 1, 'm_name' => 'n1', 'city_id' => 2, 'city_name' => 'cityName2', 'house_id' => 3, 'house_name' => 'hn3'],
            (object) ['m_id' => 1, 'm_name' => 'n1', 'city_id' => 2, 'city_name' => 'cityName2', 'house_id' => 4, 'house_name' => 'hn4'],
        ];
        
        $assert = [
            1 => [
                'id' => 1,
                'name' => 'n1',
                'cityes' => [
                    1 => [
                        'id' => 1,
                        'name' => 'cityName1',
                        'houses' => [
                            1 => [
                                'id' => 1,
                                'name' => 'hn1',
                            ],
                            2 => [
                                'id' => 2,
                                'name' => 'hn2',
                            ]
                        ]
                    ],
                    2 => [
                        'id' => 2,
                        'name' => 'cityName2',
                        'houses' => [
                            3 => [
                                'id' => 3,
                                'name' => 'hn3',
                            ],
                            4 => [
                                'id' => 4,
                                'name' => 'hn4',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $config = [
            'prefix' => 'm_',
            'cityes' => [
                'prefix' => 'city_',
                'houses' => [
                    'prefix' => 'house_',
                ]
            ]
        ];

        $arrayProc = new ArrayProcessor();
        $result = $arrayProc->process($config, $data)->resultArray();

        $r = json_encode($result);
        $a = json_encode($assert);
        $this->assertEquals($r, $a);
    }

}