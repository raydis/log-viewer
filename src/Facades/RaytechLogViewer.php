<?php

namespace Raytech\LogViewer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void auth(mixed $restriction)
 * @method static void setAccessRestriction(mixed $restriction = null)
 * @method static void allowAll()
 * @method static void addAccessRestriction(mixed $restriction)
 * @method static mixed getAccessRestriction()
 * @method static void addLogFile(string $path)
 * @method static void addLogDirectory(string $directory)
 * @method static string url(array $parameters = [])
 * @method static array allFiles()
 *
 * @see \Raytech\LogViewer\RaytechLogViewer
 */
class RaytechLogViewer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'raytech-log-viewer';
    }
}
