<?php

namespace Coddict\BingAdsApiBundle\DependencyInjection\Interfaces;

interface BingTokenIO
{
    public function readToken(): ?string;
    public function writeToken(string $token): void;
}
