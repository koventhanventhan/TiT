<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class MultiStudentTest extends TestCase
{
    /**
     * Test 1: User model has parent() relationship defined.
     */
    public function test_user_model_has_parent_relationship(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'parent'), 'User model should have parent() relationship');
    }

    /**
     * Test 2: User model has children() relationship defined.
     */
    public function test_user_model_has_children_relationship(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'children'), 'User model should have children() relationship');
    }

    /**
     * Test 3: parent_id is in the fillable array.
     */
    public function test_parent_id_is_fillable(): void
    {
        $user = new User();
        $this->assertContains('parent_id', $user->getFillable(), 'parent_id should be in the fillable array');
    }

    /**
     * Test 4: ProfileContext middleware class exists.
     */
    public function test_profile_context_middleware_exists(): void
    {
        $this->assertTrue(
            class_exists(\App\Http\Middleware\ProfileContext::class),
            'ProfileContext middleware should exist'
        );
    }

    /**
     * Test 5: ProfileContext middleware has a handle method.
     */
    public function test_profile_context_has_handle_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Http\Middleware\ProfileContext::class, 'handle'),
            'ProfileContext middleware should have a handle() method'
        );
    }

    /**
     * Test 6: AuthController has addSibling method.
     */
    public function test_auth_controller_has_add_sibling_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Http\Controllers\AuthController::class, 'addSibling'),
            'AuthController should have addSibling() method'
        );
    }

    /**
     * Test 7: parent() returns a BelongsTo relationship.
     */
    public function test_parent_relationship_is_belongs_to(): void
    {
        $user = new User();
        $relation = $user->parent();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    /**
     * Test 8: children() returns a HasMany relationship.
     */
    public function test_children_relationship_is_has_many(): void
    {
        $user = new User();
        $relation = $user->children();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
    }
}
