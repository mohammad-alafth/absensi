<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AdminPermissionBootstrapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BootstrapAdminControlCenterPermission extends Command
{
    protected $signature = 'permission:bootstrap-admin-control-center
        {--actor-id= : Existing exact admin user ID recorded as the bootstrap actor}
        {--reason= : Human-readable audited reason}
        {--correlation-id= : Unique operator-supplied audit correlation ID}
        {--confirm : Explicitly confirm the exact local/testing bootstrap tuple}';

    protected $description = 'Bootstrap only admin.control-center.view for the exact admin role in local/testing';

    public function handle(AdminPermissionBootstrapService $bootstrap): int
    {
        if (!in_array((string) config('app.env'), ['local', 'testing'], true)) {
            $this->error('Bootstrap is allowed only in local or testing environments.');

            return self::FAILURE;
        }

        if (!$this->isLocalDatabaseAllowed()) {
            $this->error('Bootstrap requires an approved local database connection.');

            return self::FAILURE;
        }

        if (!$this->option('confirm')) {
            $this->error('Explicit confirmation is required. Re-run with --confirm.');

            return self::FAILURE;
        }

        $actorId = $this->option('actor-id');
        $reason = (string) $this->option('reason');
        $correlationId = (string) $this->option('correlation-id');

        if ($actorId === null || trim($reason) === '' || trim($correlationId) === '') {
            $this->error('--actor-id, --reason, and --correlation-id are required.');

            return self::FAILURE;
        }

        $actor = User::query()->find($actorId);
        if ($actor === null || $actor->role !== AdminPermissionBootstrapService::TARGET_ROLE) {
            $this->error('Bootstrap actor must be an existing exact admin user.');

            return self::FAILURE;
        }

        try {
            $result = $bootstrap->bootstrap($actor, $reason, $correlationId);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Bootstrap failed safely; no partial permission change was committed.');

            return self::FAILURE;
        }

        if ($result['idempotent']) {
            $this->info('Exact Admin capability is already granted; no audit or version change was created.');
        } else {
            $this->info('Exact Admin capability bootstrapped with one mapping, one audit event, and one version increment.');
        }

        return self::SUCCESS;
    }

    private function isLocalDatabaseAllowed(): bool
    {
        if (config('app.env') === 'testing') {
            return true;
        }

        $host = DB::connection()->getConfig('host');

        return in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
    }
}
