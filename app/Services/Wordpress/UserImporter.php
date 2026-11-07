<?php

namespace App\Services\Wordpress;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserImporter extends WordpressImporter
{
    public function countUsers(): int
    {
        return (int) $this->wp()->table('users')->count();
    }

    /**
     * Fast path: bulk read + bulk upsert (no per-user bcrypt / Eloquent / role sync).
     *
     * @param  (callable(): void)|null  $onProgress  called once per WP user row
     * @return array{created: int, updated: int, pending_activation: int, duplicate_mobile: int}
     */
    public function migrateUsers(bool $dryRun = false, ?callable $onProgress = null): array
    {
        $created = 0;
        $updated = 0;
        $pendingActivation = 0;
        $duplicateMobile = 0;

        $orderCounts = $this->paidOrderCountsByCustomer();
        $candidates = [];

        foreach ($this->wp()->table('users')->orderBy('ID')->lazyById(500, 'ID')->chunk(500) as $batch) {
            $userMeta = $this->userMetaForIds($batch->pluck('ID')->all());
            foreach ($batch as $row) {
                $meta = $userMeta[$row->ID] ?? [];
                $mobile = $this->resolveMobile($meta, (string) $row->user_login);
                $candidates[] = [
                    'row' => $row,
                    'meta' => $meta,
                    'mobile' => $mobile,
                    'orders' => (int) ($orderCounts[(int) $row->ID] ?? 0),
                ];
            }
        }

        $winnersByMobile = $this->mobileWinners($candidates);

        $existingByWpId = DB::table('users')
            ->whereNotNull('wp_id')
            ->get(['id', 'wp_id', 'email', 'deleted_at'])
            ->keyBy(fn ($row) => (int) $row->wp_id);

        $usedEmails = [];
        foreach (DB::table('users')->whereNotNull('email')->get(['email', 'wp_id']) as $row) {
            $email = mb_strtolower((string) $row->email);
            $usedEmails[$email] = (int) ($row->wp_id ?? 0);
        }

        $now = now()->toDateTimeString();
        $rows = [];
        $winnerReleases = [];

        foreach ($candidates as $candidate) {
            $row = $candidate['row'];
            $meta = $candidate['meta'];
            $mobile = $candidate['mobile'];
            $wpId = (int) $row->ID;
            $isWinner = $mobile !== null && ($winnersByMobile[$mobile] ?? null) === $wpId;
            $isDuplicate = $mobile !== null && ! $isWinner;

            if ($isDuplicate) {
                $duplicateMobile++;
            }

            $status = $isWinner ? User::STATUS_ACTIVE : User::STATUS_PENDING_ACTIVATION;
            if ($status === User::STATUS_PENDING_ACTIVATION) {
                $pendingActivation++;
            }

            if ($isWinner && $mobile) {
                $winnerReleases[$mobile] = $wpId;
            }

            $name = trim(($meta['first_name'] ?? '').' '.($meta['last_name'] ?? ''))
                ?: ($row->display_name ?: $row->user_login);

            $email = $this->claimUniqueEmail($row->user_email ?: null, $wpId, $usedEmails);
            $password = (string) $row->user_pass;
            if ($password === '') {
                $password = '!';
            }

            $existing = $existingByWpId->get($wpId);
            if ($existing) {
                $updated++;
            } else {
                $created++;
            }

            $rows[] = [
                'wp_id' => $wpId,
                'name' => mb_substr((string) $name, 0, 191),
                'first_name' => isset($meta['first_name']) ? mb_substr((string) $meta['first_name'], 0, 100) : null,
                'last_name' => isset($meta['last_name']) ? mb_substr((string) $meta['last_name'], 0, 100) : null,
                'email' => $email,
                'phone' => $isWinner ? $mobile : null,
                'mobile' => $isWinner ? $mobile : null,
                'claimed_mobile' => $isDuplicate ? $mobile : ($isWinner ? null : $this->rawClaimedMobile($meta, (string) $row->user_login)),
                'status' => $status,
                'password' => $password,
                'is_wp_password' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];
        }

        if ($dryRun) {
            foreach ($rows as $_) {
                $onProgress && $onProgress();
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'pending_activation' => $pendingActivation,
                'duplicate_mobile' => $duplicateMobile,
            ];
        }

        $this->bulkReleaseWinnerMobiles($winnerReleases);

        $updateColumns = [
            'name', 'first_name', 'last_name', 'email', 'phone', 'mobile', 'claimed_mobile',
            'status', 'password', 'is_wp_password', 'updated_at', 'deleted_at',
        ];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('users')->upsert($chunk, ['wp_id'], $updateColumns);
            if ($onProgress) {
                for ($i = 0, $n = count($chunk); $i < $n; $i++) {
                    $onProgress();
                }
            }
        }

        $wpIds = array_column($rows, 'wp_id');
        $localIds = [];
        foreach (array_chunk($wpIds, 1000) as $chunk) {
            foreach (DB::table('users')->whereIn('wp_id', $chunk)->pluck('id') as $id) {
                $localIds[] = (int) $id;
            }
        }
        $this->bulkEnsureSiteRoles($localIds);

        return [
            'created' => $created,
            'updated' => $updated,
            'pending_activation' => $pendingActivation,
            'duplicate_mobile' => $duplicateMobile,
        ];
    }
}
