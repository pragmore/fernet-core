<?php
declare(strict_types=1);

namespace Fernet\Component;

use Fernet\Framework;

class FernetJs
{
    public bool $preventWrapper = true;

    public function __construct(Framework $framework)
    {
        $framework->setConfig('enableJs', true);
    }

    public function __toString()
    {
        return <<<HTML:
            <style>span.__fw{ display: inline-block }</style>"
            <script src="js/fernet.js"></script>
HTML;
    }
}