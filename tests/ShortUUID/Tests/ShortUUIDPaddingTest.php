<?php

namespace ShortUUID\Tests;

use PHPUnit\Framework\TestCase;
use ShortUUID\ShortUUID;
use Ramsey\Uuid\Uuid;

class ShortUUIDPaddingTest extends TestCase
{
    public function testPadding(): void
    {
        $su = new ShortUUID();
        $randomUid = Uuid::uuid4();
        $smallestUid = Uuid::fromInteger('0');

        $encodedRandom = $su->encode($randomUid);
        $encodedSmall = $su->encode($smallestUid);

        $this->assertEquals(strlen($encodedRandom), strlen($encodedSmall));
    }

    public function testDecoding(): void
    {
        $su = new ShortUUID();
        $randomUid = Uuid::uuid4();
        $smallestUid = Uuid::fromInteger('0');

        $encodedRandom = $su->encode($randomUid);
        $encodedSmall = $su->encode($smallestUid);

        $this->assertEquals($su->decode($encodedSmall)->getHex(), $smallestUid->getHex());
        $this->assertEquals($su->decode($encodedRandom)->getHex(), $randomUid->getHex());
    }
}
