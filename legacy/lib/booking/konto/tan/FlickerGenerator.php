<?php

namespace booking\konto\tan;

use SVG\SVG;

class FlickerGenerator
{
    private string $challenge;

    private StartCode $startCode;

    private DataElement $de1;

    private DataElement $de2;

    private DataElement $de3;

    public function __construct(string $challengeText)
    {
        $this->challenge = $challengeText;
        $this->parseChallenge();
    }

    private function parseChallenge(): void
    {
        $reducedChallenge = trim(str_replace(' ', '', $this->challenge));
        // length of whole challenge (without lc) max 255 | encoding: base 10
        $lc = (int) substr($reducedChallenge, 0, 3);
        $reducedChallenge = substr($reducedChallenge, 3);
        if (strlen($reducedChallenge) !== $lc) {
            throw new \InvalidArgumentException('Wrong length of TAN Challenge - only Version 1.4 supported');
        }

        [$reducedChallenge, $this->startCode] = StartCode::parseNextBlock($reducedChallenge);
        [$reducedChallenge, $this->de1] = DataElement::parseNextBlock($reducedChallenge);
        [$reducedChallenge, $this->de2] = DataElement::parseNextBlock($reducedChallenge);
        [$reducedChallenge, $this->de3] = DataElement::parseNextBlock($reducedChallenge);

        if (! empty($reducedChallenge)) {
            throw new \InvalidArgumentException("Challenge has unexpected ending $reducedChallenge");
        }
    }

    private function calcXorChecksum(): string
    {
        $xor = 0b0000; // bin Representation of 0
        $hex = str_split($this->getHexPayload());
        foreach ($hex as $hexChar) {
            $intVal = (int) base_convert($hexChar, 16, 10);
            $xor ^= $intVal;
        }

        return base_convert($xor, 10, 16);
    }

    private function getHexPayload(): string
    {
        $hex = $this->startCode->toHex();
        $hex .= $this->de1->toHex();
        $hex .= $this->de2->toHex();
        $hex .= $this->de3->toHex();
        // var_dump(implode('|', str_split($hex, 2)));
        $lc = strlen($hex) / 2 + 1;
        $lc = str_pad(base_convert($lc, 10, 16), 2, '0', STR_PAD_LEFT);

        return $lc.$hex;
    }

    private function calcLuhnChecksum(): int
    {
        $luhn = $this->startCode->getLuhnChecksum();
        $luhn += $this->de1->getLuhnChecksum();
        $luhn += $this->de2->getLuhnChecksum();
        $luhn += $this->de3->getLuhnChecksum();

        return (10 - ($luhn % 10)) % 10;
    }

    public function getSVG(int $freq = 10, int $width = 300): SVG
    {
        $payload = $this->getHexPayload();

        $luhn = $this->calcLuhnChecksum();

        $xor = $this->calcXorChecksum();

        $hexCode = $payload.$luhn.$xor;
        echo $hexCode;

        return new FlickerSVG($hexCode, $freq, $width, $width / 2);
    }

    public function __debugInfo(): ?array
    {
        return [
            'startcode' => $this->startCode,
            'de1' => $this->de1,
            'de2' => $this->de2,
            'de3' => $this->de3,
            'payload' => $this->getHexPayload(),
            'luhn' => $this->calcLuhnChecksum(),
            'xor' => $this->calcXorChecksum(),
        ];
    }
}
