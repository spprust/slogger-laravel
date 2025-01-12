<?php

namespace SLoggerLaravel\Dispatcher\Transporter\Commands;

use Illuminate\Console\Command;
use SLoggerLaravel\Dispatcher\Transporter\TransporterProcess;

class StartTransporterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slogger:transporter:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start transporter';

    public function handle(TransporterProcess $process): int
    {
        return $process->handle('start');
    }
}
