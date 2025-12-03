<?php

namespace Rappasoft\LaravelAuthenticationLog\Traits;

use Exception;
use UAParser\Parser;

trait ParsesUserAgent
{
    protected function parseUserAgent(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        try {
            $parser = Parser::create();
            $result = $parser->parse($userAgent);

            return $result->toString() ?: $userAgent;
        } catch (Exception) {
            return $userAgent;
        }
    }
}