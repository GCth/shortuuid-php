<?php

namespace ShortUUID\Tests;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ShortUUID\ShortUUID;
use ShortUUID\ValueError;

class ShortUUIDTest extends TestCase
{
    public function testGeneration(): void
    {
        $su = new ShortUUID();
        $a = strlen($su->uuid());
        $this->assertTrue((20 < $a) && ($a < 24));
        $b = strlen($su->uuid('http://www.example.com/'));
        $this->assertTrue((20 < $b) && ($b < 24));
        $c = strlen($su->uuid('HTTP://www.example.com/'));
        $this->assertTrue((20 < $c) && ($c < 24));
        $d = strlen($su->uuid('example.com/'));
        $this->assertTrue((20 < $d) && ($d < 24));
    }

    public function testEncoding(): void
    {
        $su = new ShortUUID();
        $u = Uuid::fromString('3b1f8b40-222c-4a6e-b77e-779d5a94e21c');
        $this->assertEquals('bYRT25J5s7Bniqr4b58cXC', $su->encode($u));
    }

    public function testDecoding(): void
    {
        $su = new ShortUUID();
        $u = Uuid::fromString('3b1f8b40-222c-4a6e-b77e-779d5a94e21c');
        $this->assertEquals($su->decode('bYRT25J5s7Bniqr4b58cXC')->getHex(), $u->getHex());
    }

    public function testAlphabet1(): void
    {
        $alphabet = '01';
        $su1 = new ShortUUID($alphabet);
        $su2 = new ShortUUID();

        $this->assertEquals($alphabet, $su1->getAlphabet());

        $su1 = new ShortUUID('01010101010101');
        $this->assertEquals($alphabet, $su1->getAlphabet());

        $d = array_values(array_unique(str_split($su1->uuid())));
        sort($d, SORT_NATURAL);
        $this->assertEquals($d, str_split('01'));

        $a = strlen($su1->uuid());
        $b = strlen($su2->uuid());
        $this->assertTrue(($a > 116) && ($a < 140));
        $this->assertTrue(($b > 20) && ($b < 24));

        $u = Uuid::uuid4();
        $this->assertEquals($u->getHex(), $su1->decode($su1->encode($u))->getHex());

        $u = $su1->uuid();
        $this->assertEquals($u, $su1->encode($su1->decode($u)));
    }

    public function testAlphabetException1(): void
    {
        $this->expectException(ValueError::class);
        new ShortUUID('1');
    }

    public function testAlphabetException2(): void
    {
        $this->expectException(ValueError::class);
        new ShortUUID('1111111');
    }

    public function testEncodedLength(): void
    {
        $su1 = new ShortUUID();
        $this->assertEquals(22, $su1->encodedLength());

        $base64Alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

        $su2 = new ShortUUID($base64Alphabet);
        $this->assertEquals(22, $su2->encodedLength());

        $binaryAlphabet = '01';
        $su3 = new ShortUUID($binaryAlphabet);
        $this->assertEquals(128, $su3->encodedLength());

        $su4 = new ShortUUID();
        $this->assertEquals(11, $su4->encodedLength(8));
    }
}
