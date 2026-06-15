

<?php $__env->startSection('title', 'User Manager'); ?>

<?php $__env->startSection('content'); ?>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">User Manager</h1>
            <p class="text-sm text-base-content/60 mt-1">Manage system users and roles</p>
        </div>
        <a href="<?php echo e(route('users.create')); ?>" class="btn btn-primary btn-sm">
            Add User
        </a>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr>
                                <td class="font-medium">
                                    <?php echo e($user->username); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->id === auth()->id()): ?>
                                        <span class="badge badge-ghost badge-sm ml-2">You</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td>
                                    <span
                                        class="badge <?php echo e($user->role === 'super_admin' ? 'badge-secondary' : 'badge-primary'); ?> badge-sm">
                                        <?php echo e($user->role === 'super_admin' ? 'Super Admin' : 'Admin'); ?>

                                    </span>
                                </td>
                                <td class="text-right space-x-2">
                                    <a href="<?php echo e(route('users.edit', $user)); ?>" class="btn btn-ghost btn-xs">Edit</a>
                                    <?php
                                        $isOwnAccount = $user->id === auth()->id();
                                        $isLastSuperAdmin = $user->role === 'super_admin' && $superAdminCount <= 1;
                                        $canDelete = !$isOwnAccount && !$isLastSuperAdmin;
                                    ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canDelete): ?>
                                        <form action="<?php echo e(route('users.destroy', $user)); ?>" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure you want to delete this user?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-ghost btn-xs text-error">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="btn btn-ghost btn-xs btn-disabled"
                                            title="<?php echo e($isOwnAccount ? 'You cannot delete your own account' : 'Cannot delete the last Super Admin'); ?>">Delete</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="3" class="text-center text-base-content/60">No users found.</td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\webstatus-v2\resources\views/users/index.blade.php ENDPATH**/ ?>