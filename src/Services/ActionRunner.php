<?php

namespace Enessvg\LaravelTelegramDeployer\Services;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class ActionRunner
{
    /**
     * @param array<string, string> $params
     *
     * @return array<int, array<string, mixed>>
     */
    public function run(string $action, array $params = []): array
    {
        $actions = (array) config('telegram-deployer.actions', []);
        $steps = $actions[$action] ?? null;

        if (! is_array($steps) || $steps === []) {
            throw new RuntimeException("Action [{$action}] is not configured.");
        }

        $results = [];

        foreach ($steps as $index => $step) {
            if (! is_array($step)) {
                throw new RuntimeException("Action [{$action}] step [{$index}] is invalid.");
            }

            $type = (string) Arr::get($step, 'type', '');
            $command = (string) Arr::get($step, 'command', '');

            if (! in_array($type, ['artisan', 'shell'], true)) {
                throw new RuntimeException("Action [{$action}] step [{$index}] has unsupported type [{$type}].");
            }

            if ($command === '') {
                throw new RuntimeException("Action [{$action}] step [{$index}] has an empty command.");
            }

            $command = $this->resolveCommandTemplate($action, $index, $command, $params);
            $resolvedCommand = $type === 'artisan'
                ? sprintf('php artisan %s', $command)
                : $command;

            $timeout = (int) Arr::get($step, 'timeout', (int) config('telegram-deployer.runner.default_timeout', 300));
            $cwd = (string) Arr::get($step, 'cwd', (string) config('telegram-deployer.runner.working_directory', base_path()));

            $startedAt = now();

            try {
                $process = Process::path($cwd)->timeout($timeout)->run($resolvedCommand);
            } catch (ProcessTimedOutException $exception) {
                $results[] = [
                    'index' => $index,
                    'type' => $type,
                    'command' => $resolvedCommand,
                    'cwd' => $cwd,
                    'timeout' => $timeout,
                    'exit_code' => null,
                    'output' => '',
                    'error_output' => 'Step timed out.',
                    'started_at' => $startedAt->toIso8601String(),
                    'finished_at' => now()->toIso8601String(),
                ];

                throw new ActionFailedException(
                    "Step {$index} timed out after {$timeout} seconds.",
                    $results,
                    $exception,
                );
            }

            $results[] = [
                'index' => $index,
                'type' => $type,
                'command' => $resolvedCommand,
                'cwd' => $cwd,
                'timeout' => $timeout,
                'exit_code' => $process->exitCode(),
                'output' => $process->output(),
                'error_output' => $process->errorOutput(),
                'started_at' => $startedAt->toIso8601String(),
                'finished_at' => now()->toIso8601String(),
            ];

            if (! $process->successful()) {
                throw new ActionFailedException(
                    "Step {$index} failed with exit code {$process->exitCode()}.",
                    $results,
                );
            }
        }

        return $results;
    }

    /**
     * @param array<string, string> $params
     */
    private function resolveCommandTemplate(string $action, int $index, string $command, array $params): string
    {
        $resolved = preg_replace_callback(
            '/\{([A-Za-z0-9_-]+)\}/',
            function (array $matches) use ($action, $index, $params): string {
                $name = (string) ($matches[1] ?? '');
                $value = $params[$name] ?? null;

                if (! is_string($value) || $value === '') {
                    throw new RuntimeException("Action [{$action}] step [{$index}] requires parameter [{$name}].");
                }

                return escapeshellarg($value);
            },
            $command,
        );

        if (! is_string($resolved)) {
            throw new RuntimeException("Action [{$action}] step [{$index}] command template is invalid.");
        }

        return $resolved;
    }
}
