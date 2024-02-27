<?php

namespace ProcessMaker\Package\PackageSkeleton\Listeners;

use Carbon\Carbon;
use DateTime;
use ProcessMaker\Package\Packages\Events\PackageEvent;

class PackageListener
{
    /**
     * Gets the attributes from this package and add to event list
     * The attributes are read them from composer.json file for this package,
     * The especial attribute "expire_in" is getting from ioncube loader function
     * with timezone of the ProcessMaker server
     * @param PackageEvent $event
     * @return mixed
     */
    public function handle(PackageEvent $event)
    {
        $composer = json_decode(file_get_contents(__DIR__ . '/../../composer.json'), true);
        $expires_on = null;
        if (ioncube_file_info()) {
            $expires_on = Carbon::createFromTimestamp(
                ioncube_file_info()['FILE_EXPIRY'],
                config('app.timezone')
            )->format(
                DateTime::ISO8601,
            );
        }

        $infoPackage = [
            'name' => $composer['name'],
            'friendly_name' => isset($composer['friendly_name']) ? $composer['friendly_name'] : '',
            'description' => $composer['description'],
            'version' => $composer['version'],
            'expires_on' => $expires_on,
        ];
        $event->packages->push($infoPackage);

        return $event->packages->last();
    }
}
