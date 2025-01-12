<?php

namespace SLoggerLaravel\Dispatcher\Transporter\Commands;

use Illuminate\Console\Command;
use SLoggerLaravel\Dispatcher\Transporter\TransporterLoader;

class LoadTransporterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slogger:transporter:load';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load transporter';

    public function handle(TransporterLoader $loader): int
    {
        $this->components->task(
            'Downloading transporter',
            static function () use ($loader) {
                $loader->load();

                if (PHP_OS_FAMILY === 'Linux') {
                    exec('chmod +x ' . $loader->getPath());
                }
            }
        );

        return self::SUCCESS;
    }
}
