<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Exceptions;

use RuntimeException;

/**
 * Base exception for all domain errors raised by the Blog module.
 */
class BlogException extends RuntimeException {}
