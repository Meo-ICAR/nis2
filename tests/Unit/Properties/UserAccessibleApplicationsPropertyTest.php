<?php

namespace Tests\Unit\Properties;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Eris\Generators;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for User::accessibleApplications()
 *
 * // Feature: openid-app-portal, Property 2, 3, 4, 6, 12, 13
 *
 * **Validates: Requirements 2.1, 4.3, 4.4, 4.5, 4.7, 2.5, 3.6**
 */
class UserAccessibleApplicationsPropertyTest extends TestCase
{
    use TestTrait;
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a User with a random unique email.
     */
    private function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name'  => 'Test User',
            'email' => 'user_' . uniqid() . '@example.com',
        ], $attrs));
    }

    /**
     * Create an Application with the given attributes.
     */
    private function makeApp(array $attrs = []): Application
    {
        static $counter = 0;
        $counter++;
        return Application::create(array_merge([
            'name'       => 'App ' . $counter . '_' . uniqid(),
            'url'        => 'https://example.com/app' . $counter,
            'sort_order' => 0,
            'is_active'  => true,
        ], $attrs));
    }

    /**
     * Create a Role with a unique name.
     */
    private function makeRole(array $attrs = []): Role
    {
        return Role::create(array_merge([
            'name' => 'Role_' . uniqid(),
        ], $attrs));
    }

    // =========================================================================
    // Task 3.1 — Property 2 & 3: Union and no duplicates
    // =========================================================================

    /**
     * Property 2: accessibleApplications() returns exactly the set of active
     * applications the user is authorized for — no more, no less.
     *
     * For any user with N direct permissions and M role-based permissions,
     * the result must equal the union of those active applications.
     *
     * // Feature: openid-app-portal, Property 2
     * **Validates: Requirements 2.1, 4.3**
     */
    public function test_accessible_applications_returns_exactly_authorized_active_apps(): void
    {
        $this->limitTo(100)->forAll(
            Generators::choose(0, 5),  // number of direct apps
            Generators::choose(0, 5)   // number of role apps
        )->then(function (int $directCount, int $roleCount) {
            // Create a fresh user for each iteration
            $user = $this->makeUser();

            // Create direct applications (all active)
            $directApps = collect();
            for ($i = 0; $i < $directCount; $i++) {
                $app = $this->makeApp(['is_active' => true]);
                $user->directApplications()->attach($app->id);
                $directApps->push($app);
            }

            // Create a role with role-based applications (all active)
            $roleApps = collect();
            if ($roleCount > 0) {
                $role = $this->makeRole();
                for ($i = 0; $i < $roleCount; $i++) {
                    $app = $this->makeApp(['is_active' => true]);
                    $role->applications()->attach($app->id);
                    $roleApps->push($app);
                }
                $user->roles()->attach($role->id, ['source' => 'manual']);
            }

            // The expected set is the union of direct + role apps (by id)
            $expectedIds = $directApps->merge($roleApps)->pluck('id')->unique()->sort()->values();

            $result = $user->accessibleApplications();
            $resultIds = $result->pluck('id')->sort()->values();

            $this->assertEquals(
                $expectedIds->toArray(),
                $resultIds->toArray(),
                "accessibleApplications() must return exactly the authorized active apps. " .
                "Expected IDs: [{$expectedIds->implode(',')}], Got: [{$resultIds->implode(',')}]"
            );
        });
    }

    /**
     * Property 3: For any user with both direct and role-based access to the
     * same application, accessibleApplications() returns that application exactly once.
     *
     * // Feature: openid-app-portal, Property 3
     * **Validates: Requirements 4.4**
     */
    public function test_accessible_applications_has_no_duplicates_when_overlap(): void
    {
        $this->limitTo(100)->forAll(
            Generators::choose(1, 5)  // number of overlapping apps
        )->then(function (int $overlapCount) {
            $user = $this->makeUser();
            $role = $this->makeRole();
            $user->roles()->attach($role->id, ['source' => 'manual']);

            // Create apps that are assigned BOTH directly and via role
            for ($i = 0; $i < $overlapCount; $i++) {
                $app = $this->makeApp(['is_active' => true]);
                $user->directApplications()->attach($app->id);
                $role->applications()->attach($app->id);
            }

            $result = $user->accessibleApplications();

            // No duplicates: count of unique IDs must equal total count
            $this->assertCount(
                $result->pluck('id')->unique()->count(),
                $result,
                "accessibleApplications() must not contain duplicate applications. " .
                "Got {$result->count()} items but only {$result->pluck('id')->unique()->count()} unique IDs."
            );

            // Specifically: should have exactly $overlapCount apps (not 2*$overlapCount)
            $this->assertCount(
                $overlapCount,
                $result,
                "accessibleApplications() must return each overlapping app exactly once. " .
                "Expected {$overlapCount}, got {$result->count()}."
            );
        });
    }

    // =========================================================================
    // Task 3.2 — Property 4 & 6: Sort order and is_active filter
    // =========================================================================

    /**
     * Property 4: For any set of applications with arbitrary sort_order values,
     * accessibleApplications() returns them in non-decreasing sort_order order.
     *
     * // Feature: openid-app-portal, Property 4
     * **Validates: Requirements 2.5**
     */
    public function test_accessible_applications_are_sorted_by_sort_order(): void
    {
        $this->limitTo(100)->forAll(
            Generators::choose(2, 6)  // number of apps to create
        )->then(function (int $appCount) {
            $user = $this->makeUser();

            // Create apps with random sort_order values (not necessarily unique)
            $sortOrders = [];
            for ($i = 0; $i < $appCount; $i++) {
                $sortOrder = rand(-100, 100);
                $sortOrders[] = $sortOrder;
                $app = $this->makeApp([
                    'is_active'  => true,
                    'sort_order' => $sortOrder,
                ]);
                $user->directApplications()->attach($app->id);
            }

            $result = $user->accessibleApplications();

            // Verify non-decreasing order
            $resultSortOrders = $result->pluck('sort_order')->toArray();
            $sorted = $resultSortOrders;
            sort($sorted);

            $this->assertEquals(
                $sorted,
                $resultSortOrders,
                "accessibleApplications() must return apps in non-decreasing sort_order. " .
                "Got: [" . implode(',', $resultSortOrders) . "]"
            );
        });
    }

    /**
     * Property 6: For any user with permission on an application, if is_active = false,
     * the application must NOT appear in accessibleApplications().
     *
     * // Feature: openid-app-portal, Property 6
     * **Validates: Requirements 3.6**
     */
    public function test_inactive_applications_are_excluded(): void
    {
        $this->limitTo(100)->forAll(
            Generators::choose(1, 4),  // number of active apps
            Generators::choose(1, 4)   // number of inactive apps
        )->then(function (int $activeCount, int $inactiveCount) {
            $user = $this->makeUser();

            $activeIds = [];
            for ($i = 0; $i < $activeCount; $i++) {
                $app = $this->makeApp(['is_active' => true]);
                $user->directApplications()->attach($app->id);
                $activeIds[] = $app->id;
            }

            $inactiveIds = [];
            for ($i = 0; $i < $inactiveCount; $i++) {
                $app = $this->makeApp(['is_active' => false]);
                $user->directApplications()->attach($app->id);
                $inactiveIds[] = $app->id;
            }

            $result = $user->accessibleApplications();
            $resultIds = $result->pluck('id')->toArray();

            // All active apps must be present
            foreach ($activeIds as $id) {
                $this->assertContains(
                    $id,
                    $resultIds,
                    "Active application (id={$id}) must appear in accessibleApplications()."
                );
            }

            // No inactive app must be present
            foreach ($inactiveIds as $id) {
                $this->assertNotContains(
                    $id,
                    $resultIds,
                    "Inactive application (id={$id}) must NOT appear in accessibleApplications()."
                );
            }
        });
    }

    // =========================================================================
    // Task 3.3 — Property 12 & 13: Idempotency and role access preservation
    // =========================================================================

    /**
     * Property 12: For any user with access to an application both directly and
     * via a role, revoking the direct permission must NOT remove the application
     * from accessibleApplications() (role access is preserved).
     *
     * // Feature: openid-app-portal, Property 12
     * **Validates: Requirements 4.5**
     */
    public function test_revoking_direct_permission_preserves_role_access(): void
    {
        $this->limitTo(100)->forAll(
            Generators::choose(1, 4)  // number of shared apps
        )->then(function (int $sharedCount) {
            $user = $this->makeUser();
            $role = $this->makeRole();
            $user->roles()->attach($role->id, ['source' => 'manual']);

            $sharedAppIds = [];
            for ($i = 0; $i < $sharedCount; $i++) {
                $app = $this->makeApp(['is_active' => true]);
                // Assign both directly and via role
                $user->directApplications()->attach($app->id);
                $role->applications()->attach($app->id);
                $sharedAppIds[] = $app->id;
            }

            // Verify apps are accessible before revocation
            $before = $user->accessibleApplications()->pluck('id')->toArray();
            foreach ($sharedAppIds as $id) {
                $this->assertContains($id, $before, "App {$id} should be accessible before revocation.");
            }

            // Revoke all direct permissions
            $user->directApplications()->detach($sharedAppIds);

            // Apps must still be accessible via role
            $after = $user->accessibleApplications()->pluck('id')->toArray();
            foreach ($sharedAppIds as $id) {
                $this->assertContains(
                    $id,
                    $after,
                    "App {$id} must still be accessible via role after direct permission revocation."
                );
            }
        });
    }

    /**
     * Property 13: For any (user, application) pair, assigning the direct permission
     * N times (N ≥ 1) must result in exactly one record in user_application and no errors.
     *
     * // Feature: openid-app-portal, Property 13
     * **Validates: Requirements 4.7**
     */
    public function test_assigning_direct_permission_multiple_times_is_idempotent(): void
    {
        $this->limitTo(100)->forAll(
            Generators::choose(1, 10)  // N: number of times to assign the permission
        )->then(function (int $n) {
            $user = $this->makeUser();
            $app  = $this->makeApp(['is_active' => true]);

            // Assign the same permission N times using syncWithoutDetaching (idempotent)
            for ($i = 0; $i < $n; $i++) {
                // Use firstOrCreate pattern via syncWithoutDetaching to avoid duplicates
                $user->directApplications()->syncWithoutDetaching([$app->id]);
            }

            // There must be exactly one record in user_application
            $count = \Illuminate\Support\Facades\DB::table('user_application')
                ->where('user_id', $user->id)
                ->where('application_id', $app->id)
                ->count();

            $this->assertSame(
                1,
                $count,
                "Assigning the same direct permission {$n} time(s) must produce exactly 1 record in user_application, got {$count}."
            );

            // The app must appear exactly once in accessibleApplications()
            $result = $user->accessibleApplications();
            $matchingApps = $result->where('id', $app->id);

            $this->assertCount(
                1,
                $matchingApps,
                "App must appear exactly once in accessibleApplications() after {$n} idempotent assignments."
            );
        });
    }
}
