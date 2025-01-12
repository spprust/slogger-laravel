<?php

namespace SLoggerLaravel\Dispatcher\Transporter;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use SLoggerLaravel\Dispatcher\Transporter\Commands\LoadTransporterCommand;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;

readonly class TransporterProcess
{
    public function __construct(
        private ConsoleOutput $output,
        private TransporterLoader $loader
    ) {
    }

    public function handle(string $commandName): int
    {
        $this->output->writeln("handling: $commandName");

        if (!$this->loader->fileExists()) {
            Artisan::call(LoadTransporterCommand::class, outputBuffer: $this->output);
        }

        $envFileName = '.env.strans.' . Str::slug($commandName, '.');
        $envFilePath = base_path($envFileName);

        $this->initEnv($envFilePath);

        $command = "{$this->loader->getPath()} --env=$envFileName $commandName";

        $process = Process::fromShellCommandline($command)
            ->setTimeout(null);

        $process->start();

        pcntl_signal(SIGINT, function () use ($process) {
            $process->signal(SIGINT);

            $this->output->writeln('Received stop signal');
        });

        while (!$process->isStarted()) {
            sleep(1);
        }

        $this->output->writeln("started: $command");

        while ($process->isRunning()) {
            $this->readOutput($process);

            sleep(1);
        }

        $this->readOutput($process);

        unlink($envFilePath);

        $this->output->writeln("stopped: $command");

        return $process->getExitCode();
    }

    private function readOutput(Process $process): void
    {
        $output = [
            $process->getIncrementalOutput(),
            $process->getIncrementalErrorOutput(),
        ];

        $process->clearOutput()->clearErrorOutput();

        $message = trim(implode(PHP_EOL, array_filter($output)), PHP_EOL);

        if (!$message) {
            return;
        }

        $this->output->writeln($message);
    }

    private function initEnv(string $envFilePath): void
    {
        $evnValues = config('slogger.dispatchers.transporter.env');

        $content = '';

        foreach ($evnValues as $key => $value) {
            $content .= "$key=$value" . PHP_EOL;
        }

        file_put_contents($envFilePath, $content);
    }
}
