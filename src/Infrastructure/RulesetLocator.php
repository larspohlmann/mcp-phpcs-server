<?php
declare(strict_types = 1);

namespace Mcp\PhpcsServer\Infrastructure;

final class RulesetLocator
{
    /** Try to find a ruleset file upwards from CWD. */
    public function locate(): ?string
    {
        $candidates = ['phpcs.xml', 'phpcs.xml.dist', 'ruleset.xml'];
        $dir = getcwd() ?: __DIR__;

        for ($i = 0; $i < 4; $i++) {
            foreach ($candidates as $file) {
                $path = $dir . DIRECTORY_SEPARATOR . $file;

                if (is_file($path)) {
                    return $path;
                }
            }

            $parent = dirname($dir);

            if ($parent === $dir) {
                break;
            }

            $dir = $parent;
        }

        return null;
    }
}
