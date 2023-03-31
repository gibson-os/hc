<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Service;

use Codeception\Test\Unit;
use GibsonOS\Module\Hc\Service\TransformService;

class TransformServiceTest extends Unit
{
    /**
     * @var TransformService
     */
    private $transformService;

    protected function _before(): void
    {
        $this->transformService = new TransformService();
    }

    /**
     * @dataProvider getTransformData
     */
    public function testAsciiToHex(string $ascii, string $hex): void
    {
        $this->assertEquals($hex, $this->transformService->asciiToHex($ascii));
    }

    /**
     * @dataProvider getTransformData
     */
    public function testAsciiToUnsignedInt(string $ascii, string $hex, int $unsignedInt, array $unsignedInts): void
    {
        $this->assertEquals($unsignedInt, $this->transformService->asciiToUnsignedInt($ascii));

        foreach ($unsignedInts as $byte => $unsignedInt) {
            $this->assertEquals($unsignedInt, $this->transformService->asciiToUnsignedInt($ascii, $byte));
        }
    }

    /**
     * @dataProvider getTransformData
     */
    public function testAsciiToSignedInt(string $ascii, string $hex, int $unsignedInt, array $unsignedInts, int $signedInt, array $signedInts): void
    {
        $this->assertEquals($signedInt, $this->transformService->asciiToSignedInt($ascii));

        foreach ($signedInts as $byte => $signedInt) {
            $this->assertEquals($signedInt, $this->transformService->asciiToSignedInt($ascii, $byte));
        }
    }

    /**
     * @dataProvider getTransformData
     */
    public function testAsciiToBin(string $ascii, string $hex, int $unsignedInt, array $unsignedInts, int $signedInt, array $signedInts, array $bin): void
    {
        $this->assertEquals(implode(' ', $bin), $this->transformService->asciiToBin($ascii));

        foreach ($bin as $byte => $binByte) {
            $this->assertEquals($binByte, $this->transformService->asciiToBin($ascii, $byte));
        }
    }

    /**
     * @dataProvider getTransformData
     */
    public function testHexToAscii(string $ascii, string $hex): void
    {
        $this->assertEquals($ascii, $this->transformService->hexToAscii($hex));
    }

    /**
     * @dataProvider getTransformData
     */
    public function testHexToInt(string $ascii, string $hex, int $unsignedInt): void
    {
        $this->assertEquals($unsignedInt, $this->transformService->hexToInt($hex));
    }

    /**
     * @dataProvider getTransformData
     */
    public function testHexToBin(string $ascii, string $hex, int $unsignedInt, array $unsignedInts, int $signedInt, array $signedInts, array $bin): void
    {
        $this->assertEquals(implode(' ', $bin), $this->transformService->hexToBin($hex));

        foreach ($bin as $byte => $binByte) {
            $this->assertEquals($binByte, $this->transformService->hexToBin($hex, $byte));
        }
    }

    /**
     * @dataProvider getTransformData
     */
    public function testBinToAscii(string $ascii, string $hex, int $unsignedInt, array $unsignedInts, int $signedInt, array $signedInts, array $bin): void
    {
        $binString = implode(' ', $bin);
        $this->assertEquals($ascii, $this->transformService->binToAscii($binString));

        foreach ($bin as $byte => $binByte) {
            $this->assertEquals(substr($ascii, $byte, 1), $this->transformService->binToAscii($binString, $byte));
        }
    }

    public function getTransformData(): array
    {
        return [
            [
                'Handtuch',
                '48616e6474756368',
                5215571221201380200,
                [72, 97, 110, 100, 116, 117, 99, 104],
                5215571221201380200,
                [72, 97, 110, 100, 116, 117, 99, 104],
                ['01001000', '01100001', '01101110', '01100100', '01110100', '01110101', '01100011', '01101000'],
            ], [
                chr(128) . chr(165) . '7',
                '80a537',
                8430903,
                [128, 165, 55],
                -8346313,
                [-128, -91, 55],
                ['10000000', '10100101', '00110111'],
            ],
        ];
    }
}
