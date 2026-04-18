<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * @deprecated Use ai:validate-models [--provider=grok]
 */
class ValidateGrokModelsCommand extends Command
{
    protected $signature = 'ai:validate-grok-models
        {--dry-run : Show changes without writing DB}
        {--activate-available : Re-enable models found on provider}';

    protected $description = 'Alias for ai:validate-models restricted to the grok provider';

    public function handle(): int
    {
        return $this->call('ai:validate-models', [
            '--provider' => 'grok',
            '--dry-run' => $this->option('dry-run'),
            '--activate-available' => $this->option('activate-available'),
        ]);
    }
}
