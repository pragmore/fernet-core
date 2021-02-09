<?php

declare(strict_types=1);

namespace Fernet\Component;

class Error404
{
    public function __toString(): string
    {
        return '<html lang="en"><body><h1>Error 404</h1><p>Page not found</p></body></html>';
    }
}
