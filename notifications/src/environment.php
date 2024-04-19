<?php

function getEnvironmentVariable(string $variable)
{
    return $_SERVER[$variable] = $_ENV[$variable] = ($_SERVER[$variable] ?? $_ENV[$variable] ?? null) ?: null;
}
