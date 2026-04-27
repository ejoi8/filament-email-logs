<?php

namespace Ejoi8\FilamentEmailLogs\Support;

use Ejoi8\FilamentEmailLogs\FilamentEmailLogsPlugin;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;

class EmailLogAuthorization
{
    public static function canAccess(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        $panel = Filament::getCurrentPanel();

        if ($panel !== null && $panel->hasPlugin(FilamentEmailLogsPlugin::ID)) {
            /** @var FilamentEmailLogsPlugin $plugin */
            $plugin = $panel->getPlugin(FilamentEmailLogsPlugin::ID);

            return $plugin->canAccess($user);
        }

        foreach (Filament::getPanels() as $registeredPanel) {
            if (! $registeredPanel->hasPlugin(FilamentEmailLogsPlugin::ID)) {
                continue;
            }

            /** @var FilamentEmailLogsPlugin $plugin */
            $plugin = $registeredPanel->getPlugin(FilamentEmailLogsPlugin::ID);

            if ($plugin->canAccess($user)) {
                return true;
            }
        }

        return false;
    }
}
