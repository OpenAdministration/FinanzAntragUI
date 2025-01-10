<?php

namespace framework;

/**
 * Class Singleton
 */
abstract class Singleton
{
    private static array $instances = [];

    /**
     * @return static return initialized instance
     */
    final protected static function initSingleton(string $className): static
    {
        if (! isset(self::$instances[$className])) {
            self::$instances[$className] = new $className;
        }

        return self::$instances[$className];
    }

    /**
     * @return static Singleton instance of child, if setCredentials() was not done before or child is not correct configured
     */
    public static function getInstance(): static
    {
        return self::initSingleton(static::class);
    }

    final public function __clone()
    {
        // No cloning possible
    }

    final public function __debugInfo()
    {
        // No debug Info
        return null;
    }
}
