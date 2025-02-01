<?php

namespace ShortUUID;

use Brick\Math\BigInteger;
use Ramsey\Uuid\Type\Integer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ShortUUID
{
    /** @var string[] */
    private array $alphabet;

    private int $alphabetLength;

    /**
     * divmod(a, b) -> (div, mod)
     * Return the tuple ((a-a%b)/b, a%b).
     *
     * @return BigInteger[]
     */
    private function divmod(BigInteger $a, int $b): array
    {
        $bInt = BigInteger::of($b);
        $x = bcdiv(bcsub($a->toBase(10), bcmod($a, $bInt->toBase(10))), $bInt->toBase(10));
        $y = bcmod($a, $bInt->toBase(10));

        return [
            BigInteger::of($x),
            BigInteger::of($y),
        ];
    }

    /**
     * @param string|string[]|null $alphabet
     *
     * @throws ValueError
     */
    public function __construct(string|array|null $alphabet = null)
    {
        if ($alphabet === null) {
            $alphabet = str_split('23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz');
        }

        if (is_array($alphabet)) {
            $this->setAlphabet($alphabet);
        } else {
            $this->setAlphabet(str_split($alphabet));
        }
    }

    /**
     * Convert a number to a string, using the given alphabet.
     */
    private function numToString(Integer $numberPassed, ?int $padToLength = null): string
    {
        $output = '';

        $number = BigInteger::of($numberPassed);

        while ($number->compareTo('0') > 0) {
            $ret = $this->divmod($number, $this->alphabetLength);
            [$number, $digit] = $ret;
            assert($digit instanceof BigInteger);
            $output .= $this->alphabet[(int) $digit->toBase(10)];
        }
        if ($padToLength !== null) {
            $reminder = max($padToLength - strlen($output), 0);
            $output .= str_repeat($this->alphabet[0], $reminder);
        }

        return $output;
    }

    /**
     * Convert a string to a number, using the given alphabet..
     */
    private function stringToInt(string $string): string
    {
        $number = BigInteger::of(0);
        foreach (array_reverse(str_split($string)) as $char) {
            $x = bcmul($number->toBase(10), (string) $this->alphabetLength);
            $y = BigInteger::of(array_keys($this->alphabet, $char)[0]);
            $number = BigInteger::of(bcadd($x, $y->toBase(10)));
        }

        return $number;
    }

    /**
     * Encodes a UUID into a string (LSB first) according to the alphabet
     * If leftmost (MSB) bits 0, string might be shorter
     */
    public function encode(UuidInterface $uuid): string
    {
        $padLength = $this->encodedLength(strlen($uuid->getBytes()));

        return $this->numToString($uuid->getInteger(), $padLength);
    }

    /**
     * Decodes a string according to the current alphabet into a UUID
     * Raises ValueError when encountering illegal characters
     * or too long string
     * If string too short, fills leftmost (MSB) bits with 0.
     */
    public function decode(string $string): UuidInterface
    {
        return Uuid::fromInteger($this->stringToInt($string));
    }

    /**
     * Generate and return a UUID.
     * If the $name parameter is provided, set the namespace to the provided
     * name and generate a UUID.
     */
    public function uuid(?string $name = null): string
    {
        if ($name === null) {
            $uuid = Uuid::uuid4();
        } elseif (stripos($name, 'http') === false) {
            $uuid = Uuid::uuid5(Uuid::NAMESPACE_DNS, $name);
        } else {
            $uuid = Uuid::uuid5(Uuid::NAMESPACE_URL, $name);
        }

        return $this->encode($uuid);
    }

    public function random(): void
    {
        throw new \BadMethodCallException('Not Implemented!!');
    }

    /**
     * Return the current alphabet used for new UUIDs.
     */
    public function getAlphabet(): string
    {
        return implode('', $this->alphabet);
    }

    /**
     * @param string[] $alphabet
     *
     * @throws ValueError
     */
    private function setAlphabet(array $alphabet): void
    {
        // Turn the alphabet into a set and sort it to prevent duplicates
        // and ensure reproducibility.
        $alphabet = array_values(array_unique($alphabet));
        sort($alphabet, SORT_NATURAL);
        $newAlphabetLength = count($alphabet);
        if ($newAlphabetLength <= 1) {
            throw new ValueError('Alphabet with more than one unique symbols required.');
        }

        $this->alphabet = $alphabet;
        $this->alphabetLength = $newAlphabetLength;
    }

    /**
     * Returns the string length of the shortened UUID.
     */
    public function encodedLength(int $numBytes = 16): int
    {
        $factor = log(256) / log($this->alphabetLength);

        return (int) ceil($factor * $numBytes);
    }
}
